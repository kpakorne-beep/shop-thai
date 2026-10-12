<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_adds_a_small_catalog_without_creating_orders_or_users()
    {
        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class]);
        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class]);

        $this->assertDatabaseCount('categories', 2);
        $this->assertDatabaseCount('products', 4);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertSame(2, Category::where('is_active', true)->count());
        $this->assertSame(4, Product::where('is_active', true)->where('stock', '>', 0)->count());
    }
}
