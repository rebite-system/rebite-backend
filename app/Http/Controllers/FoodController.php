<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Food;
use App\Services\FoodAiPriorityService;

class FoodController extends Controller
{
    public function store(Request $r, FoodAiPriorityService $aiService)
    {
        $data = $r->validate([

            'title' =>
                'required|string|max:255',

            'category' =>
                'nullable|string|max:255',

            'quantity' =>
                'required|integer|min:1',

            'expiry' =>
                'required|date',

            'pickup_from' =>
                'nullable|date_format:H:i',

            'pickup_until' =>
                'nullable|date_format:H:i',

            'notes' =>
                'nullable|string|max:1000',

            'image' =>
                'nullable|image|mimes:jpg,jpeg,png|max:2048',

        ]);

        $imagePath = null;

        if ($r->hasFile('image')) {
            $imagePath = $r->file('image')->store('food_images', 'public');
        }

        $food = Food::create([
            'title' => $data['title'],
            'category' => $data['category'] ?? null,
            'quantity' => $data['quantity'],
            'expiry' => $data['expiry'],
            'pickup_from' => $data['pickup_from'] ?? null,
            'pickup_until' => $data['pickup_until'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'active',
            'restaurant_id' => auth()->id(),
            'image' => $imagePath,
        ]);
        $ai = $aiService->analyze($food);

$food->ai_priority_level = $ai["ai_priority_level"];
$food->ai_priority_score = $ai["ai_priority_score"];
$food->ai_priority_reason = $ai["ai_priority_reason"];
$food->ai_recommended_action = $ai["ai_recommended_action"];
$food->save();

        return response()->json([
            'message' => 'Food listing created successfully',
            'data' => $food
        ]);
    }

  public function index(Request $request)
{
    $user = auth()->user();

    if ($user->role === 'restaurant') {
        $foods = Food::with('restaurant')
            ->where('restaurant_id', $user->id)
            ->orderByDesc('ai_priority_score')
            ->latest()
            ->paginate(10);
    } else {
        $foods = Food::with('restaurant')
            ->where('status', 'active')
            ->orderByDesc('ai_priority_score')
            ->latest()
            ->paginate(10);

        $foods->getCollection()->transform(function ($food) use ($user) {
            $food->distance_km = null;

            if (
                $user->latitude &&
                $user->longitude &&
                $food->restaurant &&
                $food->restaurant->latitude &&
                $food->restaurant->longitude
            ) {
                $earthRadius = 6371;

                $latFrom = deg2rad($user->latitude);
                $lonFrom = deg2rad($user->longitude);
                $latTo = deg2rad($food->restaurant->latitude);
                $lonTo = deg2rad($food->restaurant->longitude);

                $latDelta = $latTo - $latFrom;
                $lonDelta = $lonTo - $lonFrom;

                $a = sin($latDelta / 2) * sin($latDelta / 2) +
                    cos($latFrom) * cos($latTo) *
                    sin($lonDelta / 2) * sin($lonDelta / 2);

                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

                $food->distance_km = round($earthRadius * $c, 2);
            }

            return $food;
        });

        $sorted = $foods
            ->getCollection()
            ->sortBy(function ($food) {
                return $food->distance_km ?? 999999;
            })
            ->values();

        $foods->setCollection($sorted);
    }

    return response()->json([
        'data' => $foods
    ]);
}

    public function update(Request $request, $id)
    {
        $food = Food::findOrFail($id);

        if ($food->restaurant_id != auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validate([

            'title' =>
                'required|string|max:255',

            'category' =>
                'nullable|string|max:255',

            'quantity' =>
                'required|integer|min:1',

            'expiry' =>
                'nullable|date',

            'pickup_from' =>
                'nullable|date_format:H:i',

            'pickup_until' =>
                'nullable|date_format:H:i',

            'notes' =>
                'nullable|string|max:1000',

            'status' =>
                'nullable|string|in:active,reserved,collected,expired',

            'image' =>
                'nullable|image|mimes:jpg,jpeg,png|max:2048',

        ]);

        if ($request->hasFile('image')) {
            if ($food->image) {
                Storage::disk('public')->delete($food->image);
            }

            $food->image = $request->file('image')->store('food_images', 'public');
        }

        $food->title = $data['title'];
        $food->category = $data['category'] ?? null;
        $food->quantity = $data['quantity'];
        $food->expiry = $data['expiry'] ?? $food->expiry;
        $food->pickup_from = $data['pickup_from'] ?? null;
        $food->pickup_until = $data['pickup_until'] ?? null;
        $food->notes = $data['notes'] ?? null;

        if (isset($data['status'])) {
            $food->status = $data['status'];
        }

        $food->save();

        return response()->json([
            'message' => 'Food listing updated successfully',
            'data' => $food
        ]);
    }

    public function destroy($id)
    {
        $food = Food::findOrFail($id);

        if ($food->restaurant_id != auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($food->image) {
            Storage::disk('public')->delete($food->image);
        }

        $food->delete();

        return response()->json([
            'message' => 'Food listing deleted successfully'
        ]);
    }
} 