<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalMoney extends Model
{
    use HasFactory;

    protected $table = 'withdrawal_money';

    protected $fillable = [
        'user_id',
        'request_money',
        'mobile_no',
        'upi_id',
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'bank_name',
        'branch_name',
        'withdrawal_money_status'
    ];
}
