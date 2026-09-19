<?php

namespace Tests\Feature\StockIn;

use App\Livewire\StockIn\Manager;
use App\Models\Item;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockInManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_stock_in_page(): void
    {
        $this->get('/stock-in')->assertRedirect('/login');
    }

    public function test_it_auto_fills_the_buying_price_from_the_selected_item(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 140]);

        Livewire::test(Manager::class)
            ->set('item_id', (string) $item->id)
            ->assertSet('buying_price_per_unit', '140.00');
    }

    public function test_it_can_record_a_delivery_and_computes_total_cost(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create();

        Livewire::test(Manager::class)
            ->set('item_id', (string) $item->id)
            ->set('date', '2026-08-17')
            ->set('quantity', '10')
            ->set('buying_price_per_unit', '140')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('stock_ins', [
            'item_id' => $item->id,
            'quantity' => 10,
            'buying_price_per_unit' => 140,
            'total_cost' => 1400,
        ]);
    }

    public function test_it_allows_overriding_the_buying_price_for_one_delivery(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 140]);

        Livewire::test(Manager::class)
            ->set('item_id', (string) $item->id)
            ->set('date', '2026-08-17')
            ->set('quantity', '10')
            ->set('buying_price_per_unit', '150')
            ->call('save');

        $this->assertDatabaseHas('stock_ins', [
            'item_id' => $item->id,
            'buying_price_per_unit' => 150,
            'total_cost' => 1500,
        ]);
    }

    public function test_it_requires_an_item_date_and_quantity(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Manager::class)
            ->set('item_id', '')
            ->set('date', '')
            ->set('quantity', '')
            ->set('buying_price_per_unit', '')
            ->call('save')
            ->assertHasErrors(['item_id', 'date', 'quantity', 'buying_price_per_unit']);
    }

    public function test_it_can_update_an_existing_delivery(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = StockIn::factory()->create(['quantity' => 5, 'buying_price_per_unit' => 100]);

        Livewire::test(Manager::class)
            ->call('edit', $entry->id)
            ->set('quantity', '8')
            ->call('save')
            ->assertHasNoErrors();

        $entry->refresh();
        $this->assertEquals(8, $entry->quantity);
        $this->assertEquals(800, $entry->total_cost);
    }

    public function test_it_can_delete_a_delivery(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = StockIn::factory()->create();

        Livewire::test(Manager::class)->call('delete', $entry->id);

        $this->assertDatabaseMissing('stock_ins', ['id' => $entry->id]);
    }

    public function test_deleting_an_item_with_stock_in_history_is_blocked(): void
    {
        $item = Item::factory()->create();
        StockIn::factory()->create(['item_id' => $item->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $item->delete();
    }
}
