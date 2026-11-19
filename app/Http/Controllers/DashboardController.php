<?php

namespace App\Http\Controllers;

use App\Models\NurserySale;
use App\Models\PlantInventory;
use App\Models\PlantSpecies;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            return Inertia::render('buyer-dashboard', [
                'sales' => $user->sales()
                    ->withCount('saleItems')
                    ->latest()
                    ->limit(10)
                    ->get(),
                'stats' => [
                    'pendingOrders' => $user->sales()->where('status', 'pending')->count(),
                    'completedPurchases' => $user->sales()->where('status', 'completed')->count(),
                ],
                'featuredPlants' => PlantSpecies::with('inventory')
                    ->whereHas('inventory', fn ($query) => $query->where('quantity_available', '>', 0))
                    ->orderBy('plant_name')
                    ->limit(4)
                    ->get(),
            ]);
        }

        return Inertia::render('admin-dashboard', [
            'stats' => [
                'plants' => PlantSpecies::count(),
                'buyers' => User::where('role', 'buyer')->count(),
                'sales' => NurserySale::count(),
                'pendingSales' => NurserySale::where('status', 'pending')->count(),
                'availableStock' => PlantInventory::sum('quantity_available'),
                'confirmedRevenue' => NurserySale::whereIn('status', ['confirmed', 'completed'])
                    ->sum('total_amount'),
            ],
        ]);
    }
}
