<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_lists_only_active_products_and_active_categories()
    {
        $category = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories',
        ]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'sku' => 'BAG-001',
            'price' => 25,
            'stock' => 3,
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'Hidden Product',
            'slug' => 'hidden-product',
            'sku' => 'HIDDEN-001',
            'price' => 10,
            'stock' => 1,
            'is_active' => false,
        ]);
        Category::create([
            'name' => 'Hidden category',
            'slug' => 'hidden-category',
            'is_active' => false,
        ]);

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertSee('Canvas Bag')
            ->assertDontSee('Hidden Product')
            ->assertSee('Accessories')
            ->assertDontSee('Hidden category');
    }

    public function test_storefront_can_search_and_filter_products()
    {
        $category = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories',
        ]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'sku' => 'BAG-001',
            'description' => 'A cotton carry bag',
            'price' => 25,
            'stock' => 3,
        ]);
        Product::create([
            'name' => 'Ceramic Cup',
            'slug' => 'ceramic-cup',
            'sku' => 'CUP-001',
            'price' => 15,
            'stock' => 4,
        ]);

        $this->get(route('shop.index', ['search' => 'cotton', 'category' => 'accessories']))
            ->assertOk()
            ->assertSee('Canvas Bag')
            ->assertDontSee('Ceramic Cup');

        $this->get(route('shop.index', ['category' => 'missing-category']))
            ->assertOk()
            ->assertSee('No products found.');
    }

    public function test_product_details_show_active_products_but_hide_inactive_products()
    {
        $activeProduct = Product::create([
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'sku' => 'BAG-001',
            'description' => 'A cotton carry bag',
            'price' => 25,
            'stock' => 3,
        ]);
        Product::create([
            'name' => 'Hidden Product',
            'slug' => 'hidden-product',
            'sku' => 'HIDDEN-001',
            'price' => 10,
            'stock' => 1,
            'is_active' => false,
        ]);

        $this->get(route('shop.products.show', $activeProduct->slug))
            ->assertOk()
            ->assertSee('A cotton carry bag')
            ->assertSee('In stock (3 available)');

        $this->get(route('shop.products.show', 'hidden-product'))
            ->assertNotFound();
    }

    public function test_products_are_paginated_and_search_filters_are_preserved()
    {
        for ($index = 1; $index <= 13; $index++) {
            Product::create([
                'name' => 'Item '.$index,
                'slug' => 'item-'.$index,
                'sku' => 'ITEM-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'description' => 'catalog',
                'price' => 10,
                'stock' => 1,
            ]);
        }

        $this->get(route('shop.index', ['search' => 'catalog']))
            ->assertOk()
            ->assertSee('Item 1')
            ->assertDontSee('Item 13')
            ->assertSee('search=catalog', false);
    }
}
