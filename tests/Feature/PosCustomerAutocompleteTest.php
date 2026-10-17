<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCustomerAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    protected function createStore(): Store
    {
        return Store::create([
            'name' => 'Gulshan Store',
            'code' => 'GLS-01',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_search_pos_customers()
    {
        $response = $this->getJson(route('pos.customers.search', ['q' => '013']));
        $response->assertStatus(401);
    }

    public function test_cashier_can_search_customers_by_partial_phone()
    {
        $store = $this->createStore();
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'store_id' => $store->id,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'name' => 'MD Sajol Hossen',
            'phone' => '01310997902',
            'email' => 'sajol@test.com',
            'loyalty_points' => 33,
            'total_spend' => 3360.00,
        ]);

        $response = $this->actingAs($cashier)
            ->getJson(route('pos.customers.search', ['q' => '01310']));

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $customer->id,
                'name' => 'MD Sajol Hossen',
                'phone' => '01310997902',
                'loyalty_points' => 33,
            ]);
    }

    public function test_cashier_can_search_customers_by_name()
    {
        $store = $this->createStore();
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'store_id' => $store->id,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'name' => 'MD Sajol Hossen',
            'phone' => '01310997902',
            'loyalty_points' => 33,
        ]);

        $response = $this->actingAs($cashier)
            ->getJson(route('pos.customers.search', ['q' => 'Sajol']));

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $customer->id,
                'name' => 'MD Sajol Hossen',
            ]);
    }

    public function test_search_with_less_than_two_characters_returns_empty_array()
    {
        $store = $this->createStore();
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'store_id' => $store->id,
            'is_active' => true,
        ]);

        Customer::create([
            'name' => 'MD Sajol Hossen',
            'phone' => '01310997902',
        ]);

        $response = $this->actingAs($cashier)
            ->getJson(route('pos.customers.search', ['q' => '0']));

        $response->assertOk()
            ->assertExactJson([]);
    }
}
