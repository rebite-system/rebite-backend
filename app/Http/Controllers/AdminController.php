<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Cache;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\SystemSetting;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Food;
use App\Models\Claim;
use App\Models\Donation;

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json([

            'total_users' => User::where('role', '!=', 'admin')->count(),

            'restaurants' => User::where('role', 'restaurant')->count(),

            'charities' => User::where('role', 'charity')->count(),

            'donors' => User::where('role', 'donor')->count(),

            'pending_restaurants' => User::where('role', 'restaurant')
                ->where('is_approved', false)
                ->count(),

            'pending_charities' => User::where('role', 'charity')
                ->where('is_approved', false)
                ->count(),

            'total_food' => Food::count(),

            'total_claims' => Claim::count(),

            'accepted_claims' => Claim::where('status', 'accepted')->count(),

            'pending_claims' => Claim::where('status', 'pending')->count(),

            'rejected_claims' => Claim::where('status', 'rejected')->count(),

            'total_donations' => Donation::count(),

            'total_amount' => Donation::sum('amount'),

            'platform_profit' => Donation::sum('platform_fee'),
        ]);
    }

    public function users()
    {
        $users = User::where('role', '!=', 'admin')
            ->latest()
            ->get();

        return response()->json([
            'data' => $users
        ]);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Admin account cannot be edited'
            ], 403);
        }

        $data = $request->validate([

            'name' =>
                'required|string|max:255',

            'email' =>
                'required|email|unique:users,email,' . $user->id,

            'phone' =>
                'nullable|string|max:20',

            'location' =>
                'nullable|string|max:255',

            'role' =>
                'required|string|in:restaurant,charity,donor',

            'is_approved' =>
                'nullable|boolean',

            'is_banned' =>
                'nullable|boolean',
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    public function banUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Admin account cannot be banned'
            ], 403);
        }

        $user->is_banned = true;

        $user->save();

        return response()->json([
            'message' => 'User banned successfully'
        ]);
    }

    public function unbanUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Admin account cannot be unbanned'
            ], 403);
        }

        $user->is_banned = false;

        $user->save();

        return response()->json([
            'message' => 'User unbanned successfully'
        ]);
    }

    public function approveUser($id)
    {
        $user = User::where('id', $id)
            ->whereIn('role', ['restaurant', 'charity'])
            ->firstOrFail();

        $user->is_approved = true;

        $user->save();

        return response()->json([
            'message' => 'User approved successfully'
        ]);
    }

    public function rejectUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Admin account cannot be rejected'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Request rejected successfully'
        ]);
    }

    public function pendingRequests()
    {
        $pending = User::whereIn('role', ['restaurant', 'charity'])
            ->where('is_approved', false)
            ->latest()
            ->get();

        return response()->json([
            'data' => $pending
        ]);
    }

    /* ================= SYSTEM SETTINGS ================= */

   /* ================= SYSTEM SETTINGS DATABASE ================= */

public function settings()
{
    $notifications = SystemSetting::where('key', 'notifications')->first();

    return response()->json([
        'data' => [
            'locations' => Location::latest()->get(),
            'notifications' => $notifications?->value ?? [
                'newRegistrations' => true,
                'urgentFoodAlerts' => true,
                'approvalUpdates' => true,
                'newDonationAlerts' => true,
            ],
        ],
    ]);
}

public function storeLocation(Request $request)
{
    $data = $request->validate([
        'name' => 'required|string|max:255|unique:locations,name',
    ]);

    Location::create([
        'name' => $data['name'],
        'status' => 'Active',
    ]);

    return response()->json([
        'message' => 'Location added successfully',
        'data' => Location::latest()->get(),
    ]);
}

public function updateLocation(Request $request, $id)
{
    $location = Location::findOrFail($id);

    $data = $request->validate([
        'name' => 'required|string|max:255|unique:locations,name,' . $id,
        'status' => 'nullable|string|max:50',
    ]);

    $location->update([
        'name' => $data['name'],
        'status' => $data['status'] ?? 'Active',
    ]);

    return response()->json([
        'message' => 'Location updated successfully',
        'data' => Location::latest()->get(),
    ]);
}

public function deleteLocation($id)
{
    Location::findOrFail($id)->delete();

    return response()->json([
        'message' => 'Location deleted successfully',
        'data' => Location::latest()->get(),
    ]);
}

