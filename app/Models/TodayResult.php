<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodayResult extends Model
{
    use HasFactory;

    protected $table = 'todays_numbers';
    protected $fillable = [
        'category_id',
        'category_name',
        'open_number',
        'open_time',
    ];
}
