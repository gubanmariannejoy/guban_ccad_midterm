<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlantSpeciesRequest;
use App\Http\Requests\UpdatePlantSpeciesRequest;
use App\Models\PlantSpecies;
use App\Services\PlantSpeciesService;
use Inertia\Inertia;

class PlantSpeciesController extends Controller
{
    public function __construct(
        private PlantSpeciesService $service
    ) {}

    public function index()
    {
        $plants = PlantSpecies::with('inventory')
            ->withCount('saleItems')
            ->latest()
            ->paginate(10);

        return Inertia::render('plant-species/index', compact('plants'));
    }

    public function create()
    {
        return Inertia::render('plant-species/create');
    }

    public function edit($id)
    {
        return Inertia::render('plant-species/create', [
            'plant' => $this->service->findForEdit($id),
        ]);
    }

    public function store(StorePlantSpeciesRequest $request)
    {
        $this->service->createPlantSpecies($request->validated());

        return redirect()
            ->route('plant-species.index')
            ->with('success', 'Plant species created successfully.');
    }

    public function update(
        UpdatePlantSpeciesRequest $request,
        $id
    ) {
        $this->service->updatePlantSpecies(
            $id,
            $request->validated()
        );

        return redirect()
            ->route('plant-species.index')
            ->with('success', 'Plant species updated successfully.');
    }

    public function destroy($id)
    {
        $this->service->deletePlantSpecies($id);

        return redirect()
            ->route('plant-species.index')
            ->with('success', 'Plant species deleted successfully.');
    }
}
