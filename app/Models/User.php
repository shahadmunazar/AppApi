<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;

class User extends Authenticatable implements Auditable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, \OwenIt\Auditing\Auditable;

    /**
     * Attributes excluded from the audit log for security.
     */
    protected $auditExclude = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

     protected $table = 'users';
    protected $fillable = [
        'name', 'email', 'mobile', 'password', 'referrer_id','status', 'referral_code', 'role', 'balance', 'bonus_balance', 'deposit_balance', 'winning_balance'
    ];

    public function referrals()
    {
        return $this->hasMany(User::class, 'referrer_id');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isAdmin()
    {
        return $this->is_admin;
    }

    /**
     * Recalculates the main withdrawable/playable balance based on specific balances.
     */
    public function recalculateBalance()
    {
        $this->balance = $this->deposit_balance + $this->winning_balance;
        $this->save();
    }

    public function transactions()
    {
        return $this->hasMany(\App\Models\Transaction::class, 'user_id');
    }

    public function playGames()
    {
        return $this->hasMany(\App\Models\PlayGame::class, 'user_id');
    }

    public function adminAddedMoneys()
    {
        return $this->hasMany(\App\Models\AdminAddedMoney::class, 'user_id');
    }

    public function addMoneyRequests()
    {
        return $this->hasMany(\App\Models\AddMoneyRequest::class, 'user_id');
    }

    public function withdrawals()
    {
        return $this->hasMany(\App\Models\WithdrawalMoney::class, 'user_id');
    }
}
