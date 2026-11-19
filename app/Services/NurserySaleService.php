<?php

namespace App\Services;

use App\Models\NurserySale;
use App\Models\PlantInventory;
use App\Models\PlantSpecies;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NurserySaleService
{
    public function creationData(): array
    {
        $buyers = User::orderBy('name')
            ->get(['id', 'name', 'email', 'contact_no']);

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
        $data = $this->validateSaleData($this->sanitizeSaleData($data));

        return DB::transaction(function () use ($data) {
            $userId = isset($data['new_buyer'])
                ? $this->createBuyer($data['new_buyer'])->id
                : $data['user_id'];
            $buyer = User::findOrFail($userId);

            $sale = NurserySale::create([
                'user_id' => $userId,
                'buyer_name' => $buyer->name,
                'buyer_contact_no' => $buyer->contact_no,
                'buyer_address' => $buyer->address,
                'payment_method' => $data['payment_method'],
                'discount_rate' => $data['discount_rate'],
                'dispatch_date' => $data['dispatch_date'] ?? null,
                'status' => 'pending',
            ]);

            $subtotal = 0;

            foreach ($data['items'] as $item) {
                $plant = PlantSpecies::findOrFail($item['plant_species_id']);

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

            $items = $sale->saleItems;

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

                if (
                    ! $inventory ||
                    $inventory->quantity_available < $item->quantity_bought
                ) {
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

    public function cancelSaleById(int|string $id): NurserySale
    {
        return DB::transaction(function () use ($id): NurserySale {
            $sale = NurserySale::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($sale->status, ['pending', 'confirmed'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending or confirmed orders can be cancelled.',
                ]);
            }

            if ($sale->status === 'confirmed') {
                foreach ($sale->saleItems as $item) {
                    $inventory = PlantInventory::where('plant_species_id', $item->plant_species_id)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $inventory->increment('quantity_available', $item->quantity_bought);
                }
            }

            $sale->update(['status' => 'cancelled']);

            return $sale->refresh()->load(['buyer', 'saleItems.plantSpecies']);
        });
    }

    public function cancelPendingSaleForBuyer(int|string $id, User $buyer): NurserySale
    {
        return DB::transaction(function () use ($id, $buyer): NurserySale {
            $sale = NurserySale::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $sale->user_id !== $buyer->id || $sale->status !== 'pending') {
                throw ValidationException::withMessages([
                    'sale' => 'Only your pending orders can be cancelled.',
                ]);
            }

            $sale->update(['status' => 'cancelled']);

            return $sale->refresh()->load(['buyer', 'saleItems.plantSpecies']);
        });
    }

    public function completeSaleById(int|string $id): NurserySale
    {
        return DB::transaction(function () use ($id): NurserySale {
            $sale = NurserySale::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sale->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'status' => 'Only confirmed orders can be completed.',
                ]);
            }

            $sale->update(['status' => 'completed']);

            return $sale->refresh()->load(['buyer', 'saleItems.plantSpecies']);
        });
    }

    public function formatContactNumber(string $contact): string
    {
        $contact = $this->sanitizeText($contact);
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
        $data = $this->validateSaleData(
            $this->sanitizeSaleData($data),
            updating: true
        );

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

            if (isset($data['new_buyer'])) {
                $details['user_id'] = $this->createBuyer($data['new_buyer'])->id;
            } elseif (array_key_exists('user_id', $data)) {
                $details['user_id'] = $data['user_id'];
            }

            foreach (
                [
                    'payment_method',
                    'discount_rate',
                    'dispatch_date',
                ] as $field
            ) {
                if (array_key_exists($field, $data)) {
                    $details[$field] = $data[$field];
                }
            }

            if (array_key_exists('items', $data)) {
                $sale->saleItems()->delete();

                $subtotal = 0;

                foreach ($data['items'] as $item) {
                    $plant = PlantSpecies::findOrFail($item['plant_species_id']);

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

    public function deleteCompletedSaleForBuyer(int|string $id, User $buyer): void
    {
        DB::transaction(function () use ($id, $buyer): void {
            $sale = NurserySale::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $sale->user_id !== $buyer->id || $sale->status !== 'completed') {
                throw ValidationException::withMessages([
                    'sale' => 'Only your completed orders can be deleted.',
                ]);
            }

            $sale->delete();
        });
    }

    private function sanitizeSaleData(array $data): array
    {
        $allowedFields = [
            'user_id',
            'new_buyer',
            'payment_method',
            'discount_rate',
            'dispatch_date',
            'items',
        ];

        $data = array_intersect_key($data, array_flip($allowedFields));

        if (isset($data['new_buyer']) && is_array($data['new_buyer'])) {
            $data['new_buyer'] = array_intersect_key(
                $data['new_buyer'],
                array_flip(['name', 'email', 'contact_no', 'password'])
            );

            foreach (['name', 'email', 'contact_no'] as $field) {
                if (isset($data['new_buyer'][$field]) && is_string($data['new_buyer'][$field])) {
                    $data['new_buyer'][$field] = $this->sanitizeText($data['new_buyer'][$field]);
                }
            }

            if (is_string($data['new_buyer']['name'] ?? null)) {
                $data['new_buyer']['name'] = mb_convert_case(
                    mb_strtolower($data['new_buyer']['name']),
                    MB_CASE_TITLE,
                    'UTF-8'
                );
            }

            if (is_string($data['new_buyer']['contact_no'] ?? null)) {
                $data['new_buyer']['contact_no'] = $this->formatContactNumber(
                    $data['new_buyer']['contact_no']
                );
            }
        }

        foreach (['payment_method', 'dispatch_date'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = $this->sanitizeText($data[$field]);
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

        return $data;
    }

    private function validateSaleData(
        array $data,
        bool $updating = false
    ): array {
        return Validator::make($data, [
            'user_id' => [
                ...($updating ? ['sometimes'] : ['required_without:new_buyer']),
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'new_buyer' => [
                ...($updating ? ['sometimes'] : ['required_without:user_id']),
                'array:name,email,contact_no,password',
            ],
            'new_buyer.name' => [
                'required_with:new_buyer',
                'string',
                'max:150',
            ],
            'new_buyer.email' => [
                'required_with:new_buyer',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'new_buyer.contact_no' => [
                'required_with:new_buyer',
                'string',
                'regex:/^\+63[0-9]{10}$/',
            ],
            'new_buyer.password' => [
                'required_with:new_buyer',
                'string',
                'min:8',
            ],
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
                ...($updating ? ['sometimes'] : []),
                'nullable',
                'date',
                'after_or_equal:today',
                'before_or_equal:2027-12-31',
            ],
            'items' => [
                ...($updating ? ['sometimes', 'required'] : ['required']),
                'array',
                'min:1',
            ],
            'items.*' => [
                'required_with:items',
                'array:plant_species_id,quantity_bought',
            ],
            'items.*.plant_species_id' => [
                'required_with:items',
                'integer',
                'distinct',
                'exists:plant_species,id',
            ],
            'items.*.quantity_bought' => [
                'required_with:items',
                'integer',
                'min:1',
                'max:100000',
            ],
        ])->validate();
    }

    private function createBuyer(array $data): User
    {
        return User::create($data);
    }

    private function sanitizeText(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim($value ?? '');
    }
}
