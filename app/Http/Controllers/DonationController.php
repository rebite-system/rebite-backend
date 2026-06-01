<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\User;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    public function store(Request $r)
    {
        $r->validate([
            'charity_id' => 'nullable|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:card,vodafone,instapay',
            'payment_account' => 'nullable|string|max:255',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $charity = null;

        if ($r->charity_id) {
            $charity = User::where('id', $r->charity_id)
                ->where('role', 'charity')
                ->first();

            if (!$charity) {
                return response()->json([
                    'message' => 'Selected user is not a charity'
                ], 400);
            }
        }

        $amount = Number_format((float) $r->amount, 2, '.', '');
        $feePercent = 0.10;

        $fee = round($amount * $feePercent, 2);
        $charityAmount = round($amount - $fee, 2);

        $donation = Donation::create([
            'donor_id' => auth()->id(),
            'charity_id' => $r->charity_id ?? null,
            'amount' => $amount,
            'platform_fee' => $fee,
            'charity_amount' => $charityAmount,
            'payment_method' => $r->payment_method,
            'payment_account' => $r->payment_account,
            'payment_reference' => $r->payment_reference,
            'payment_status' => 'paid',
        ]);

        return response()->json([
            'message' => 'Donation completed successfully',
            'data' => [
                'id' => $donation->id,
                'amount' => $donation->amount,
                'platform_fee' => $donation->platform_fee,
                'charity_amount' => $donation->charity_amount,
                'payment_method' => $donation->payment_method,
                'payment_account' => $donation->payment_account,
                'payment_reference' => $donation->payment_reference,
                'payment_status' => $donation->payment_status,
                'created_at' => $donation->created_at,
                'charity' => $charity ? [
                    'id' => $charity->id,
                    'name' => $charity->name,
                    'email' => $charity->email,
                ] : null,
            ]
        ]);
    }

    public function myDonations()
    {
        $donations = Donation::with('charity')
            ->where('donor_id', auth()->id())
            ->latest()
            ->get()
            ->map(function ($donation) {
                return [
                    'id' => $donation->id,
                    'amount' => $donation->amount,
                    'platform_fee' => $donation->platform_fee,
                    'charity_amount' => $donation->charity_amount,
                    'payment_method' => $donation->payment_method,
                    'payment_account' => $donation->payment_account,
                    'payment_reference' => $donation->payment_reference,
                    'payment_status' => $donation->payment_status,
                    'created_at' => $donation->created_at,
                    'charity' => $donation->charity ? [
                        'id' => $donation->charity->id,
                        'name' => $donation->charity->name,
                        'email' => $donation->charity->email,
                    ] : null,
                ];
            });

        return response()->json([
            'data' => $donations
        ]);
    }

    public function adminDonations()
    {
        return response()->json([
            'data' => Donation::with(['donor', 'charity'])
                ->latest()
                ->get()
        ]);
    }
}