public function updateNotifications(Request $request)
{
    $data = $request->validate([
        'newRegistrations' => 'required|boolean',
        'urgentFoodAlerts' => 'required|boolean',
        'approvalUpdates' => 'required|boolean',
        'newDonationAlerts' => 'required|boolean',
    ]);

    SystemSetting::updateOrCreate(
        ['key' => 'notifications'],
        ['value' => $data]
    );

    return response()->json([
        'message' => 'Notification settings saved successfully',
        'data' => $data,
    ]);
}

    /* ================= REPORTS ================= */

    public function reportsOverview()
    {
        return response()->json([
            'data' => [
                [
                    'label' => 'Total Donations',
                    'value' => Donation::count(),
                ],
                [
                    'label' => 'Food Listings',
                    'value' => Food::count(),
                ],
                [
                    'label' => 'Total Users',
                    'value' => User::where('role', '!=', 'admin')->count(),
                ],
                [
                    'label' => 'Pending Requests',
                    'value' => User::whereIn('role', ['restaurant', 'charity'])
                        ->where('is_approved', false)
                        ->count(),
                ],
            ],
        ]);
    }

    public function generateReport(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $from = Carbon::parse(
            $data['date_from'] ?? now()->startOfMonth()
        )->startOfDay();

        $to = Carbon::parse(
            $data['date_to'] ?? now()
        )->endOfDay();

        $type = $data['type'];

        if ($type === 'Donations Report') {
            $query = Donation::whereBetween('created_at', [$from, $to]);

            $paid = (clone $query)
                ->where('payment_status', 'paid')
                ->count();

            $pending = (clone $query)
                ->where('payment_status', 'pending')
                ->count();

            $failed = (clone $query)
                ->where('payment_status', 'failed')
                ->count();

            return response()->json([
                'data' => [
                    'stats' => [
                        [
                            'label' => 'Total Donations',
                            'value' => (clone $query)->count(),
                        ],
                        [
                            'label' => 'Total Amount',
                            'value' => number_format((clone $query)->sum('amount')) . ' EGP',
                        ],
                        [
                            'label' => 'Paid Donations',
                            'value' => $paid,
                        ],
                        [
                            'label' => 'Platform Fees',
                            'value' => number_format((clone $query)->sum('platform_fee')) . ' EGP',
                        ],
                    ],

                    'summary' => [
                        [
                            'label' => 'Paid',
                            'value' => $paid,
                            'color' => '#27ae60',
                        ],
                        [
                            'label' => 'Pending',
                            'value' => $pending,
                            'color' => '#f39c12',
                        ],
                        [
                            'label' => 'Failed',
                            'value' => $failed,
                            'color' => '#e74c3c',
                        ],
                    ],
                ],
            ]);
        }

        if ($type === 'Food Waste Report') {
            $query = Food::whereBetween('created_at', [$from, $to]);

            $active = (clone $query)
                ->where(function ($q) {
                    $q->whereNull('expiry')
                        ->orWhere('expiry', '>=', now());
                })
                ->count();

            $expired = (clone $query)
                ->whereNotNull('expiry')
                ->where('expiry', '<', now())
                ->count();

            return response()->json([
                'data' => [
                    'stats' => [
                        [
                            'label' => 'Food Listings',
                            'value' => (clone $query)->count(),
                        ],
                        [
                            'label' => 'Total Quantity',
                            'value' => (clone $query)->sum('quantity') . ' portions',
                        ],
                        [
                            'label' => 'Active Listings',
                            'value' => $active,
                        ],
                        [
                            'label' => 'Expired Listings',
                            'value' => $expired,
                        ],
                    ],

                    'summary' => [
                        [
                            'label' => 'Active',
                            'value' => $active,
                            'color' => '#27ae60',
                        ],
                        [
                            'label' => 'Expired',
                            'value' => $expired,
                            'color' => '#e74c3c',
                        ],
                    ],
                ],
            ]);
        }

        if ($type === 'Users Report') {
            $query = User::whereBetween('created_at', [$from, $to]);

            $restaurants = (clone $query)
                ->where('role', 'restaurant')
                ->count();

            $charities = (clone $query)
                ->where('role', 'charity')
                ->count();

            $donors = (clone $query)
                ->where('role', 'donor')
                ->count();

            $admins = (clone $query)
                ->where('role', 'admin')
                ->count();

            return response()->json([
                'data' => [
                    'stats' => [
                        [
                            'label' => 'Restaurants',
                            'value' => $restaurants,
                        ],
                        [
                            'label' => 'Charities',
                            'value' => $charities,
                        ],
                        [
                            'label' => 'Donors',
                            'value' => $donors,
                        ],
                        [
                            'label' => 'Admins',
                            'value' => $admins,
                        ],
                    ],

                    'summary' => [
                        [
                            'label' => 'Restaurants',
                            'value' => $restaurants,
                            'color' => '#27ae60',
                        ],
                        [
                            'label' => 'Charities',
                            'value' => $charities,
                            'color' => '#2980b9',
                        ],
                        [
                            'label' => 'Donors',
                            'value' => $donors,
                            'color' => '#f39c12',
                        ],
                    ],
                ],
            ]);
        }

        $foodQuery = Food::whereBetween('created_at', [$from, $to]);

        $donationQuery = Donation::whereBetween('created_at', [$from, $to]);

        $savedPortions = (clone $foodQuery)->sum('quantity');

        return response()->json([
            'data' => [
                'stats' => [
                    [
                        'label' => 'Saved Portions',
                        'value' => $savedPortions,
                    ],
                    [
                        'label' => 'Platform Fees',
                        'value' => number_format((clone $donationQuery)->sum('platform_fee')) . ' EGP',
                    ],
                    [
                        'label' => 'Supported Areas',
                        'value' => count(
                            Cache::get(
                                'admin_supported_locations',
                                $this->defaultLocations()
                            )
                        ),
                    ],
                    [
                        'label' => 'Estimated CO₂ Saved',
                        'value' => number_format($savedPortions * 0.5, 1) . ' kg',
                    ],
                ],

                'summary' => [
                    [
                        'label' => 'Collected',
                        'value' => $savedPortions,
                        'color' => '#27ae60',
                    ],
                    [
                        'label' => 'Food Listings',
                        'value' => (clone $foodQuery)->count(),
                        'color' => '#f39c12',
                    ],
                    [
                        'label' => 'Donations',
                        'value' => (clone $donationQuery)->count(),
                        'color' => '#2980b9',
                    ],
                ],
            ],
        ]);
    }
}