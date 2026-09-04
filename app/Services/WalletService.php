<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Exception;

class WalletService
{
    /**
     * Helper to centralize transaction logging
     */
    private static function logTransaction($userId, $type, $amount, $desc, $balance, $image = null, $status = null)
    {
        return Transaction::create([
            'user_id' => $userId,
            'transaction_type' => $type,
            'amount' => $amount,
            'description' => $desc,
            'image' => $image,
            'confirm_payment' => $status,
            'available_balance' => $balance,
            'transaction_date' => now(),
        ]);
    }

    /**
     * Deduct the amount from the playable balances in the order: deposit -> winning.
     * Updates the main balance to reflect the total playable balance.
     */
    public static function deductPlayableBalance(User $user, $amount, $description = 'Deducted for game')
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $totalPlayable = $lockedUser->deposit_balance + $lockedUser->winning_balance;

            // Auto-migrate legacy balances for older users
            if ($totalPlayable == 0 && $lockedUser->balance > 0) {
                $lockedUser->deposit_balance = $lockedUser->balance;
                $totalPlayable = $lockedUser->balance;
            }

            if ($totalPlayable < $amount) {
                throw new Exception("Insufficient playable balance.");
            }

            $amountToDeduct = $amount;

            // 2. Deduct from deposit balance
            if ($amountToDeduct > 0) {
                if ($lockedUser->deposit_balance >= $amountToDeduct) {
                    $lockedUser->deposit_balance -= $amountToDeduct;
                    $amountToDeduct = 0;
                } else {
                    $amountToDeduct -= $lockedUser->deposit_balance;
                    $lockedUser->deposit_balance = 0;
                }
            }

            // 3. Deduct from winning balance
            if ($amountToDeduct > 0) {
                if ($lockedUser->winning_balance >= $amountToDeduct) {
                    $lockedUser->winning_balance -= $amountToDeduct;
                    $amountToDeduct = 0;
                } else {
                    throw new Exception("Insufficient specific balances despite total balance being sufficient. Data inconsistency detected.");
                }
            }

            // Update main balance
            $lockedUser->recalculateBalance();

            // Log transaction
            $transaction = self::logTransaction($lockedUser->id, 'loss', $amount, $description, $lockedUser->balance);

