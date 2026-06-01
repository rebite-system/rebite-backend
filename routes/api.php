<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [PasswordResetController::class, 'sendCode']);
Route::post('/verify-reset-code', [PasswordResetController::class, 'verifyCode']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Restaurant / Food Routes
    |--------------------------------------------------------------------------
    */

    Route::post('/food', [FoodController::class, 'store'])
        ->middleware('role:restaurant');

    Route::get('/foods', [FoodController::class, 'index']);

    Route::put('/food/{id}', [FoodController::class, 'update'])
        ->middleware('role:restaurant');

    Route::delete('/food/{id}', [FoodController::class, 'destroy'])
        ->middleware('role:restaurant');

    Route::get('/dashboard', [DashboardController::class, 'restaurant'])
        ->middleware('role:restaurant');

    /*
    |--------------------------------------------------------------------------
    | Claim Routes
    |--------------------------------------------------------------------------
    */

    Route::post('/claim', [ClaimController::class, 'store'])
        ->middleware('role:charity');

    Route::get('/claims', [ClaimController::class, 'index'])
        ->middleware('role:restaurant');

    Route::get('/my-claims', [ClaimController::class, 'myClaims'])
        ->middleware('role:charity');

    Route::post('/claim/status/{id}', [ClaimController::class, 'updateStatus'])
        ->middleware('role:restaurant');

    /*
    |--------------------------------------------------------------------------
    | Donation Routes
    |--------------------------------------------------------------------------
    */

    Route::post('/donate', [DonationController::class, 'store'])
        ->middleware('role:donor');

    Route::get('/my-donations', [DonationController::class, 'myDonations'])
        ->middleware('role:donor');

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])
        ->middleware('role:admin');

    Route::get('/admin/settings', [AdminController::class, 'settings'])
        ->middleware('role:admin');

    Route::put('/admin/settings/notifications', [AdminController::class, 'updateNotifications'])
        ->middleware('role:admin');

    Route::get('/admin/reports/overview', [AdminController::class, 'reportsOverview'])
        ->middleware('role:admin');

    Route::get('/admin/reports', [AdminController::class, 'generateReport'])
        ->middleware('role:admin');

    Route::get('/admin/users', [AdminController::class, 'users'])
        ->middleware('role:admin');

    Route::get('/admin/pending-requests', [AdminController::class, 'pendingRequests'])
        ->middleware('role:admin');

    Route::get('/admin/donations', [DonationController::class, 'adminDonations'])
        ->middleware('role:admin');

    Route::post('/admin/ban/{id}', [AdminController::class, 'banUser'])
        ->middleware('role:admin');

    Route::post('/admin/unban/{id}', [AdminController::class, 'unbanUser'])
        ->middleware('role:admin');

    Route::post('/admin/approve/{id}', [AdminController::class, 'approveUser'])
        ->middleware('role:admin');

    Route::post('/admin/reject/{id}', [AdminController::class, 'rejectUser'])
        ->middleware('role:admin');

    Route::put('/admin/users/{id}', [AdminController::class, 'updateUser'])
        ->middleware('role:admin');

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    Route::get('/notifications', function (Request $request) {
        $user = $request->user();

        return response()->json([
            'data' => $user->notifications
        ]);
    });

    Route::post('/notifications/read-all', function (Request $request) {
        $user = $request->user();

        $user->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'Notifications marked as read'
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Profile Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'show']);

    Route::put('/profile', [ProfileController::class, 'update']);

    Route::put('/change-password', [ProfileController::class, 'changePassword']);

    /*
    |--------------------------------------------------------------------------
    | Charities
    |--------------------------------------------------------------------------
    */

    Route::get('/charities', function () {
        return response()->json([
            'data' => \App\Models\User::where('role', 'charity')->get()
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', [AuthController::class, 'logout']);
});