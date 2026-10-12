<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_loads_the_admin_layout_and_stylesheet()
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('css/app.css')
            ->assertSee('admin-login-page')
            ->assertSee('admin-login-layout')
            ->assertDontSee('cart-link')
            ->assertDontSee('Track order');
    }

    public function test_only_administrators_can_access_product_management()
    {
        $this->get(route('admin.products.index'))
            ->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_non_administrator_cannot_log_in_to_the_admin_area()
    {
        $user = User::factory()->create([
            'password' => Hash::make('secure-test-password'),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'secure-test-password',
            ])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_administrator_can_log_in_and_create_product_with_image()
    {
        Storage::fake('public');
        $admin = User::factory()->create([
            'password' => Hash::make('secure-test-password'),
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'secure-test-password',
        ])->assertRedirect(route('admin.products.index'));

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/tVQAAAAASUVORK5CYII=');
        $this->post(route('admin.products.store'), [
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'sku' => 'BAG-001',
            'price' => '250.00',
            'stock' => '10',
            'description' => 'A sturdy everyday bag.',
            'image' => UploadedFile::fake()->createWithContent('bag.png', $png),
            'is_active' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::where('sku', 'BAG-001')->firstOrFail();
        $this->assertSame('Canvas Bag', $product->name);
        $this->assertSame('250.00', $product->price);
        $this->assertTrue($product->is_active);
        Storage::disk('public')->assertExists($product->image_path);

        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Canvas Bag')
            ->assertSee('BAG-001');

        $this->put(route('admin.products.update', $product), [
            'name' => 'Canvas Bag Updated',
            'slug' => 'canvas-bag-updated',
            'sku' => 'BAG-001',
            'price' => '275.00',
            'stock' => '8',
        ])->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame('Canvas Bag Updated', $product->name);
        $this->assertSame('275.00', $product->price);

        $imagePath = $product->image_path;
        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_product_sku_must_be_unique()
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);

        Product::create([
            'name' => 'First product',
            'slug' => 'first-product',
            'sku' => 'UNIQUE-001',
            'price' => 10,
            'stock' => 1,
        ]);

        $this->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => 'Second product',
                'slug' => 'second-product',
                'sku' => 'UNIQUE-001',
                'price' => '20.00',
                'stock' => '2',
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('sku');

        $this->assertDatabaseCount('products', 1);
    }
}
