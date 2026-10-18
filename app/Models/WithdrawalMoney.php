<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class WithdrawalMoney extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'withdrawal_money';

    protected $fillable = [
        'user_id',
        'request_money',
        'mobile_no',
        'upi_id',
        'acount_holder_name',
        'account_number',
        'ifsc_code',
        'bank_name',
        'branch_name',
        'withdrawal_money_status',
        'qr_code_image',
        'payment_screenshot'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
