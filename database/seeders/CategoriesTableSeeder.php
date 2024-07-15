<?php
namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();

        $categories = [
            ['name' => 'Deshawar', 'open_time' => '06:20 AM', 'last_time' => '03:20 AM', 'no_open' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Super Delhi Night', 'open_time' => '06:20 AM', 'last_time' => '01:30 AM', 'no_open' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Faridabad', 'open_time' => '06:20 AM', 'last_time' => '17:45 PM', 'no_open' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ghazibad', 'open_time' => '06:20 AM', 'last_time' => '20:00 PM', 'no_open' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gali', 'open_time' => '06:20 AM', 'last_time' => '23:00 PM', 'no_open' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sri Nagar', 'open_time' => '06:20 AM', 'last_time' => '21:45 PM', 'no_open' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'New Faridabad', 'open_time' => '06:20 AM', 'last_time' => '17:45 PM', 'no_open' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Meerut City', 'open_time' => '06:20 AM', 'last_time' => '15:50 PM', 'no_open' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'SK Night', 'open_time' => '06:20 AM', 'last_time' => '22:40 AM', 'no_open' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sl Night', 'open_time' => '06:20 AM', 'last_time' => '03:00 PM', 'no_open' => 0, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('categories')->insert($categories);
    }
}
