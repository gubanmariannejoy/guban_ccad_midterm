<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBuyerRequest;
use App\Http\Requests\UpdateBuyerRequest;
use App\Services\BuyerService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BuyerController extends Controller
{
    public function index(BuyerService $buyers): Response
    {
        $buyersPage = $buyers->listBuyers();

        return Inertia::render('buyers/index', ['buyers' => $buyersPage]);
    }

    public function create(): Response
    {
        return Inertia::render('buyers/create');
    }

    public function store(StoreBuyerRequest $request, BuyerService $buyers): RedirectResponse
    {
        $buyers->createBuyer($request->validated());

        return redirect()
            ->route('buyers.index')
            ->with('success', 'Buyer added successfully.');
    }

    public function edit(int|string $id, BuyerService $buyers): Response
    {
        return Inertia::render('buyers/edit', [
            'buyer' => $buyers->buyerForEdit($id),
        ]);
    }

    public function update(UpdateBuyerRequest $request, int|string $id, BuyerService $buyers): RedirectResponse
    {
        $buyers->updateBuyer($id, $request->validated());

        return redirect()
            ->route('buyers.index')
            ->with('success', 'Buyer information updated successfully.');
    }

    public function destroy(int|string $id, BuyerService $buyers): RedirectResponse
    {
        $buyers->deleteBuyer($id);

        return redirect()
            ->route('buyers.index')
            ->with('success', 'Buyer deleted successfully.');
    }
}
