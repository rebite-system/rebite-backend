<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Claim;
use App\Models\Food;
use App\Notifications\ClaimNotification;

class ClaimController extends Controller
{
    public function store(Request $r)
    {
        $r->validate([
            'food_id' => 'required|exists:food,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $food = Food::findOrFail($r->food_id);

        if ($food->status !== 'active') {
            return response()->json([
                'message' => 'This food listing is not available'
            ], 400);
        }

        if ($r->quantity > $food->quantity) {
            return response()->json([
                'message' => 'Requested quantity exceeds available quantity'
            ], 400);
        }

        $exists = Claim::where('food_id', $r->food_id)
            ->where('charity_id', auth()->id())
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Already claimed'
            ], 400);
        }

        $claim = Claim::create([
            'food_id' => $r->food_id,
            'charity_id' => auth()->id(),
            'quantity' => $r->quantity,
            'status' => 'pending'
        ]);

        if ($food->restaurant) {
            $food->restaurant->notify(
    new ClaimNotification(
        auth()->user()->name .
        ' requested ' .
        $claim->quantity .
        ' portions of "' .
        $food->title .
        '"'
    )
);
        }

        return response()->json([
            'message' => 'Claim created',
            'data' => $claim
        ]);
    }

    public function index()
    {
        $claims = Claim::with(['charity', 'food'])
            ->whereHas('food', function ($q) {
                $q->where('restaurant_id', auth()->id());
            })
            ->latest()
            ->get();

        return response()->json([
            'data' => $claims
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        if (!in_array($request->status, ['accepted', 'rejected', 'collected'])) {
            return response()->json([
                'message' => 'Invalid status'
            ], 400);
        }

        $claim = Claim::with(['food', 'charity'])
            ->whereHas('food', function ($q) {
                $q->where('restaurant_id', auth()->id());
            })
            ->findOrFail($id);

        $food = $claim->food;

        if ($request->status === 'accepted') {
            if ($claim->status !== 'pending') {
                return response()->json([
                    'message' => 'Only pending claims can be accepted'
                ], 400);
            }

            if ($food->status !== 'active') {
                return response()->json([
                    'message' => 'This food listing is no longer active'
                ], 400);
            }

            if ($claim->quantity > $food->quantity) {
                return response()->json([
                    'message' => 'Requested quantity no longer available'
                ], 400);
            }

            $food->decrement('quantity', $claim->quantity);
            $food->refresh();

            if ($food->quantity <= 0) {
                $food->quantity = 0;
            }

            $food->status = 'reserved';
            $food->save();
        }

        if ($request->status === 'rejected') {
            if ($claim->status !== 'pending') {
                return response()->json([
                    'message' => 'Only pending claims can be rejected'
                ], 400);
            }
        }

        if ($request->status === 'collected') {
            if ($claim->status !== 'accepted') {
                return response()->json([
                    'message' => 'Only accepted claims can be marked as collected'
                ], 400);
            }

            $food->status = 'collected';
            $food->save();
        }

        $claim->status = $request->status;
        $claim->save();

        if ($claim->charity) {
            $claim->charity->notify(
    new ClaimNotification(
        'Your request for "' .
        $food->title .
        '" from ' .
        $food->restaurant->name .
        ' was ' .
        $claim->status
    )
);
        }

        return response()->json([
            'message' => 'Status updated',
            'data' => $claim->load(['food', 'charity'])
        ]);
    }

    public function myClaims()
{
    return response()->json([
        'data' => Claim::with(['food.restaurant'])
            ->where('charity_id', auth()->id())
            ->latest()
            ->get()
    ]);
}
}