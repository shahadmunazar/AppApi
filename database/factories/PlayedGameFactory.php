<?php

namespace Database\Factories;

use App\Models\PlayedGame;
use App\Models\User;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayedGameFactory extends Factory
{
    protected $model = PlayedGame::class;

    public function definition()
    {
        $user = User::inRandomOrder()->first();
        $category = Category::inRandomOrder()->first();
        $subCategory = SubCategory::inRandomOrder()->first();
        return [
            'user_id' => $user ? $user->id : null,
            'category_id' => $category ? $category->id : null,
            'played_game_id' => $subCategory ? $subCategory->id : null,
            'played_game_type' => $this->faker->word,
            'entered_number' => $this->faker->randomNumber(2),
            'entered_amount' => $this->faker->randomFloat(2, 1, 100),
            'is_open' => $this->faker->randomElement(['waiting', 'pending']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
