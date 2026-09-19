<?php

namespace App\Livewire\Sales;

use App\Models\Item;
use App\Models\Sale;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class Manager extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $item_id = '';

    public string $date = '';

    /** How this sale was recorded: quantity, pieces (loose units), or amount. */
    public string $mode = 'quantity';

    public string $value = '';

    public string $buying_price_override = '';

    public string $selling_price_override = '';

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function render()
    {
        return view('livewire.sales.manager', [
            'sales' => Sale::query()->with('item')->orderByDesc('date')->orderByDesc('id')->paginate(15),
            'items' => Item::query()->orderBy('name')->get(),
            'selectedItem' => $this->item_id ? Item::find($this->item_id) : null,
        ]);
    }

    public function updatedItemId(): void
    {
        $item = $this->item_id ? Item::find($this->item_id) : null;

        if (! $item || ! $item->sellsByPieces()) {
            if ($this->mode === 'pieces') {
                $this->mode = 'quantity';
            }
        }
    }

    public function create(): void
    {
        $this->resetForm();
        $this->dispatch('open-modal', 'sale-form');
    }

    public function edit(int $id): void
    {
        $sale = Sale::findOrFail($id);

        $this->editingId = $sale->id;
        $this->item_id = (string) $sale->item_id;
        $this->date = $sale->date->format('Y-m-d');
        $this->buying_price_override = $sale->buying_price_override !== null
            ? (string) $sale->buying_price_override
            : '';
        $this->selling_price_override = (string) $sale->selling_price_per_unit;

        if ($sale->quantity_sold !== null) {
            $this->mode = 'quantity';
            $this->value = (string) $sale->quantity_sold;
        } elseif ($sale->pieces_sold !== null) {
            $this->mode = 'pieces';
            $this->value = (string) $sale->pieces_sold;
        } else {
            $this->mode = 'amount';
            $this->value = (string) $sale->amount_paid;
        }

        $this->dispatch('open-modal', 'sale-form');
    }

    public function save(): void
    {
        $validated = Validator::make(
            [
                'item_id' => $this->item_id,
                'date' => $this->date,
                'mode' => $this->mode,
                'value' => $this->value,
                'buying_price_override' => $this->buying_price_override,
                'selling_price_override' => $this->selling_price_override,
            ],
            [
                'item_id' => ['required', 'exists:items,id'],
                'date' => ['required', 'date'],
                'mode' => ['required', 'in:quantity,pieces,amount'],
                'value' => ['required', 'numeric', 'min:0.001'],
                'buying_price_override' => ['nullable', 'numeric', 'min:0'],
                'selling_price_override' => ['nullable', 'numeric', 'min:0'],
            ]
        )->validate();

        $item = Item::findOrFail($validated['item_id']);

        $attributes = Sale::computeAttributes(
            item: $item,
            date: $validated['date'],
            quantitySold: $validated['mode'] === 'quantity' ? (float) $validated['value'] : null,
            piecesSold: $validated['mode'] === 'pieces' ? (int) $validated['value'] : null,
            amountPaid: $validated['mode'] === 'amount' ? (float) $validated['value'] : null,
            buyingPriceOverride: $validated['buying_price_override'] !== '' ? (float) $validated['buying_price_override'] : null,
            sellingPriceOverride: $validated['selling_price_override'] !== '' ? (float) $validated['selling_price_override'] : null,
        );

        Sale::updateOrCreate(['id' => $this->editingId], $attributes);

        $this->resetForm();
        $this->dispatch('close-modal', 'sale-form');
    }

    public function delete(int $id): void
    {
        Sale::findOrFail($id)->delete();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'item_id', 'value', 'buying_price_override', 'selling_price_override']);
        $this->mode = 'quantity';
        $this->date = now()->format('Y-m-d');
        $this->resetErrorBag();
    }
}
