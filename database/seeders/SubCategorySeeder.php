<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\SubCategory;

class SubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Fetching all categories
        $categories = Category::all();

        // Define sample subcategories data
        $subcategories = [];

        foreach ($categories as $category) {
            // Ensure at least one subcategory per category
            $subcategories[] = [
                'name' => 'Default Subcategory for ' . $category->name,
                'category_id' => $category->id,
            ];

            // Add additional subcategories as needed
            switch ($category->name) {
                case 'Category A':
                    $subcategories[] = [
                        'name' => 'Subcategory A1',
                        'category_id' => $category->id,
                    ];
                    $subcategories[] = [
                        'name' => 'Subcategory A2',
                        'category_id' => $category->id,
                    ];
                    break;
                case 'Category B':
                    $subcategories[] = [
                        'name' => 'Subcategory B1',
                        'category_id' => $category->id,
                    ];
                    $subcategories[] = [
                        'name' => 'Subcategory B2',
                        'category_id' => $category->id,
                    ];
                    break;
                    // Add more cases for other categories as needed
                default:
                    // Default subcategory for any other category
                    $subcategories[] = [
                        'name' => 'Default Subcategory',
                        'category_id' => $category->id,
                    ];
                    break;
            }
        }

        // Insert data into the database
        foreach ($subcategories as $subcategory) {
            SubCategory::create($subcategory);
        }
    }
}
