<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\BuyerCheckoutRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Models\PlantSpecies;
use App\Services\BuyerShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BuyerShopController extends Controller
{
    public function index(Request $request, BuyerShopService $shop): Response
    {
        return Inertia::render(
            'buyer/plants',
            $shop->catalog((string) $request->query('search', ''))
        );
    }

    public function cart(Request $request, BuyerShopService $shop): Response
    {
        $cart = $request->session()->get('buyer_cart', []);

        return Inertia::render(
            'buyer/cart',
            $shop->cartPageData($cart, $request->user())
        );
    }

    public function addToCart(AddToCartRequest $request, PlantSpecies $plant, BuyerShopService $shop): RedirectResponse
    {
        $shop->addToCart(
            $request->session(),
            $plant,
            (int) $request->validated('quantity')
        );

        return back()->with('success', 'Plant added to your cart.');
    }

    public function updateCart(UpdateCartRequest $request, PlantSpecies $plant, BuyerShopService $shop): RedirectResponse
    {
        $shop->updateCart(
            $request->session(),
            $plant,
            (int) $request->validated('quantity')
        );

        return back()->with('success', 'Cart quantity updated.');
    }

    public function removeFromCart(Request $request, PlantSpecies $plant, BuyerShopService $shop): RedirectResponse
    {
        $shop->removeFromCart($request->session(), $plant);

        return back()->with('success', 'Plant removed from your cart.');
    }

    public function checkout(BuyerCheckoutRequest $request, BuyerShopService $shop): RedirectResponse
    {
        $shop->checkout(
            $request->session(),
            $request->user(),
            $request->validated()
        );

        return redirect()
            ->route('nursery-sales.index')
            ->with('success', 'Your order has been placed and is pending confirmation.');
    }
}
