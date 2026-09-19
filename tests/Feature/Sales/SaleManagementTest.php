<?php

namespace Tests\Feature\Sales;

use App\Livewire\Sales\Manager;
use App\Models\Item;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SaleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_sales_page(): void
    {
        $this->get('/sales')->assertRedirect('/login');
    }

    public function test_it_can_record_a_sale_by_quantity(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 160, 'selling_price' => 180]);

        Livewire::test(Manager::class)
            ->set('item_id', (string) $item->id)
            ->set('date', '2026-08-19')
            ->set('mode', 'quantity')
            ->set('value', '0.5')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sales', [
            'item_id' => $item->id,
            'revenue' => 90,
            'cogs' => 80,
            'profit' => 10,
        ]);
    }

    public function test_it_can_record_a_sale_by_amount_paid(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 100, 'selling_price' => 160]);

        Livewire::test(Manager::class)
            ->set('item_id', (string) $item->id)
            ->set('date', '2026-08-19')
            ->set('mode', 'amount')
            ->set('value', '80')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sales', [
            'item_id' => $item->id,
            'amount_paid' => 80,
            'revenue' => 80,
            'profit' => 30,
        ]);
    }

    public function test_it_can_record_a_sale_by_pieces_for_an_item_sold_that_way(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create([
            'buying_price' => 410,
            'selling_price' => 450,
            'eggs_per_tray' => 30,
        ]);

        Livewire::test(Manager::class)
            ->set('item_id', (string) $item->id)
            ->set('date', '2026-08-19')
            ->set('mode', 'pieces')
            ->set('value', '15')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sales', [
            'item_id' => $item->id,
            'pieces_sold' => 15,
            'quantity' => 0.5,
            'profit' => 20,
        ]);
    }

    public function test_pieces_mode_resets_when_switching_to_an_item_without_eggs_per_tray(): void
    {
        $this->actingAs(User::factory()->create());
        $eggs = Item::factory()->create(['eggs_per_tray' => 30]);
        $maize = Item::factory()->create(['eggs_per_tray' => null]);

        Livewire::test(Manager::class)
            ->set('item_id', (string) $eggs->id)
            ->set('mode', 'pieces')
            ->set('item_id', (string) $maize->id)
            ->assertSet('mode', 'quantity');
    }

    public function test_it_requires_exactly_one_valid_value(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Manager::class)
            ->set('item_id', '')
            ->set('value', '')
            ->call('save')
            ->assertHasErrors(['item_id', 'value']);
    }

    public function test_it_can_update_an_existing_sale(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 100, 'selling_price' => 200]);
        $sale = Sale::create(\App\Models\Sale::computeAttributes($item, '2026-08-01', 1, null, null, null));

        Livewire::test(Manager::class)
            ->call('edit', $sale->id)
            ->set('value', '2')
            ->call('save')
            ->assertHasNoErrors();

        $sale->refresh();
        $this->assertEquals(2, $sale->quantity);
        $this->assertEquals(400, $sale->revenue);
    }

    public function test_it_can_delete_a_sale(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create();
        $sale = Sale::create(Sale::computeAttributes($item, '2026-08-01', 1, null, null, null));

        Livewire::test(Manager::class)->call('delete', $sale->id);

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }

    public function test_deleting_an_item_with_sales_history_is_blocked(): void
    {
        $item = Item::factory()->create();
        Sale::create(Sale::computeAttributes($item, '2026-08-01', 1, null, null, null));

        $this->expectException(\Illuminate\Database\QueryException::class);

        $item->delete();
    }
}
