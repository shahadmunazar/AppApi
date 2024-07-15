<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlayGamesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get all users and categories
        $users = User::all();
        $categories = Category::all();
        $play_Game_id = SubCategory::all();

        // Prepare an array to hold the data
        $playGamesData = [];

        // Generate 1000 records
        for ($i = 0; $i < 4599; $i++) {
            // Randomly select a user and a category
            $user = $users->random();
            $category = $categories->random();
            $PlayGame_id = $play_Game_id->random();

            // Create a new play game record
            $playGamesData[] = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'category_id' => $category->id,
                'Playing_Name' => 'Game' . rand(1, 10),
                'play_type' => 'Type' . rand(1, 5),
                'ander_harup' => 'Option' . rand(1, 10),
                'bahar_harup' => 'Option' . rand(1, 10),
                'play_game_id' => $PlayGame_id->id,
                'entered_number' => rand(1, 100),
                'entered_amount' => rand(50, 500) . '.' . rand(0, 99),
                'status' => 'waiting',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert the data into the play_games table
        DB::table('play_games')->insert($playGamesData);
    }
}
