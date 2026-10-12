<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ShopSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBankTransferSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_manage_bank_transfer_settings()
    {
        $this->get(route('admin.settings.bank-transfer.edit'))
            ->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('admin.settings.bank-transfer.edit'))
            ->assertForbidden();
    }

    public function test_admin_can_save_transfer_details_and_customer_sees_them_on_unpaid_order()
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);

        $this->put(route('admin.settings.bank-transfer.update'), [
            'bank_name' => 'Example Bank',
            'account_name' => 'Example Shop Co.',
            'account_number' => '123-4-56789-0',
            'instructions' => 'Use the order number as the transfer note.',
        ])->assertRedirect(route('admin.settings.bank-transfer.edit'));

        $this->assertDatabaseHas('shop_settings', [
            'key' => 'bank_name',
            'value' => 'Example Bank',
        ]);

        $this->get(route('admin.settings.bank-transfer.edit'))
            ->assertOk()
            ->assertSee('123-4-56789-0');

        $order = $this->createUnpaidOrder();
        $this->get(route('checkout.confirmation', $order->order_number))
            ->assertOk()
            ->assertSee('Example Bank')
            ->assertSee('Example Shop Co.')
            ->assertSee('123-4-56789-0')
            ->assertSee('Use the order number as the transfer note.');
    }

    public function test_account_number_validation_prevents_unsafe_values()
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->from(route('admin.settings.bank-transfer.edit'))
            ->put(route('admin.settings.bank-transfer.update'), [
                'bank_name' => 'Example Bank',
                'account_name' => 'Example Shop Co.',
                'account_number' => '<script>alert(1)</script>',
            ])
            ->assertRedirect(route('admin.settings.bank-transfer.edit'))
            ->assertSessionHasErrors('account_number');

        $this->assertDatabaseCount('shop_settings', 0);
    }

    public function test_unconfigured_account_is_not_presented_as_transfer_instructions()
    {
        $order = $this->createUnpaidOrder();

        $this->get(route('checkout.confirmation', $order->order_number))
            ->assertOk()
            ->assertSee('Bank transfer details are not available yet.')
            ->assertDontSee('Account number');
    }

    private function createUnpaidOrder(): Order
    {
        $order = Order::create([
            'order_number' => 'ES-'.strtoupper(bin2hex(random_bytes(10))),
            'status' => Order::STATUS_PENDING_PAYMENT,
            'customer_name' => 'Example Customer',
            'customer_email' => 'customer@example.test',
            'customer_phone' => '0812345678',
            'shipping_address' => '123 Example Road',
            'shipping_subdistrict' => 'Example Subdistrict',
            'shipping_district' => 'Example District',
            'shipping_province' => 'Bangkok',
            'shipping_postal_code' => '10100',
            'subtotal' => 100,
            'shipping_fee' => 0,
            'total' => 100,
        ]);

        $order->payment()->create([
            'amount' => 100,
            'status' => Payment::STATUS_AWAITING_TRANSFER,
        ]);

        return $order;
    }
}
