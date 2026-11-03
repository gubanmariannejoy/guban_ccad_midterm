<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNurserySaleRequest;
use App\Http\Requests\UpdateNurserySaleRequest;
use App\Models\NurserySale;
use App\Services\NurserySaleService;
use Inertia\Inertia;

class NurserySaleController extends Controller
{
    public function __construct(
        private NurserySaleService $service
    ) {}

    public function index()
    {
        $sales = NurserySale::with('buyer')
            ->withCount('saleItems')
            ->latest()
            ->paginate(10);

        return Inertia::render('nursery-sales/index', compact('sales'));
    }

    public function create()
    {
        return Inertia::render(
            'nursery-sales/create',
            $this->service->creationData()
        );
    }

    public function edit($id)
    {
        return Inertia::render(
            'nursery-sales/create',
            $this->service->editData($id)
        );
    }

    public function store(StoreNurserySaleRequest $request)
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
        $id
    ) {
        $this->service->updateSaleDetails($id, $request->validated());

        return redirect()
            ->route('nursery-sales.index')
            ->with('success', 'Sale updated successfully.');
    }

    public function confirm($id)
    {
        $this->service->confirmSaleById($id);

        return redirect()
            ->route('nursery-sales.index')
            ->with('success', 'Sale confirmed and inventory updated.');
    }

    public function destroy($id)
    {
        $this->service->deleteSale($id);

        return redirect()
            ->route('nursery-sales.index')
            ->with('success', 'Pending sale deleted successfully.');
    }
}
