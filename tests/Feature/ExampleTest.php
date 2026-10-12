<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_page_is_accessible()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_shop_page_renders_inside_the_shared_layout()
    {
        $response = $this->get('/home/index');

        $response->assertStatus(200)
            ->assertSee('Example-Shop')
            ->assertSee('<div class="page">', false);
    }

    public function test_admin_shop_page_renders_the_admin_view()
    {
        $response = $this->get('/console/index');

        $response->assertStatus(200)
            ->assertSee('Example-Shop')
            ->assertSee('Welcome Bro');
    }
}
