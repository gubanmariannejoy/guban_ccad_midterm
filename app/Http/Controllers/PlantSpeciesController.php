<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlantSpeciesRequest;
use App\Http\Requests\UpdatePlantSpeciesRequest;
use App\Models\PlantSpecies;
use App\Services\PlantSpeciesService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlantSpeciesController extends Controller
{
    public function __construct(
        private PlantSpeciesService $service
    ) {}

    public function index(): Response
    {
        $plants = PlantSpecies::with('inventory') // need further optimization
            ->withCount('saleItems')
            ->latest()
            ->paginate(10);

        return Inertia::render('plant-species/index', compact('plants'));
    }

    public function create(): Response
    {
        return Inertia::render('plant-species/create');
    }

    public function edit(int|string $id): Response
    {
        return Inertia::render('plant-species/create', [
            'plant' => $this->service->findForEdit($id),
        ]);
    }

    public function store(StorePlantSpeciesRequest $request): RedirectResponse
    {
        $this->service->createPlantSpecies($request->validated());

        return redirect()
            ->route('plant-species.index')
            ->with('success', 'Plant species created successfully.');
    }

    public function update(
        UpdatePlantSpeciesRequest $request,
        int|string $id
    ): RedirectResponse {
        $this->service->updatePlantSpecies(
            $id,
            $request->validated()
        );

        return redirect()
            ->route('plant-species.index')
            ->with('success', 'Plant species updated successfully.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $this->service->deletePlantSpecies($id);

        return redirect()
            ->route('plant-species.index')
            ->with('success', 'Plant species deleted successfully.');
    }
}
