<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the shop's existing item catalogue, carried over from its
     * spreadsheet (ITEMS sheet), so the app starts with real data instead
     * of an empty list.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'Basmat', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 140, 'selling_price' => 180],
            ['name' => 'Sindano', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 150, 'selling_price' => 180],
            ['name' => 'Pishori', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 160, 'selling_price' => 200],
            ['name' => 'Biryani', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 95, 'selling_price' => 140],
            ['name' => 'Yellow beans', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 120, 'selling_price' => 160],
            ['name' => 'Njahi', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 80, 'selling_price' => 160],
            ['name' => 'Kamande', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 166, 'selling_price' => 220],
            ['name' => 'Rose coco', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 100, 'selling_price' => 160],
            ['name' => 'Wairimu', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 100, 'selling_price' => 160],
            ['name' => 'Makueni', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 160, 'selling_price' => 180],
            ['name' => 'Ndengu special', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 90, 'selling_price' => 140],
            ['name' => 'Maize', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 55, 'selling_price' => 100],
            ['name' => 'Popcorn', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 200, 'selling_price' => 260],
            ['name' => 'Njugu Karanga', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 250, 'selling_price' => 300],
            ['name' => 'Kunde Red', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 95, 'selling_price' => 160],
            ['name' => 'Nyayo', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 80, 'selling_price' => 160],
            ['name' => 'Mwitemania', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 100, 'selling_price' => 140],
            ['name' => 'Mbaazi', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 120, 'selling_price' => 160],
            ['name' => 'Army Beans', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 120, 'selling_price' => 180],
            ['name' => 'Eggs', 'category' => 'Eggs', 'unit' => 'Tray', 'buying_price' => 410, 'selling_price' => 450, 'eggs_per_tray' => 30],
            ['name' => 'Honey', 'category' => 'Honey', 'unit' => 'Kg', 'buying_price' => 400, 'selling_price' => 800],
            ['name' => 'Milk', 'category' => 'Milk', 'unit' => 'L', 'buying_price' => 60, 'selling_price' => 75],
            ['name' => 'Cooking Oil', 'category' => 'Oil', 'unit' => 'L', 'buying_price' => 245, 'selling_price' => 275],
            ['name' => 'Sugar', 'category' => 'Sugar', 'unit' => 'Kg', 'buying_price' => 136, 'selling_price' => 160],
            ['name' => 'Chachu', 'category' => 'Uji', 'unit' => 'Kg', 'buying_price' => 85, 'selling_price' => 130],
            ['name' => 'Wimbi', 'category' => 'Uji', 'unit' => 'Kg', 'buying_price' => 85, 'selling_price' => 130],
            ['name' => 'Mhogo', 'category' => 'Uji', 'unit' => 'Kg', 'buying_price' => 85, 'selling_price' => 130],
            ['name' => 'Mtama', 'category' => 'Uji', 'unit' => 'Kg', 'buying_price' => 85, 'selling_price' => 130],
            ['name' => 'Soya', 'category' => 'Uji', 'unit' => 'Kg', 'buying_price' => 130, 'selling_price' => 180],
            ['name' => 'Unga Ugali', 'category' => 'Unga', 'unit' => 'Kg', 'buying_price' => 70, 'selling_price' => 90],
        ];

        foreach ($items as $item) {
            Item::query()->updateOrCreate(['name' => $item['name']], $item);
        }
    }
}
