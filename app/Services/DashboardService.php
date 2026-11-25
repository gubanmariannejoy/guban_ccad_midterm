<?php

namespace App\Services;

use App\Models\NurserySale;
use App\Models\PlantInventory;
use App\Models\PlantSpecies;
use App\Models\User;

class DashboardService
{
    public function dashboardFor(User $user): array
    {
        if (! $user->isAdmin()) {
            return [
                'component' => 'buyer-dashboard',
                'props' => [
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
                ],
            ];
        }

        return [
            'component' => 'admin-dashboard',
            'props' => [
                'stats' => [
                    'plants' => PlantSpecies::count(),
                    'buyers' => User::where('role', 'buyer')->count(),
                    'sales' => NurserySale::count(),
                    'pendingSales' => NurserySale::where('status', 'pending')->count(),
                    'availableStock' => PlantInventory::sum('quantity_available'),
                    'confirmedRevenue' => NurserySale::whereIn('status', ['confirmed', 'completed'])
                        ->sum('total_amount'),
                ],
            ],
        ];
    }
}
