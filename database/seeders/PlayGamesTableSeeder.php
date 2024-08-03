<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\PlayGame;

class PlayGamesTableSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();
        $categories = Category::all();
        $playGameIds = [1, 2, 3, 4]; // Adjust as needed

        $playTypes = ['ander_harup', 'bahar_harup']; // Initialize the play types array
        $playGamesData = []; // Initialize array to hold the play games data

        for ($i = 0; $i < 4999; $i++) {
            // Randomly select a user and a category
            $user = $users->random();
            $category = $categories->random();
            $playGameId = $playGameIds[array_rand($playGameIds)]; // Randomly select a play game ID

            // Ensure entered_number is between 0 and 9 if play_game_id is 2; otherwise, between 0 and 99
            if ($playGameId === 2) {
                $enteredNumber = rand(0, 9); // Number between 0 and 9
            } else {
                $enteredNumber = rand(0, 99); // Number between 0 and 99
            }

            $playGamesData[] = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'category_id' => 9,
                'Playing_Name' => 'Game' . rand(1, 10),
                'play_type' => $playTypes[array_rand($playTypes)], // Use the initialized play types array
                'ander_harup' => 'ander_harup',
                'bahar_harup' => 'bahar_harup' . rand(1, 10),
                'play_game_id' => $playGameId,
                'entered_number' => $enteredNumber,
                'entered_amount' => rand(50, 500) . '.' . rand(0, 99),
                'status' => 'waiting',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Use the PlayGame model to insert data
        PlayGame::insert($playGamesData);
    }
}
