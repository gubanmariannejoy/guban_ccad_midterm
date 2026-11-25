<?php

namespace App\Services;

use App\Models\PlantSpecies;
use App\Models\User;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuyerShopService
{
    public function __construct(
        private readonly NurserySaleService $sales
    ) {}

    public function catalog(string $search): array
    {
        $search = trim($search);

        $plants = PlantSpecies::with('inventory')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('plant_name', 'like', "%{$search}%")
                        ->orWhere('botanical_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('plant_name')
            ->paginate(12)
            ->withQueryString();

        return [
            'plants' => $plants,
            'search' => $search,
        ];
    }

    public function cartPageData(array $cart, User $buyer): array
    {
        $plants = PlantSpecies::with('inventory')
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        $items = collect($cart)->map(function ($quantity, $plantId) use ($plants): ?array {
            $plant = $plants->get($plantId);

            if (! $plant) {
                return null;
            }

            return [
                'id' => $plant->id,
                'plant_name' => $plant->plant_name,
                'botanical_name' => $plant->botanical_name,
                'pot_size' => $plant->pot_size,
                'unit_price' => $plant->unit_price,
                'quantity_available' => $plant->inventory?->quantity_available ?? 0,
                'quantity' => (int) $quantity,
            ];
        })->filter()->values();

        return [
            'items' => $items,
            'buyer' => $buyer->only(['name', 'contact_no', 'address']),
        ];
    }

    public function addToCart(Store $session, PlantSpecies $plant, int $quantityToAdd): void
    {
        $available = (int) ($plant->inventory?->quantity_available ?? 0);

        if ($available < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'This plant is currently out of stock.',
            ]);
        }

        $cart = $session->get('buyer_cart', []);
        $quantity = (int) ($cart[$plant->id] ?? 0) + $quantityToAdd;

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => 'The requested quantity exceeds the available stock.',
            ]);
        }

        $cart[$plant->id] = $quantity;
        $session->put('buyer_cart', $cart);
    }

    public function updateCart(Store $session, PlantSpecies $plant, int $quantity): void
    {
        $available = (int) ($plant->inventory?->quantity_available ?? 0);

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => 'The requested quantity exceeds the available stock.',
            ]);
        }

        $cart = $session->get('buyer_cart', []);
        $cart[$plant->id] = $quantity;
        $session->put('buyer_cart', $cart);
    }

    public function removeFromCart(Store $session, PlantSpecies $plant): void
    {
        $cart = $session->get('buyer_cart', []);
        unset($cart[$plant->id]);
        $session->put('buyer_cart', $cart);
    }

    public function checkout(Store $session, User $buyer, array $validated): void
    {
        $cart = $session->get('buyer_cart', []);

        if ($cart === []) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        $plants = PlantSpecies::with('inventory')
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        foreach ($cart as $plantId => $quantity) {
            $plant = $plants->get($plantId);

            if (! $plant || (int) ($plant->inventory?->quantity_available ?? 0) < (int) $quantity) {
                throw ValidationException::withMessages([
                    'cart' => 'Some items no longer have enough stock. Please review your cart.',
                ]);
            }
        }

        DB::transaction(function () use ($buyer, $validated, $cart): void {
            $buyer->update([
                'name' => $validated['name'],
                'contact_no' => $validated['contact_no'],
                'address' => $validated['address'] ?? null,
            ]);

            $this->sales->createSale([
                'user_id' => $buyer->id,
                'payment_method' => $validated['payment_method'],
                'discount_rate' => 0,
                'dispatch_date' => $validated['dispatch_date'] ?? null,
                'items' => collect($cart)->map(fn ($quantity, $plantId) => [
                    'plant_species_id' => (int) $plantId,
                    'quantity_bought' => (int) $quantity,
                ])->values()->all(),
            ]);
        });

        $session->forget('buyer_cart');
    }
}
