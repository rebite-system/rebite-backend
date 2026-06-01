<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\Claim;

class DashboardController extends Controller
{
    public function restaurant()
    {
        $restaurantId = auth()->id();

        $totalClaims = Claim::whereHas('food', function ($q) use ($restaurantId) {
            $q->where('restaurant_id', $restaurantId);
        })->count();

        $accepted = Claim::where('status', 'accepted')
            ->whereHas('food', fn($q) => $q->where('restaurant_id', $restaurantId))
            ->count();

        $rejected = Claim::where('status', 'rejected')
            ->whereHas('food', fn($q) => $q->where('restaurant_id', $restaurantId))
            ->count();

        $pending = Claim::where('status', 'pending')
            ->whereHas('food', fn($q) => $q->where('restaurant_id', $restaurantId))
            ->count();

        $totalFood = Food::where('restaurant_id', $restaurantId)->sum('quantity');

        return response()->json([
            'total_claims' => $totalClaims,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'pending' => $pending,
            'remaining_food' => $totalFood
        ]);
    }
}