            return $transaction;
        });
    }

    /**
     * Deduct strictly from winning balance for withdrawals.
     */
    public static function deductWithdrawableBalance(User $user, $amount, $description = 'Withdrawal request')
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            if ($lockedUser->winning_balance < $amount) {
                throw new Exception("Insufficient withdrawable winning balance.");
            }

            $lockedUser->winning_balance -= $amount;
            
            // Update main balance
            $lockedUser->recalculateBalance();

            // Typically withdrawals are logged when requested or approved, based on caller. 
            // We just update the balance here.
            return $lockedUser;
        });
    }

    /**
     * Add winnings to winning balance.
     */
    public static function addWinning(User $user, $amount, $description = 'Game Won')
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $lockedUser->winning_balance += $amount;
            
            // Update main balance
            $lockedUser->recalculateBalance();

            $transaction = self::logTransaction($lockedUser->id, 'won', $amount, $description, $lockedUser->balance);

            return $transaction;
        });
    }

    /**
     * Add deposit money. Calculates and applies deposit bonus based on settings.
     */
    public static function addDeposit(User $user, $amount, $description = 'Added money to balance', $transactionImage = null)
    {
        return DB::transaction(function () use ($user, $amount, $description, $transactionImage) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $lockedUser->deposit_balance += $amount;
            
            // Calculate deposit bonus (bonus unlocking)
            $bonusPercentageSetting = \App\Models\AppSetting::where('key', 'deposit_bonus_percentage')->first();
            $bonusPercentage = $bonusPercentageSetting ? (float)$bonusPercentageSetting->value : 0;
            
            $conversionAmount = 0;
            if ($bonusPercentage > 0) {
                // How much bonus we are allowed to convert
                $targetConversion = ($amount * $bonusPercentage) / 100;
                
                // We can only convert up to what the user actually has in their bonus_balance
                $conversionAmount = min($targetConversion, $lockedUser->bonus_balance);
                
                if ($conversionAmount > 0) {
                    $lockedUser->bonus_balance -= $conversionAmount;
                    $lockedUser->deposit_balance += $conversionAmount;
                }
            }

            // Update main balance
            $lockedUser->recalculateBalance();

            $transaction = self::logTransaction($lockedUser->id, 'credit', $amount, $description, $lockedUser->balance, $transactionImage, 'received_successfully');

            // If there's a bonus, we should ideally log it as a separate transaction for clarity
            if ($conversionAmount > 0) {
                self::logTransaction($lockedUser->id, 'bonus', $conversionAmount, 'Promotional deposit bonus unlocked', $lockedUser->balance, null, 'received_successfully');
            }

            // Award 5% to referrer if user was referred
            if ($lockedUser->referrer_id) {
                $referrer = User::find($lockedUser->referrer_id);
                if ($referrer) {
                    $referralBonusAmount = $amount * 0.05;
                    self::addReferralBonus($referrer, $referralBonusAmount, 'Referral Bonus: 5% of deposit');
                }
            }

            return $transaction;
        });
    }

    /**
     * Add joining bonus to a user.
     */
    public static function addJoiningBonus(User $user)
    {
        return DB::transaction(function () use ($user) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $joiningBonusSetting = \App\Models\AppSetting::where('key', 'joining_bonus')->first();
            $bonusAmount = $joiningBonusSetting ? (float)$joiningBonusSetting->value : 0;

            if ($bonusAmount > 0) {
                $lockedUser->bonus_balance += $bonusAmount;
                $lockedUser->recalculateBalance();

                self::logTransaction($lockedUser->id, 'bonus', $bonusAmount, 'Joining bonus', $lockedUser->balance, null, 'received_successfully');
            }
            
            return $lockedUser;
        });
    }

    /**
     * Add referral bonus to a user.
     */
    public static function addReferralBonus(User $user, $amount, $description = 'Referral Bonus')
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            if ($amount > 0) {
                $lockedUser->bonus_balance += $amount;
                $lockedUser->recalculateBalance();

                self::logTransaction($lockedUser->id, 'bonus', $amount, $description, $lockedUser->balance, null, 'received_successfully');
            }
            
            return $lockedUser;
        });
    }

    /**
     * Deduct from winning directly (e.g. revoking a win).
     * If they don't have enough balance, their deposit_balance will go negative to enforce the debt.
     */
    public static function revertWinning(User $user, $amount, $description = 'Game Reverted: Deduction for revoked number')
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $amountToDeduct = $amount;

            // 1. Take from winning
            if ($lockedUser->winning_balance >= $amountToDeduct) {
                $lockedUser->winning_balance -= $amountToDeduct;
                $amountToDeduct = 0;
            } else {
                $amountToDeduct -= $lockedUser->winning_balance;
                $lockedUser->winning_balance = 0;
            }

            // 2. Take from bonus
            if ($amountToDeduct > 0) {
                if ($lockedUser->bonus_balance >= $amountToDeduct) {
                    $lockedUser->bonus_balance -= $amountToDeduct;
                    $amountToDeduct = 0;
                } else {
                    $amountToDeduct -= $lockedUser->bonus_balance;
                    $lockedUser->bonus_balance = 0;
                }
            }
            
            // 3. Take from deposit (allow it to go negative to track the remaining debt)
            if ($amountToDeduct > 0) {
                $lockedUser->deposit_balance -= $amountToDeduct;
            }

            $lockedUser->recalculateBalance();

            self::logTransaction($lockedUser->id, 'debit', $amount, $description, $lockedUser->balance);
            
            return $lockedUser;
        });
    }
}
