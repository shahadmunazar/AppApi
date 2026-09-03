<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Exception;

class WalletService
{
    /**
     * Deduct the amount from the playable balances in the order: bonus -> deposit -> winning.
     * Updates the main balance to reflect the total playable balance.
     */
    public static function deductPlayableBalance(User $user, $amount, $description = 'Deducted for game')
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            if ($lockedUser->balance < $amount) {
                throw new Exception("Insufficient playable balance.");
            }

            $amountToDeduct = $amount;

            // 1. Deduct from bonus balance
            if ($lockedUser->bonus_balance >= $amountToDeduct) {
                $lockedUser->bonus_balance -= $amountToDeduct;
                $amountToDeduct = 0;
            } else {
                $amountToDeduct -= $lockedUser->bonus_balance;
                $lockedUser->bonus_balance = 0;
            }

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
            $lockedUser->balance = $lockedUser->bonus_balance + $lockedUser->deposit_balance + $lockedUser->winning_balance;
            $lockedUser->save();

            // Log transaction
            $transaction = Transaction::create([
                'user_id' => $lockedUser->id,
                'transaction_type' => 'loss', // Using 'loss' for game deductions to match existing enums if needed
                'amount' => $amount,
                'description' => $description,
                'available_balance' => $lockedUser->balance,
                'transaction_date' => now(),
            ]);

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
            $lockedUser->balance = $lockedUser->bonus_balance + $lockedUser->deposit_balance + $lockedUser->winning_balance;
            $lockedUser->save();

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
            $lockedUser->balance = $lockedUser->bonus_balance + $lockedUser->deposit_balance + $lockedUser->winning_balance;
            $lockedUser->save();

            $transaction = Transaction::create([
                'user_id' => $lockedUser->id,
                'transaction_type' => 'won',
                'amount' => $amount,
                'description' => $description,
                'transaction_date' => now(),
                'available_balance' => $lockedUser->balance
            ]);

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
            
            // Calculate deposit bonus
            $bonusPercentageSetting = \App\Models\AppSetting::where('key', 'deposit_bonus_percentage')->first();
            $bonusPercentage = $bonusPercentageSetting ? (float)$bonusPercentageSetting->value : 0;
            
            $bonusAmount = 0;
            if ($bonusPercentage > 0) {
                $bonusAmount = ($amount * $bonusPercentage) / 100;
                $lockedUser->bonus_balance += $bonusAmount;
            }

            $lockedUser->balance = $lockedUser->bonus_balance + $lockedUser->deposit_balance + $lockedUser->winning_balance;
            $lockedUser->save();

            $transaction = Transaction::create([
                'user_id' => $lockedUser->id,
                'transaction_type' => 'credit',
                'amount' => $amount,
                'description' => $description,
                'image' => $transactionImage,
                'confirm_payment' => 'received_successfully',
                'transaction_date' => now(),
                'available_balance' => $lockedUser->balance
            ]);

            // If there's a bonus, we should ideally log it as a separate transaction for clarity
            if ($bonusAmount > 0) {
                Transaction::create([
                    'user_id' => $lockedUser->id,
                    'transaction_type' => 'bonus',
                    'amount' => $bonusAmount,
                    'description' => 'Promotional deposit bonus',
                    'confirm_payment' => 'received_successfully',
                    'transaction_date' => now(),
                    'available_balance' => $lockedUser->balance
                ]);
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
                $lockedUser->balance = $lockedUser->bonus_balance + $lockedUser->deposit_balance + $lockedUser->winning_balance;
                $lockedUser->save();

                Transaction::create([
                    'user_id' => $lockedUser->id,
                    'transaction_type' => 'bonus',
                    'amount' => $bonusAmount,
                    'description' => 'Joining bonus',
                    'confirm_payment' => 'received_successfully',
                    'transaction_date' => now(),
                    'available_balance' => $lockedUser->balance
                ]);
            }
            
            return $lockedUser;
        });
    }

    /**
     * Deduct from winning directly (e.g. revoking a win).
     */
    public static function revertWinning(User $user, $amount, $description = 'Game Reverted: Deduction for revoked number')
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            // Note: If winning_balance goes negative, we might need to deduct from other balances, 
            // but strict rules imply they lose their winnings. Let's just deduct from winning for simplicity,
            // or if it goes below 0, take from deposit then bonus.
            $amountToDeduct = $amount;

            if ($lockedUser->winning_balance >= $amountToDeduct) {
                $lockedUser->winning_balance -= $amountToDeduct;
                $amountToDeduct = 0;
            } else {
                $amountToDeduct -= $lockedUser->winning_balance;
                $lockedUser->winning_balance = 0;
            }

            if ($amountToDeduct > 0) {
                if ($lockedUser->deposit_balance >= $amountToDeduct) {
                    $lockedUser->deposit_balance -= $amountToDeduct;
                    $amountToDeduct = 0;
                } else {
                    $amountToDeduct -= $lockedUser->deposit_balance;
                    $lockedUser->deposit_balance = 0;
                }
            }

            if ($amountToDeduct > 0) {
                if ($lockedUser->bonus_balance >= $amountToDeduct) {
                    $lockedUser->bonus_balance -= $amountToDeduct;
                    $amountToDeduct = 0;
                } else {
                    $amountToDeduct -= $lockedUser->bonus_balance;
                    $lockedUser->bonus_balance = 0;
                }
            }

            $lockedUser->balance = $lockedUser->bonus_balance + $lockedUser->deposit_balance + $lockedUser->winning_balance;
            $lockedUser->save();

            Transaction::create([
                'user_id' => $lockedUser->id,
                'transaction_type' => 'debit',
                'amount' => $amount,
                'description' => $description,
                'transaction_date' => now(),
                'available_balance' => $lockedUser->balance
            ]);
            
            return $lockedUser;
        });
    }
}
