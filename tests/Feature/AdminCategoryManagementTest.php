<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_access_category_management()
    {
        $this->get(route('admin.categories.index'))
            ->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_edit_and_deactivate_category_without_losing_product_relation()
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);

        $this->post(route('admin.categories.store'), [
            'name' => 'Home Decor',
            'slug' => 'home-decor',
            'description' => 'Decorations for the home.',
            'is_active' => '1',
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::where('slug', 'home-decor')->firstOrFail();
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Ceramic Vase',
            'slug' => 'ceramic-vase',
            'sku' => 'VASE-001',
            'price' => 35,
            'stock' => 2,
        ]);

        $this->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Home Decor')
            ->assertSee('1');

        $this->put(route('admin.categories.update', $category), [
            'name' => 'Interior Decor',
            'slug' => 'interior-decor',
            'description' => 'Updated category description.',
            'is_active' => '1',
        ])->assertRedirect(route('admin.categories.index'));

        $category->refresh();
        $this->assertSame('Interior Decor', $category->name);
        $this->assertSame('interior-decor', $category->slug);

        $this->patch(route('admin.categories.toggle', $category))
            ->assertRedirect(route('admin.categories.index'));

        $category->refresh();
        $this->assertFalse($category->is_active);
        $this->assertTrue($product->fresh()->category->is($category));

        $this->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Interior Decor');

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertDontSee('interior-decor');
    }

    public function test_category_slug_must_be_unique_and_use_supported_characters()
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);

        Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories',
        ]);

        $this->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), [
                'name' => 'Other',
                'slug' => 'accessories',
            ])
            ->assertRedirect(route('admin.categories.create'))
            ->assertSessionHasErrors('slug');

        $this->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), [
                'name' => 'Not valid',
                'slug' => 'Not Valid!',
            ])
            ->assertRedirect(route('admin.categories.create'))
            ->assertSessionHasErrors('slug');

        $this->assertDatabaseCount('categories', 1);
    }
}
