<?php

namespace Tests\Feature\MarketPrices;

use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\MarketPriceRevision;
use App\Models\Role;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * F11 slice 4: contributors correct (logged) or withdraw (kept, hidden) their own entries within 7 days,
 * see them on the dashboard, and stale tabs are refused.
 */
class CorrectAndWithdrawPriceTest extends TestCase
{
    use MarketPriceHelpers, RefreshDatabase;

    private function ownPrice(array $attributes = []): array
    {
        $contributor = $this->contributor();
        $price = MarketPrice::factory()->for($contributor, 'contributor')->for($this->kawranBazar())->create($attributes + ['unit_price' => '350.00']);

        return [$contributor, $price];
    }

    private function correction(MarketPrice $price, array $overrides = []): array
    {
        return array_merge(['version' => $price->version, 'unit_price' => '35', 'note' => 'fixed', 'reason' => 'Typed 350 instead of 35.'], $overrides);
    }

    private function withdrawal(MarketPrice $price, array $overrides = []): array
    {
        return array_merge(['confirm_withdraw' => '1', 'version' => $price->version, 'withdrawal_reason' => 'Wrong market chosen.'], $overrides);
    }

    public function test_a_correction_keeps_the_old_value_in_the_history(): void
    {
        [$contributor, $price] = $this->ownPrice(['note' => 'morning']);

        $this->actingAs($contributor)->get(route('contributor.prices.edit', $price))->assertOk()->assertSee('Correct a price');
        $this->actingAs($contributor)->patch(route('contributor.prices.update', $price), $this->correction($price))
            ->assertRedirect(route('contributor.prices.index'))
            ->assertSessionHasNoErrors();

        $fresh = $price->fresh();
        $this->assertSame('35.00', $fresh->unit_price);
        $this->assertSame('fixed', $fresh->note);
        $this->assertSame(2, $fresh->version);
        $this->assertTrue($fresh->isCorrected());

        $revision = MarketPriceRevision::sole();
        $this->assertSame('350.00', $revision->old_unit_price);
        $this->assertSame('morning', $revision->old_note);
        $this->assertSame('Typed 350 instead of 35.', $revision->reason);
        $this->assertSame($contributor->id, $revision->revised_by);

        $this->actingAs($contributor)->get(route('contributor.prices.edit', $fresh))->assertSee('Earlier values')->assertSee('Was ৳ 350.00');
        $this->actingAs($contributor)->get(route('contributor.prices.index'))->assertSee('Corrected');
    }

    public function test_only_price_and_note_can_be_corrected(): void
    {
        [$contributor, $price] = $this->ownPrice();
        $otherMarket = Market::where('name', 'Mohasthan Hat')->sole();

        $this->actingAs($contributor)->patch(route('contributor.prices.update', $price), $this->correction($price, [
            'market_id' => $otherMarket->id, 'item_code' => 'onion', 'price_date' => $this->daysFromToday(-3),
            'price_session' => 'evening', 'contributor_id' => $this->contributor()->id,
        ]))->assertSessionHasNoErrors();

        $fresh = $price->fresh();
        $this->assertSame($this->kawranBazar()->id, $fresh->market_id);
        $this->assertSame('potato', $fresh->item_code);
        $this->assertSame(MarketPrice::today(), $fresh->price_date->toDateString());
        $this->assertSame('morning', $fresh->price_session);
        $this->assertSame($contributor->id, $fresh->contributor_id);
    }

    public function test_a_correction_needs_a_reason_a_valid_price_and_a_real_change(): void
    {
        [$contributor, $price] = $this->ownPrice(['note' => null]);

        $this->actingAs($contributor)->patch(route('contributor.prices.update', $price), $this->correction($price, ['reason' => '']))
            ->assertSessionHasErrors('reason');
        $this->actingAs($contributor)->patch(route('contributor.prices.update', $price), $this->correction($price, ['unit_price' => '0']))
            ->assertSessionHasErrors('unit_price');
        $this->actingAs($contributor)->patch(route('contributor.prices.update', $price), $this->correction($price, ['unit_price' => '৩৫০', 'note' => '']))
            ->assertSessionHasErrors('unit_price'); // Bangla digits for the same price and no note: nothing changed

        $this->assertSame(0, MarketPriceRevision::count());
        $this->assertSame(1, $price->fresh()->version);
    }

    public function test_a_correction_from_an_old_tab_is_refused_with_a_conflict_page(): void
    {
        [$contributor, $price] = $this->ownPrice();
        $tabA = $this->correction($price, ['unit_price' => '35']);
        $tabB = $this->correction($price, ['unit_price' => '36', 'reason' => 'Old tab reason']);

        $this->actingAs($contributor)->patch(route('contributor.prices.update', $price), $tabA)->assertSessionHasNoErrors();
        $this->actingAs($contributor)->patch(route('contributor.prices.update', $price), $tabB)
            ->assertStatus(409)
            ->assertSee('Your change was not saved.')
            ->assertSee('Old tab reason');

        $this->assertSame('35.00', $price->fresh()->unit_price);
        $this->assertSame(1, MarketPriceRevision::count());
    }

