<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayGame extends Model
{
    use HasFactory;

    protected $table = 'play_games';

    protected $fillable = [
        'user_id',
        'user_name',
        'category_id',
        'Playing_Name',
        'play_type',
        'ander_harup',
        'bahar_harup',
        'play_game_id',
        'today_number',
        'after_open_number_block',
        'open_time_number',
        'loss_amount',
        'won_amount',
        'entered_number',
        'entered_amount',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

}
