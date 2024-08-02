<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayedGame extends Model
{
    use HasFactory;

    protected $table = 'played_game';

    protected $fillable = [
        'user_id',
        'category_id',
        'played_game_id',
        'played_game_type',
        'entered_number',
        'entered_amount',
        'won_game_price',
        'loss_game_price',
        'open_today_number',
        'is_open',
        'status'
    ];

    // Add any relationships, e.g., user, category, etc.
}
