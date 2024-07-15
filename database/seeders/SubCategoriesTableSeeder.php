<?php
namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubCategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();

        $subCategories = [
            ['name' => 'Double', 'category_id' => 1, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Harup', 'category_id' => 1, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Crossing', 'category_id' => 1, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jantri', 'category_id' => 1, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],

            ['name' => 'Double', 'category_id' => 2, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Harup', 'category_id' => 2, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Crossing', 'category_id' => 2, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jantri', 'category_id' => 2, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],

            ['name' => 'Double', 'category_id' => 3, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Harup', 'category_id' => 3, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Crossing', 'category_id' => 3, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jantri', 'category_id' => 3, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],

            ['name' => 'Double', 'category_id' => 4, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Harup', 'category_id' => 4, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Crossing', 'category_id' => 4, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jantri', 'category_id' => 4, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],

            ['name' => 'Double', 'category_id' => 5, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Harup', 'category_id' => 5, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Crossing', 'category_id' => 5, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jantri', 'category_id' => 5, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],

            ['name' => 'Double', 'category_id' => 6, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Harup', 'category_id' => 6, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Crossing', 'category_id' => 6, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jantri', 'category_id' => 6, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],

            ['name' => 'Double', 'category_id' => 7, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Harup', 'category_id' => 7, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Crossing', 'category_id' => 7, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jantri', 'category_id' => 7, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],

            ['name' => 'Double', 'category_id' => 8, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Harup', 'category_id' => 8, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Crossing', 'category_id' => 8, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jantri', 'category_id' => 8, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],

            ['name' => 'Double', 'category_id' => 9, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Harup', 'category_id' => 9, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Crossing', 'category_id' => 9, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jantri', 'category_id' => 9, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],

            ['name' => 'Double', 'category_id' => 10, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Harup', 'category_id' => 10, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Crossing', 'category_id' => 10, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jantri', 'category_id' => 10, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],

        ];

        DB::table('sub_categories')->insert($subCategories);
    }
}
