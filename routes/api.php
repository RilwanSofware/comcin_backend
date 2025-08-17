<?php

use App\Http\Controllers\API\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\API\V1\Admin\PaymentMethodController as AdminPaymentMethodController;
use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\API\V1\Member\PaymentController as MemberPaymentController;
use App\Http\Controllers\API\V1\Member\SupportController as MemberSupportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {

    // Public routes (no auth needed)
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::get('verify-email/{uuid}/{otp}', [AuthController::class, 'verifyEmail']);



    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // Routes accessible to both admins and members
        Route::post('logout', [AuthController::class, 'logout']);

        // Admin routes
        Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum', 'admin']], function () {

            Route::get('dashboard', [AdminDashboardController::class, 'index']);
            Route::apiResource('payment-methods', AdminPaymentMethodController::class);

            Route::get('memberships', [AdminDashboardController::class, 'memberships']);
            Route::get('institutions', [AdminDashboardController::class, 'institutions']);
            Route::get('applications', [AdminDashboardController::class, 'applications']);
            Route::get('notifications/{userId}', [AdminDashboardController::class, 'notifications']);

            // approveOrRejectApplication
            Route::post('applications/{user_id}/action"', [AdminDashboardController::class, 'approveOrRejectApplication']);
        });


        // Member routes
        Route::group(['prefix' => 'member', 'middleware' => ['auth:sanctum', 'member']], function () {
            
            Route::get('dashboard', [MemberDashboardController::class, 'index']);
            Route::get('institution', [MemberDashboardController::class, 'institution']);
            Route::post('edit-institution', [MemberDashboardController::class, 'editInstitution']);
            Route::get('financials', [MemberDashboardController::class, 'financials']);
            Route::get('certificates', [MemberDashboardController::class, 'certificates']);

            //payment
            Route::get('payment', [MemberPaymentController::class, 'getPaymentMethods']);
            Route::post('payment/manual', [MemberPaymentController::class, 'manualPayment']);
            Route::post('payment/paystack/verify', [MemberPaymentController::class, 'verifyPaystackPayment']);

            // Notifications
            Route::get('notifications', [MemberDashboardController::class, 'notifications']);
            Route::post('notifications/mark-as-read', [MemberDashboardController::class, 'markNotificationAsRead']);

            // Support tickets
            Route::get('support/tickets', [MemberSupportController::class, 'getAllSupportTickets']);
            Route::post('support/tickets', [MemberSupportController::class, 'createSupportTicket']);
            Route::get('support/tickets/{uuid}', [MemberSupportController::class, 'getSupportTicket']);


        });
    });
});
