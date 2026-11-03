<?php

namespace App\Services;

use App\Models\Buyer;
use App\Models\NurserySale;
use App\Models\PlantInventory;
use App\Models\PlantSpecies;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NurserySaleService
{
    public function creationData(): array
    {
        $buyers = Buyer::orderBy('buyer_name')
            ->get();

        $buyers->each(function ($buyer): void {
            $buyer->contact_no = $this->formatContactNumber((string) $buyer->contact_no);
        });

        return [
            'buyers' => $buyers,
            'plants' => PlantSpecies::with('inventory')
                ->orderBy('plant_name')
                ->get(['id', 'plant_name', 'unit_price']),
        ];
    }

    public function editData(int|string $id): array
    {
        $sale = NurserySale::with('saleItems')
            ->findOrFail($id);

        if ($sale->status !== 'pending') {
            throw ValidationException::withMessages([
                'sale' => 'Only pending sales can be edited.',
            ]);
        }

        return [
            ...$this->creationData(),
            'sale' => $sale,
        ];
    }

    public function createSale(array $data): NurserySale
    {
        $data = $this->validateSaleData($data);

        return DB::transaction(function () use ($data) {
            $sale = NurserySale::create([
                'buyer_id' => $data['buyer_id'],
                'payment_method' => $data['payment_method'],
                'discount_rate' => $data['discount_rate'],
                'dispatch_date' => $data['dispatch_date'] ?? null,
                'status' => 'pending',
            ]);

            $subtotal = 0;

            foreach ($data['items'] as $item) {
                $plant = PlantSpecies::findOrFail(
                    $item['plant_species_id']
                );

                $quantity = (int) $item['quantity_bought'];
                $unitPrice = (float) $plant->unit_price;

                SaleItem::create([
                    'nursery_sale_id' => $sale->id,
                    'plant_species_id' => $plant->id,
                    'quantity_bought' => $quantity,
                    'unit_price' => $unitPrice,
                ]);

                $subtotal += $quantity * $unitPrice;
            }

            $discount = (float) $data['discount_rate'];

            $total = round(
                $subtotal * (1 - $discount),
                2
            );

            $sale->update([
                'total_amount' => $total,
            ]);

            return $sale->load(['buyer', 'saleItems.plantSpecies']);
        });
    }

    public function confirmSale(NurserySale $sale): NurserySale
    {
        return DB::transaction(function () use ($sale) {
            $sale = NurserySale::whereKey($sale->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sale->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending sales can be confirmed.',
                ]);
            }

            $items = $sale->saleItems()
                ->with('plantSpecies')
                ->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Cannot confirm a sale without items.',
                ]);
            }

            foreach ($items as $item) {
                $inventory = PlantInventory::where(
                    'plant_species_id',
                    $item->plant_species_id
                )
                    ->lockForUpdate()
                    ->first();

                if (! $inventory ||
                    $inventory->quantity_available < $item->quantity_bought) {
                    throw ValidationException::withMessages([
                        'inventory' => 'Insufficient stock for '.
                            $item->plantSpecies->plant_name.'.',
                    ]);
                }

                $inventory->decrement(
                    'quantity_available',
                    $item->quantity_bought
                );
            }

            $sale->update([
                'status' => 'confirmed',
            ]);

            return $sale->refresh()
                ->load(['buyer', 'saleItems.plantSpecies']);
        });
    }

    public function confirmSaleById(int|string $id): NurserySale
    {
        $sale = NurserySale::findOrFail($id);

        return $this->confirmSale($sale);
    }

    public function formatContactNumber(string $contact): string
    {
        $digits = preg_replace('/\D+/', '', $contact) ?? '';

        if (str_starts_with($digits, '63')) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return '+63'.substr($digits, 1);
        }

        return $digits;
    }

    public function updateSaleDetails(
        int|string $id,
        array $data
    ): NurserySale {
        $data = $this->validateSaleData($data, true);

        return DB::transaction(function () use ($id, $data) {
            $sale = NurserySale::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sale->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending sales can be updated.',
                ]);
            }

            $details = [];

            foreach ([
                'buyer_id',
                'payment_method',
                'discount_rate',
                'dispatch_date',
            ] as $field) {
                if (array_key_exists($field, $data)) {
                    $details[$field] = $data[$field];
                }
            }

            if (array_key_exists('items', $data)) {
                $sale->saleItems()->delete();

                $subtotal = 0;

                foreach ($data['items'] as $item) {
                    $plant = PlantSpecies::findOrFail(
                        $item['plant_species_id']
                    );

                    $quantity = (int) $item['quantity_bought'];
                    $unitPrice = (float) $plant->unit_price;

                    SaleItem::create([
                        'nursery_sale_id' => $sale->id,
                        'plant_species_id' => $plant->id,
                        'quantity_bought' => $quantity,
                        'unit_price' => $unitPrice,
                    ]);

                    $subtotal += $quantity * $unitPrice;
                }

                $discount = (float) (
                    $details['discount_rate']
                    ?? $sale->discount_rate
                );

                $details['total_amount'] = round(
                    $subtotal * (1 - $discount),
                    2
                );
            } elseif (array_key_exists('discount_rate', $details)) {
                $subtotal = $sale->saleItems->sum(
                    fn ($item) => $item->quantity_bought * $item->unit_price
                );

                $details['total_amount'] = round(
                    $subtotal * (1 - (float) $details['discount_rate']),
                    2
                );
            }

            $sale->update($details);

            return $sale->refresh()
                ->load(['buyer', 'saleItems.plantSpecies']);
        });
    }

    public function deleteSale(int|string $id): void
    {
        DB::transaction(function () use ($id): void {
            $sale = NurserySale::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sale->status !== 'pending') {
                throw ValidationException::withMessages([
                    'sale' => 'Only pending sales can be deleted.',
                ]);
            }

            $sale->delete();
        });
    }

    private function validateSaleData(array $data, bool $updating = false): array
    {
        $allowedFields = [
            'buyer_id',
            'payment_method',
            'discount_rate',
            'dispatch_date',
            'items',
        ];

        $data = array_intersect_key($data, array_flip($allowedFields));

        foreach (['payment_method', 'dispatch_date'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = strip_tags(trim($data[$field]));
            }
        }

        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = array_map(
                static fn ($item) => is_array($item)
                    ? array_intersect_key(
                        $item,
                        array_flip(['plant_species_id', 'quantity_bought'])
                    )
                    : $item,
                $data['items']
            );
        }

        $rules = [
            'payment_method' => [
                ...($updating ? ['sometimes', 'required'] : ['required']),
                Rule::in(['Cash', 'GCash', 'Bank Transfer']),
            ],
            'discount_rate' => [
                ...($updating ? ['sometimes', 'required'] : ['required']),
                'numeric',
                'between:0,1',
            ],
            'dispatch_date' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'items' => [
                ...($updating ? ['sometimes', 'required'] : ['required']),
                'array',
                'min:1',
            ],
            'items.*' => ['array:plant_species_id,quantity_bought'],
            'items.*.plant_species_id' => [
                'required',
                'integer',
                'distinct',
                'exists:plant_species,id',
            ],
            'items.*.quantity_bought' => [
                'required',
                'integer',
                'min:1',
                'max:100000',
            ],
        ];

        if (! $updating) {
            $rules['buyer_id'] = ['required', 'integer', 'exists:buyers,id'];
        } else {
            $rules['buyer_id'] = ['sometimes', 'required', 'integer', 'exists:buyers,id'];
        }

        return Validator::make($data, $rules)->validate();
    }
}