    public function test_withdrawing_hides_the_entry_everywhere_but_keeps_it_with_the_reason(): void
    {
        [$contributor, $price] = $this->ownPrice(['unit_price' => '35.00']);

        $this->actingAs($contributor)->get(route('contributor.prices.edit', [$price, 'confirm' => 'withdraw']))->assertSee('Withdraw this price?');
        $this->assertNotNull($price->fresh(), 'opening the confirmation changes nothing');

        $this->actingAs($contributor)->delete(route('contributor.prices.destroy', $price), $this->withdrawal($price))
            ->assertRedirect(route('contributor.prices.index'));

        $kept = MarketPrice::withTrashed()->findOrFail($price->id);
        $this->assertNotNull($kept->deleted_at);
        $this->assertSame('Wrong market chosen.', $kept->withdrawal_reason);
        $this->get(route('market-prices.index'))->assertSee('No market prices yet');
        $this->actingAs($contributor)->get(route('contributor.prices.index'))->assertSee('You have not recorded any prices');
        $this->actingAs($contributor)->get(route('contributor.prices.edit', $price))->assertNotFound();
    }

    public function test_withdrawing_frees_the_slot_to_record_the_right_price(): void
    {
        [$contributor, $price] = $this->ownPrice();
        $this->actingAs($contributor)->delete(route('contributor.prices.destroy', $price), $this->withdrawal($price));

        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['unit_price' => '36']))->assertSessionHasNoErrors();

        $this->assertSame('36.00', MarketPrice::sole()->unit_price);
        $this->assertSame(2, MarketPrice::withTrashed()->count());
    }

    public function test_withdrawing_needs_confirmation_a_reason_and_the_current_version(): void
    {
        [$contributor, $price] = $this->ownPrice();

        $this->actingAs($contributor)->delete(route('contributor.prices.destroy', $price), $this->withdrawal($price, ['confirm_withdraw' => null]))
            ->assertSessionHasErrors('confirm_withdraw');
        $this->actingAs($contributor)->delete(route('contributor.prices.destroy', $price), $this->withdrawal($price, ['withdrawal_reason' => '']))
            ->assertSessionHasErrors('withdrawal_reason');
        $this->actingAs($contributor)->delete(route('contributor.prices.destroy', $price), $this->withdrawal($price, ['version' => 9]))
            ->assertStatus(409);

        $this->assertNull($price->fresh()->deleted_at);
    }

    public function test_after_seven_days_an_entry_is_locked_history(): void
    {
        [$contributor, $price] = $this->ownPrice();
        $this->travel(MarketPrice::CORRECTION_DAYS)->days();
        $this->travel(1)->minutes();

        $this->actingAs($contributor)->get(route('contributor.prices.edit', $price))->assertForbidden()->assertSee('part of the price history');
        $this->actingAs($contributor)->patch(route('contributor.prices.update', $price), $this->correction($price))->assertForbidden();
        $this->actingAs($contributor)->delete(route('contributor.prices.destroy', $price), $this->withdrawal($price))->assertForbidden();
        $this->actingAs($contributor)->get(route('contributor.prices.index'))->assertSee('Locked (history)');

        $this->assertSame('350.00', $price->fresh()->unit_price);
    }

    public function test_another_contributor_gets_not_found_and_others_are_forbidden(): void
    {
        [, $price] = $this->ownPrice();
        $intruder = $this->contributor();

        $this->actingAs($intruder)->get(route('contributor.prices.edit', $price))->assertNotFound();
        $this->actingAs($intruder)->patch(route('contributor.prices.update', $price), $this->correction($price))->assertNotFound();
        $this->actingAs($intruder)->delete(route('contributor.prices.destroy', $price), $this->withdrawal($price))->assertNotFound();

        $buyer = $this->userWith(Role::BUYER);
        $this->actingAs($buyer)->get(route('contributor.prices.edit', $price))->assertForbidden();
        $this->actingAs($buyer)->delete(route('contributor.prices.destroy', $price), $this->withdrawal($price))->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->get(route('contributor.prices.edit', $price))->assertRedirect(route('login'));
        $this->delete(route('contributor.prices.destroy', $price), $this->withdrawal($price))->assertRedirect(route('login'));

        $this->assertSame('350.00', $price->fresh()->unit_price);
        $this->assertNull($price->fresh()->deleted_at);
    }

    public function test_the_dashboard_shows_a_contributors_latest_entries_with_edit_links(): void
    {
        [$contributor, $price] = $this->ownPrice(['item_code' => 'milk_cow', 'price_session' => 'all_day', 'unit_price' => '90.00']);
        MarketPrice::factory()->create(['note' => 'not mine', 'item_code' => 'beef', 'price_session' => 'all_day']);

        $this->actingAs($contributor)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cow milk')
            ->assertSee('90.00')
            ->assertSee(route('contributor.prices.edit', $price))
            ->assertSee(route('contributor.prices.create'))
            ->assertDontSee('Beef');

        $this->actingAs($this->userWith(Role::FARMER))->get(route('dashboard'))->assertOk()->assertDontSee(route('contributor.prices.create'));
    }

    public function test_the_database_keeps_withdrawal_and_reason_together_and_revisions_cannot_be_orphaned(): void
    {
        [$contributor, $price] = $this->ownPrice();
        $this->actingAs($contributor)->patch(route('contributor.prices.update', $price), $this->correction($price));

        try {
            DB::table('market_prices')->where('id', $price->id)->update(['deleted_at' => now()]); // no reason
            $this->fail('A withdrawal without a reason was accepted.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('check constraint', strtolower($e->getMessage()));
        }

        $this->expectException(QueryException::class); // a corrected price cannot be hard-deleted
        DB::table('market_prices')->where('id', $price->id)->delete();
    }
}
