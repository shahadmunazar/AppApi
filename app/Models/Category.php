<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "categories";

    protected $fillable = [
        'name', 'open_time', 'last_time', 'no_open',
    ];

    protected $dates = ['deleted_at'];

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class);
    }
    public function playGames()
    {
        return $this->hasMany(PlayGame::class, 'category_id');
    }

}
