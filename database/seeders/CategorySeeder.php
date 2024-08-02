<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Sample categories data
        $categories = [
            [
                'name' => 'Category A',
                'open_time' => '08:00:00',
                'last_time' => '18:00:00',
            ],
            [
                'name' => 'Category B',
                'open_time' => '09:00:00',
                'last_time' => '17:00:00',
            ],
            [
                'name' => 'Category C',
                'open_time' => '08:00:00',
                'last_time' => '18:00:00',
            ],
            [
                'name' => 'Category D',
                'open_time' => '09:00:00',
                'last_time' => '17:00:00',
            ],
            [
                'name' => 'Category E',
                'open_time' => '08:00:00',
                'last_time' => '18:00:00',
            ],
            [
                'name' => 'Category F',
                'open_time' => '09:00:00',
                'last_time' => '17:00:00',
            ],
            [
                'name' => 'Category G',
                'open_time' => '08:00:00',
                'last_time' => '18:00:00',
            ],
            [
                'name' => 'Category H',
                'open_time' => '09:00:00',
                'last_time' => '17:00:00',
            ],
            [
                'name' => 'Category I',
                'open_time' => '08:00:00',
                'last_time' => '18:00:00',
            ],
            [
                'name' => 'Category J',
                'open_time' => '09:00:00',
                'last_time' => '17:00:00',
            ],
            [
                'name' => 'Category K',
                'open_time' => '08:00:00',
                'last_time' => '18:00:00',
            ],
            [
                'name' => 'Category L',
                'open_time' => '09:00:00',
                'last_time' => '17:00:00',
            ],
            // Add more categories as needed
        ];

        // Insert data into the database
        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
