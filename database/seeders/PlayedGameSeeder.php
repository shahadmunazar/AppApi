<?php

namespace Database\Seeders;

use App\Models\Played_Game;
use Illuminate\Database\Seeder;

class PlayedGameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Played_Game::factory()->count(5600)->create();
    }
}
