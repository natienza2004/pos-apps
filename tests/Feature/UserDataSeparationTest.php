<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDataSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_only_shows_products_for_the_signed_in_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Product::query()->create([
            'user_id' => $user->id,
            'sku' => 'ITM-001',
            'name' => 'Private Flour',
            'category' => 'Bakery',
            'unit' => 'kg',
            'price' => 40,
            'starting_stock' => 2,
            'current_stock' => 2,
            'low_stock_threshold' => 5,
            'include_in_costing' => true,
        ]);

        Product::query()->create([
            'user_id' => $otherUser->id,
            'sku' => 'ITM-001',
            'name' => 'Other Account Sugar',
            'category' => 'Bakery',
            'unit' => 'kg',
            'price' => 30,
            'starting_stock' => 1,
            'current_stock' => 1,
            'low_stock_threshold' => 5,
            'include_in_costing' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Private Flour')
            ->assertDontSee('Other Account Sugar');
    }
}
