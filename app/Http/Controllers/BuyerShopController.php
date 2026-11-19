<?php

namespace App\Http\Controllers;

use App\Models\PlantSpecies;
use App\Services\NurserySaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BuyerShopController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

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

        return Inertia::render('buyer/plants', [
            'plants' => $plants,
            'search' => $search,
        ]);
    }

    public function cart(Request $request): Response
    {
        $cart = $request->session()->get('buyer_cart', []);
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

        return Inertia::render('buyer/cart', [
            'items' => $items,
            'buyer' => $request->user()->only(['name', 'contact_no', 'address']),
        ]);
    }

    public function addToCart(Request $request, PlantSpecies $plant): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $available = (int) ($plant->inventory?->quantity_available ?? 0);

        if ($available < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'This plant is currently out of stock.',
            ]);
        }

        $cart = $request->session()->get('buyer_cart', []);
        $quantity = (int) ($cart[$plant->id] ?? 0) + (int) $validated['quantity'];

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => 'The requested quantity exceeds the available stock.',
            ]);
        }

        $cart[$plant->id] = $quantity;
        $request->session()->put('buyer_cart', $cart);

        return back()->with('success', 'Plant added to your cart.');
    }

    public function updateCart(Request $request, PlantSpecies $plant): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);
        $available = (int) ($plant->inventory?->quantity_available ?? 0);

        if ((int) $validated['quantity'] > $available) {
            throw ValidationException::withMessages([
                'quantity' => 'The requested quantity exceeds the available stock.',
            ]);
        }

        $cart = $request->session()->get('buyer_cart', []);
        $cart[$plant->id] = (int) $validated['quantity'];
        $request->session()->put('buyer_cart', $cart);

        return back()->with('success', 'Cart quantity updated.');
    }

    public function removeFromCart(Request $request, PlantSpecies $plant): RedirectResponse
    {
        $cart = $request->session()->get('buyer_cart', []);
        unset($cart[$plant->id]);
        $request->session()->put('buyer_cart', $cart);

        return back()->with('success', 'Plant removed from your cart.');
    }

    public function checkout(Request $request, NurserySaleService $sales): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_no' => ['required', 'string', 'regex:/^\+63[0-9]{10}$/'],
            'address' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::in(['Cash', 'GCash', 'Bank Transfer'])],
            'dispatch_date' => ['nullable', 'date', 'after_or_equal:today', 'before_or_equal:2027-12-31'],
        ]);

        $cart = $request->session()->get('buyer_cart', []);

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

        $buyer = $request->user();
        DB::transaction(function () use ($buyer, $validated, $cart, $sales): void {
            $buyer->update([
                'name' => $validated['name'],
                'contact_no' => $validated['contact_no'],
                'address' => $validated['address'] ?? null,
            ]);

            $sales->createSale([
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

        $request->session()->forget('buyer_cart');

        return redirect()
            ->route('nursery-sales.index')
            ->with('success', 'Your order has been placed and is pending confirmation.');
    }
}
