<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AppSetting;
use App\Services\WalletService;
use App\Models\Transaction;

class WalletRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup default configuration for tests
        AppSetting::updateOrCreate(['key' => 'joining_bonus'], ['value' => '100']);
        AppSetting::updateOrCreate(['key' => 'deposit_bonus_percentage'], ['value' => '10']);
    }

    private function createUser(array $data)
    {
        $user = new User();
        $user->name = $data['name'];
        $user->mobile = $data['mobile'];
        $user->email = $data['email'] ?? ($data['mobile'] . '@example.com');
        $user->password = $data['password'];
        $user->balance = $data['balance'] ?? 0;
        $user->bonus_balance = $data['bonus_balance'] ?? 0;
        $user->deposit_balance = $data['deposit_balance'] ?? 0;
        $user->winning_balance = $data['winning_balance'] ?? 0;
        $user->save();
        return $user;
    }

    public function test_joining_bonus_is_applied()
    {
        $user = $this->createUser([
            'name' => 'Test',
            'mobile' => '1234567890',
            'password' => 'secret',
            'balance' => 0,
            'bonus_balance' => 0,
        ]);
        
        WalletService::addJoiningBonus($user);
        
        $user->refresh();
        $this->assertEquals(100, $user->bonus_balance);
        $this->assertEquals(100, $user->balance);
        $this->assertEquals(0, $user->deposit_balance);
        $this->assertEquals(0, $user->winning_balance);
    }

    public function test_deposit_bonus_is_applied()
    {
        $user = $this->createUser([
            'name' => 'Test2',
            'mobile' => '1234567891',
            'password' => 'secret',
            'balance' => 0,
            'bonus_balance' => 0,
            'deposit_balance' => 0,
            'winning_balance' => 0,
        ]);
        
        WalletService::addDeposit($user, 100);
        
        $user->refresh();
        $this->assertEquals(10, $user->bonus_balance); // 10% of 100
        $this->assertEquals(100, $user->deposit_balance);
        $this->assertEquals(0, $user->winning_balance);
        $this->assertEquals(110, $user->balance);
    }

    public function test_deduct_playable_balance_order()
    {
        $user = $this->createUser([
            'name' => 'Test3',
            'mobile' => '1234567892',
            'password' => 'secret',
            'balance' => 110,
            'bonus_balance' => 10,
            'deposit_balance' => 100,
            'winning_balance' => 0,
        ]);

        WalletService::deductPlayableBalance($user, 15);
        $user->refresh();

        // 10 should come from bonus, 5 from deposit
        $this->assertEquals(0, $user->bonus_balance);
        $this->assertEquals(95, $user->deposit_balance);
        $this->assertEquals(95, $user->balance);
        
        WalletService::addWinning($user, 50);
        $user->refresh();
        $this->assertEquals(50, $user->winning_balance);
        $this->assertEquals(145, $user->balance);

        WalletService::deductPlayableBalance($user, 120);
        $user->refresh();

        // 95 from deposit, 25 from winning
        $this->assertEquals(0, $user->deposit_balance);
        $this->assertEquals(25, $user->winning_balance);
        $this->assertEquals(25, $user->balance);
    }

    public function test_deduct_withdrawable_balance()
    {
        $user = $this->createUser([
            'name' => 'Test4',
            'mobile' => '1234567893',
            'password' => 'secret',
            'balance' => 150,
            'bonus_balance' => 50,
            'deposit_balance' => 50,
            'winning_balance' => 50,
        ]);

        // Attempt to withdraw more than winning balance (but less than total balance)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient withdrawable winning balance.");
        WalletService::deductWithdrawableBalance($user, 60);

        // This should succeed
        WalletService::deductWithdrawableBalance($user, 40);
        $user->refresh();

        $this->assertEquals(10, $user->winning_balance);
        $this->assertEquals(110, $user->balance); // 50+50+10
    }
}
