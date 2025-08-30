<?php

use App\Http\Controllers\API\V1\Admin\AdminController;
use App\Http\Controllers\API\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\API\V1\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\API\V1\Admin\PaymentMethodController as AdminPaymentMethodController;
use App\Http\Controllers\API\V1\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\API\V1\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\API\V1\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\API\V1\Admin\UserController as MemberUserController;
use App\Http\Controllers\API\V1\Admin\WebsiteContentController;
use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\API\V1\Member\PaymentController as MemberPaymentController;
use App\Http\Controllers\API\V1\Member\SupportController as MemberSupportController;
use App\Http\Controllers\API\V1\WebsiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

    // Public routes (no auth needed)
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::get('verify-email/{uuid}/{otp}', [AuthController::class, 'verifyEmail']);

    Route::get('homepage', [WebsiteController::class, 'homepage']);



    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // Routes accessible to both admins and members
        Route::post('logout', [AuthController::class, 'logout']);

        // Admin routes
        Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum', 'admin']], function () {

            Route::get('dashboard', [AdminDashboardController::class, 'index']);
            Route::apiResource('payment-methods', AdminPaymentMethodController::class);
            Route::apiResource('admins', AdminController::class);
            Route::apiResource('members', MemberUserController::class);
            Route::patch('admins/{id}/deactivate', [AdminController::class, 'deactivate']);

            Route::get('memberships', [AdminDashboardController::class, 'memberships']);
            Route::get('institutions', [AdminDashboardController::class, 'institutions']);
            Route::get('notifications/{userId}', [AdminDashboardController::class, 'notifications']);
            
            Route::get('applications/{userId}', [AdminDashboardController::class, 'application']);
            // approveOrRejectApplication
            Route::post('applications/{user_id}/action', [AdminDashboardController::class, 'approveOrRejectApplication']);

            //Website content links
            Route::group(['prefix' => 'website-content',], function () {
                Route::get('/', [WebsiteContentController::class, 'index']);
                Route::post('/', [WebsiteContentController::class, 'store']);
                Route::get('{section}/{key}', [WebsiteContentController::class, 'show']);
                Route::put('{section}/{key}', [WebsiteContentController::class, 'update']);
                Route::delete('{section}/{key}', [WebsiteContentController::class, 'destroy']);
            });

            //Suport Ticket Links
            Route::group(['prefix' => 'support-tickets',], function () {
                Route::get('/', [AdminSupportController::class, 'index']);
                Route::get('{id}', [AdminSupportController::class, 'show']);
                Route::post('{id}/action', [AdminSupportController::class, 'approveOrRejectTicket']);
            });

            Route::group(['prefix' => 'testimonials',], function () {
                Route::get('/', [AdminTestimonialController::class, 'index']);
                Route::get('{id}', [AdminTestimonialController::class, 'show']);
                Route::delete('{id}', [WebsiteContentController::class, 'destroy']);
                Route::post('{id}/publish', [AdminTestimonialController::class, 'publish']);
            });

            //News
            Route::group(['prefix' => 'news',], function () {
                Route::get('/', [AdminNewsController::class, 'index']);
                Route::post('/', [AdminNewsController::class, 'store']);
                Route::get('{id}', [AdminNewsController::class, 'show']);
                Route::delete('{id}', [AdminNewsController::class, 'destroy']);
                Route::post('{id}/publish', [AdminNewsController::class, 'publish']);
            });

            Route::group(['prefix' => 'settings',], function () {
                Route::match(['get', 'post'], '/general', [AdminSettingsController::class, 'updateGeneral']);
                Route::match(['get', 'post'], '/security', [AdminSettingsController::class, 'updateSecurity']);
                Route::match(['get', 'post'], '/notifications', [AdminSettingsController::class, 'updateNotifications']);
                Route::match(['get', 'post'], '/super-admin', [AdminSettingsController::class, 'updateSuperAdmin']);
            });
        });


        // Member routes
        Route::group(['prefix' => 'member', 'middleware' => ['auth:sanctum', 'member']], function () {

            Route::get('dashboard', [MemberDashboardController::class, 'index']);
            Route::get('institution', [MemberDashboardController::class, 'institution']);

            Route::post('edit-institution', [MemberDashboardController::class, 'editInstitution']);
            Route::post('edit-institution/logo-banner', [MemberDashboardController::class, 'updateInstitutionLogoAndBanner']);
            Route::post('edit-profile', [MemberDashboardController::class, 'editProfile']);
            
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
