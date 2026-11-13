<?php

namespace Tests\Feature\AgriculturalProducts;

use App\Models\AgriculturalProduct;
use App\Models\Role;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F25: who may use the seller workspace. Every request is sent directly, as an attacker would,
 * instead of relying on buttons being hidden.
 */
class SellerProductAccessTest extends TestCase
{
    use AgriculturalProductHelpers, RefreshDatabase;

    public function test_guests_are_sent_to_log_in_from_every_seller_page(): void
    {
        $product = $this->productFor($this->seller());

        foreach ([
            $this->get(route('seller.products.index')),
            $this->get(route('seller.products.create')),
            $this->get(route('seller.products.show', $product)),
            $this->get(route('seller.products.edit', $product)),
            $this->post(route('seller.products.store'), $this->validForm()),
            $this->patch(route('seller.products.update', $product), ['operation' => 'unpublish', 'version' => 1]),
            $this->delete(route('seller.products.destroy', $product), ['confirm_remove' => '1', 'version' => 1]),
        ] as $response) {
            $response->assertRedirect(route('login'));
        }

        $this->assertTrue($product->fresh()->isPublished());
        $this->assertSame(1, AgriculturalProduct::count());
    }

    public function test_signed_in_users_without_the_seller_role_are_forbidden(): void
    {
        $product = $this->productFor($this->seller());

        // An administrator gets no automatic bypass: administration has its own routes.
        foreach ([Role::BUYER, Role::FARMER, Role::ADMIN] as $role) {
            $user = $this->userWith($role);

            $this->actingAs($user)->get(route('seller.products.index'))->assertForbidden();
            $this->actingAs($user)->get(route('seller.products.create'))->assertForbidden();
            $this->actingAs($user)->post(route('seller.products.store'), $this->validForm())->assertForbidden();
            $this->actingAs($user)->get(route('seller.products.show', $product))->assertForbidden();
            $this->actingAs($user)->get(route('seller.products.edit', $product))->assertForbidden();
            $this->actingAs($user)->patch(route('seller.products.update', $product), $this->editForm($product, ['name' => 'Taken over']))->assertForbidden();
            $this->actingAs($user)->delete(route('seller.products.destroy', $product), ['confirm_remove' => '1', 'version' => 1])->assertForbidden();
        }

        $fresh = $product->fresh();
        $this->assertSame(1, AgriculturalProduct::count());
        $this->assertSame($product->name, $fresh->name);
        $this->assertSame(1, $fresh->version);
    }

    public function test_another_seller_gets_not_found_for_every_owner_action(): void
    {
        $product = AgriculturalProduct::factory()->for($this->seller(), 'seller')->withBulk()->create();
        $intruder = $this->seller();

        $this->actingAs($intruder)->get(route('seller.products.show', $product))->assertNotFound();
        $this->actingAs($intruder)->get(route('seller.products.edit', $product))->assertNotFound();
        $this->actingAs($intruder)->patch(route('seller.products.update', $product), $this->editForm($product, ['unit_price' => '1']))->assertNotFound();
        $this->actingAs($intruder)->patch(route('seller.products.update', $product), ['operation' => 'unpublish', 'version' => 1])->assertNotFound();
        $this->actingAs($intruder)->delete(route('seller.products.destroy', $product), ['confirm_remove' => '1', 'version' => 1])->assertNotFound();

        $fresh = $product->fresh();
        $this->assertSame('1350.00', $fresh->unit_price);
        $this->assertTrue($fresh->isPublished());
        $this->assertSame(1, $fresh->version);
    }

    public function test_the_product_list_shows_only_the_sellers_own_products(): void
    {
        $seller = $this->seller();
        AgriculturalProduct::factory()->for($seller, 'seller')->create(['name' => 'My urea']);
        AgriculturalProduct::factory()->for($seller, 'seller')->unpublished()->create(['name' => 'My hidden potash']);
        AgriculturalProduct::factory()->create(['name' => 'Someone else’s seed']);

        $this->actingAs($seller)->get(route('seller.products.index'))
            ->assertOk()
            ->assertSee('My urea')
            ->assertSee('My hidden potash')
            ->assertDontSee('Someone else’s seed');
    }

    public function test_a_user_with_several_roles_including_seller_can_use_the_workspace(): void
    {
        $user = $this->userWith(Role::FARMER, Role::SELLER);

        $this->actingAs($user)->get(route('seller.products.create'))->assertOk();
        $this->actingAs($user)->post(route('seller.products.store'), $this->validForm())->assertSessionHasNoErrors();

        $this->assertSame($user->id, AgriculturalProduct::sole()->seller_id);
    }

    public function test_forged_writes_are_rejected_when_csrf_protection_is_active(): void
    {
        // Laravel switches CSRF checks off in tests; this version keeps them on, like a real browser request.
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $seller = $this->seller();
        $product = $this->productFor($seller);

        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm())->assertStatus(419);
        $this->actingAs($seller)->patch(route('seller.products.update', $product), $this->editForm($product, ['stock_quantity' => '0']))->assertStatus(419);
        $this->actingAs($seller)->delete(route('seller.products.destroy', $product), ['confirm_remove' => '1', 'version' => 1])->assertStatus(419);

        $this->assertSame(1, AgriculturalProduct::count());
        $this->assertSame('40.000', $product->fresh()->stock_quantity);
    }

    public function test_adding_products_is_rate_limited_per_account(): void
    {
        $seller = $this->seller();

        // Invalid (empty) forms still count: the limit applies before any validation.
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($seller)->post(route('seller.products.store'), [])->assertRedirect();
        }
        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm())->assertStatus(429);

        $this->assertSame(0, AgriculturalProduct::count());

        // Another seller has their own limit.
        $this->actingAs($this->seller())->post(route('seller.products.store'), $this->validForm())->assertSessionHasNoErrors();
    }

    public function test_changes_are_rate_limited_per_account(): void
    {
        $seller = $this->seller();
        $product = $this->productFor($seller);

        for ($i = 0; $i < 30; $i++) {
            $this->actingAs($seller)->patch(route('seller.products.update', $product), ['operation' => 'nothing'])->assertRedirect();
        }
        $this->actingAs($seller)->patch(route('seller.products.update', $product), ['operation' => 'unpublish', 'version' => 1])->assertStatus(429);

        $this->assertTrue($product->fresh()->isPublished());
    }

    public function test_seller_text_is_shown_as_text_never_as_html(): void
    {
        $seller = $this->seller();
        $product = AgriculturalProduct::factory()->for($seller, 'seller')->create([
            'name' => '<script>alert(1)</script>Urea',
            'brand' => '"><svg onload=alert(3)>',
            'description' => '<img src=x onerror=alert(2)>',
        ]);

        foreach ([
            $this->actingAs($seller)->get(route('seller.products.index')),
            $this->actingAs($seller)->get(route('seller.products.show', $product)),
            $this->actingAs($seller)->get(route('seller.products.edit', $product)),
        ] as $response) {
            $response->assertOk()
                ->assertDontSee('<script>alert(1)</script>', false)
                ->assertDontSee('<img src=x onerror', false)
                ->assertDontSee('<svg onload', false)
                ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;Urea', false);
        }
    }

    public function test_the_private_bulk_price_never_leaves_through_serialisation(): void
    {
        $product = AgriculturalProduct::factory()->withBulk('10.000', '1234.56')->create();

        $this->assertArrayNotHasKey('bulk_min_unit_price', $product->toArray());
        $this->assertStringNotContainsString('1234.56', $product->toJson());
        $this->assertArrayNotHasKey('submission_token', $product->toArray());
    }
}
