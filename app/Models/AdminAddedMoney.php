<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class AdminAddedMoney extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'admin_added_moneys';

    protected $fillable = [
        'user_id',
        'admin_id',
        'transaction_id',
        'amount',
        'previous_balance',
        'current_balance',
        'description',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'previous_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    /**
     * Get the user that was credited, including soft deleted users.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /**
     * Get the associated transaction if any.
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id')->withTrashed();
    }

    /**
     * Get the admin user who credited the amount, including soft deleted admins.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id')->withTrashed();
    }
}
