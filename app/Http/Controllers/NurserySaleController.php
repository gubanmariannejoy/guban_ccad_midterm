<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNurserySaleRequest;
use App\Http\Requests\UpdateNurserySaleRequest;
use App\Models\NurserySale;
use App\Services\NurserySaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NurserySaleController extends Controller
{
    public function __construct(
        private NurserySaleService $service
    ) {}

    public function index(): Response
    {
        $query = NurserySale::with(['buyer', 'saleItems.plantSpecies'])
            ->withCount('saleItems')
            ->latest();

        if (! auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        $sales = $query->paginate(10);

        return Inertia::render('nursery-sales/index', compact('sales'));
    }

    public function create(): Response
    {
        return Inertia::render(
            'nursery-sales/create',
            $this->service->creationData()
        );
    }

    public function edit(int|string $id): Response
    {
        return Inertia::render(
            'nursery-sales/create',
            $this->service->editData($id)
        );
    }

    public function store(StoreNurserySaleRequest $request): RedirectResponse
    {
        $this->service->createSale(
            $request->validated()
        );

        return redirect()
            ->route('nursery-sales.index')
            ->with('success', 'Sale created successfully.');
    }

    public function update(
        UpdateNurserySaleRequest $request,
        int|string $id
    ): RedirectResponse {
        $this->service->updateSaleDetails($id, $request->validated());

        return redirect()
            ->route('nursery-sales.index')
            ->with('success', 'Sale updated successfully.');
    }

    public function confirm(int|string $id): RedirectResponse
    {
        $this->service->confirmSaleById($id);

        return redirect()
            ->route('nursery-sales.index')
            ->with('success', 'Sale confirmed and inventory updated.');
    }

    public function cancel(int|string $id): RedirectResponse
    {
        $this->service->cancelSaleById($id);

        return redirect()
            ->route('nursery-sales.index')
            ->with('success', 'Order cancelled.');
    }

    public function cancelForBuyer(Request $request, int|string $id): RedirectResponse
    {
        $this->service->cancelPendingSaleForBuyer($id, $request->user());

        return redirect()
            ->route('nursery-sales.index')
            ->with('success', 'Your order has been cancelled.');
    }

    public function destroyForBuyer(Request $request, int|string $id): RedirectResponse
    {
        $this->service->deleteCompletedSaleForBuyer($id, $request->user());

        return redirect()
            ->route('nursery-sales.index')
            ->with('success', 'Your completed order has been deleted.');
    }

    public function complete(int|string $id): RedirectResponse
    {
        $this->service->completeSaleById($id);

        return redirect()
            ->route('nursery-sales.index')
            ->with('success', 'Order marked as completed.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $this->service->deleteSale($id);

        return redirect()
            ->route('nursery-sales.index')
            ->with('success', 'Pending sale deleted successfully.');
    }
}
