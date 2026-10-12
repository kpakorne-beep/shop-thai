<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            'everyday-bags' => Category::firstOrCreate(
                ['slug' => 'everyday-bags'],
                [
                    'name' => 'Everyday Bags',
                    'description' => 'Simple bags for daily use.',
                    'is_active' => true,
                ]
            ),
            'home-goods' => Category::firstOrCreate(
                ['slug' => 'home-goods'],
                [
                    'name' => 'Home Goods',
                    'description' => 'Small essentials for the home.',
                    'is_active' => true,
                ]
            ),
        ];

        $products = [
            [
                'category_id' => $categories['everyday-bags']->id,
                'name' => 'Canvas Tote Bag',
                'slug' => 'canvas-tote-bag',
                'sku' => 'BAG-001',
                'description' => 'A sturdy canvas tote for everyday errands.',
                'price' => 390,
                'stock' => 8,
            ],
            [
                'category_id' => $categories['everyday-bags']->id,
                'name' => 'Everyday Pouch',
                'slug' => 'everyday-pouch',
                'sku' => 'BAG-002',
                'description' => 'A compact pouch for small essentials.',
                'price' => 190,
                'stock' => 6,
            ],
            [
                'category_id' => $categories['home-goods']->id,
                'name' => 'Ceramic Coffee Mug',
                'slug' => 'ceramic-coffee-mug',
                'sku' => 'HOME-001',
                'description' => 'A simple ceramic mug for your daily coffee.',
                'price' => 290,
                'stock' => 5,
            ],
            [
                'category_id' => $categories['home-goods']->id,
                'name' => 'Cotton Coaster Set',
                'slug' => 'cotton-coaster-set',
                'sku' => 'HOME-002',
                'description' => 'A set of soft cotton coasters.',
                'price' => 120,
                'stock' => 4,
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['sku' => $product['sku']],
                $product
            );
        }
    }
}
