<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Transaction extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;
    protected $table = 'transactions';
    protected $fillable = [
        'user_id',
        'transaction_type',
        'amount',
        'image',
        'description',
        'transaction_date',
        'available_balance',
        'confirm_payment'
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
