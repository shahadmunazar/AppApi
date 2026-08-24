<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppNumber extends Model
{
    use HasFactory;
    protected $table = 'whatsapp_numbers';

    protected $fillable = ['Name', 'Mobile', 'status','message_sent'];
}
