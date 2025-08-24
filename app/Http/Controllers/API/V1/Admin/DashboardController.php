<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Charges;
use App\Models\Institution;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class DashboardController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v1/admin/dashboard",
     *    operationId="getDashboardData",
     *    summary="Get admin dashboard data",
     *    tags={"Admin - Dashboard"},
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(response=200, description="Dashboard data retrieved successfully"),
     *    @OA\Response(response=500, description="Internal server error")
     *     
     * )
     */
    public function index()
    {
        try {
            // ====== Base Counts ======
            $total_institutions = Institution::count();
            $active_members = User::where('role', 'member')->where('is_active', 1)->count();
            $total_pending_application = Institution::where('status', 'pending')->count();
            $total_revenue = Transaction::where('status', 'successful')->sum('amount');

            // ====== Date Ranges for Percentage Increase ======
            $twoMonthsAgoStart = now()->subMonths(2)->startOfMonth();
            $twoMonthsAgoEnd   = now()->subMonths(2)->endOfMonth();
            $lastMonthStart    = now()->subMonth()->startOfMonth();
            $lastMonthEnd      = now()->subMonth()->endOfMonth();

            // Institutions increase %
            $institutions_two_months_ago = Institution::whereBetween('created_at', [$twoMonthsAgoStart, $twoMonthsAgoEnd])->count();
            $institutions_last_month     = Institution::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
            $total_institutions_percentage_increase = $institutions_two_months_ago > 0
                ? (($institutions_last_month - $institutions_two_months_ago) / $institutions_two_months_ago) * 100
                : 0;

            // Active members increase %
            $active_members_two_months_ago = User::where('role', 'member')->where('is_active', 1)
                ->whereBetween('created_at', [$twoMonthsAgoStart, $twoMonthsAgoEnd])
                ->count();
            $active_members_last_month = User::where('role', 'member')->where('is_active', 1)
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
                ->count();
            $active_members_percentage_increase = $active_members_two_months_ago > 0
                ? (($active_members_last_month - $active_members_two_months_ago) / $active_members_two_months_ago) * 100
                : 0;

            // Pending applications increase %
            $pending_two_months_ago = Institution::where('status', 'pending')
                ->whereBetween('created_at', [$twoMonthsAgoStart, $twoMonthsAgoEnd])
                ->count();
            $pending_last_month = Institution::where('status', 'pending')
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
                ->count();
            $total_pending_application_percentage_increase = $pending_two_months_ago > 0
                ? (($pending_last_month - $pending_two_months_ago) / $pending_two_months_ago) * 100
                : 0;

            // Revenue increase %
            $revenue_two_months_ago = Transaction::where('status', 'successful')
                ->whereBetween('created_at', [$twoMonthsAgoStart, $twoMonthsAgoEnd])
                ->sum('amount');
            $revenue_last_month = Transaction::where('status', 'successful')
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
                ->sum('amount');
            $total_revenue_percentage_increase = $revenue_two_months_ago > 0
                ? (($revenue_last_month - $revenue_two_months_ago) / $revenue_two_months_ago) * 100
                : 0;

            // ====== Status Percentages ======
            $total_members = User::where('role', 'member')->count();
            $pending_approvals_count = Institution::where('status', 'pending')->count();
            $under_review_count = Institution::where('status', 'verifying')->count(); // assuming 3 means under review
            $suspended_members_count = User::where('role', 'member')->where('is_active', 0)->count();

            $active_members_percentage = $total_members > 0 ? ($active_members / $total_members) * 100 : 0;
            $pending_approvals_percentage = $total_members > 0 ? ($pending_approvals_count / $total_members) * 100 : 0;
            $under_review_percentage = $total_members > 0 ? ($under_review_count / $total_members) * 100 : 0;
            $suspended_members_percentage = $total_members > 0 ? ($suspended_members_count / $total_members) * 100 : 0;

            // ====== Recent Lists ======
            $recent_applications = Institution::with('user:id,name,email')->latest()->take(5)->get();
            $recent_transactions = Charges::with('member:id,name,email')->latest()->take(5)->get();

            // ====== Response Data ======
            $data = [
                'totals' => [
                    'institutions' => $total_institutions,
                    'institutions_percentage_increase' => round($total_institutions_percentage_increase, 2),
                    'active_members' => $active_members,
                    'active_members_percentage_increase' => round($active_members_percentage_increase, 2),
                    'pending_applications' => $total_pending_application,
                    'pending_applications_percentage_increase' => round($total_pending_application_percentage_increase, 2),
                    'revenue' => $total_revenue,
                    'revenue_percentage_increase' => round($total_revenue_percentage_increase, 2),
                ],
                'status_percentages' => [
                    'active_members_percentage' => round($active_members_percentage, 2),
                    'pending_approvals_percentage' => round($pending_approvals_percentage, 2),
                    'under_review_percentage' => round($under_review_percentage, 2),
                    'suspended_members_percentage' => round($suspended_members_percentage, 2),
                ],
                'recent' => [
                    'applications' => $recent_applications,
                    'transactions' => $recent_transactions,
                ]
            ];

            return response()->json(['data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch dashboard data.',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    //Admin membership dashboard
    /**
     *   @OA\Get(
     *      path="/api/v1/admin/memberships",
     *      operationId="getMembershipData",
     *      summary="Get admin membership data",
     *      tags={"Admin - Dashboard"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(response=200, description="Membership data retrieved successfully"),
     *      @OA\Response(response=500, description="Internal server error")
     * * )
     */
    public function memberships()
    {
        try {
            // ====== Date Ranges for Percentage Increase ======
            $twoMonthsAgoStart = now()->subMonths(2)->startOfMonth();
            $twoMonthsAgoEnd   = now()->subMonths(2)->endOfMonth();
            $lastMonthStart    = now()->subMonth()->startOfMonth();
            $lastMonthEnd      = now()->subMonth()->endOfMonth();

            // ====== Current Counts ======
            $total_applications = Institution::count();
            $total_approved_members = Institution::where('is_approved', 1)->count();
            $pending_members = Institution::where('status', 'pending')->count();
            $total_rejected = Institution::where('status', 'rejected')->count();

            // ====== Previous Month Counts ======
            $total_applications_last_month = Institution::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
            $total_applications_two_months_ago = Institution::whereBetween('created_at', [$twoMonthsAgoStart, $twoMonthsAgoEnd])->count();

            $approved_last_month = Institution::where('is_approved', 1)
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
                ->count();
            $approved_two_months_ago = Institution::where('is_approved', 1)
                ->whereBetween('created_at', [$twoMonthsAgoStart, $twoMonthsAgoEnd])
                ->count();

            $pending_last_month = Institution::where('status', 'pending')
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
                ->count();
            $pending_two_months_ago = Institution::where('status', 'pending')
                ->whereBetween('created_at', [$twoMonthsAgoStart, $twoMonthsAgoEnd])
                ->count();

            $rejected_last_month = Institution::where('status', 'rejected')
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
                ->count();
            $rejected_two_months_ago = Institution::where('status', 'rejected')
                ->whereBetween('created_at', [$twoMonthsAgoStart, $twoMonthsAgoEnd])
                ->count();

            // ====== Percentage Increase Calculation ======
            $calcPercentage = function ($current, $previous) {
                if ($previous == 0) {
                    return $current > 0 ? 100 : 0;
                }
                return round((($current - $previous) / $previous) * 100, 2);
            };

            $total_applications_percentage_increase = $calcPercentage($total_applications_last_month, $total_applications_two_months_ago);
            $total_approved_members_percentage_increase = $calcPercentage($approved_last_month, $approved_two_months_ago);
            $pending_members_percentage_increase = $calcPercentage($pending_last_month, $pending_two_months_ago);
            $total_rejected_percentage_increase = $calcPercentage($rejected_last_month, $rejected_two_months_ago);

            // ====== Applications Lists ======
            $pending_applications = Institution::with('user:id,name,email')
                ->where('is_approved', 0)
                ->get();

            $all_applications = Institution::with('user:id,name,email')->get();

            // ====== Data Response ======
            $data = [
                'totals' => [
                    'total_applications' => $total_applications,
                    'total_applications_percentage_increase' => $total_applications_percentage_increase,

                    'total_approved_members' => $total_approved_members,
                    'total_approved_members_percentage_increase' => $total_approved_members_percentage_increase,

                    'pending_members' => $pending_members,
                    'pending_members_percentage_increase' => $pending_members_percentage_increase,

                    'total_rejected' => $total_rejected,
                    'total_rejected_percentage_increase' => $total_rejected_percentage_increase,
                ],
                'lists' => [
                    'pending_applications' => $pending_applications,
                    'all_applications' => $all_applications,
                ]
            ];

            return response()->json(['data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => 'Unable to fetch membership data.', 'details' => $e->getMessage()],
                500
            );
        }
    }



    //Admin Institution dashboard
    /**
     * @OA\Get(
     *     path="/api/v1/admin/institutions",
     *     operationId="getInstitutionData",
     *     summary="Get admin institution data",
     *     tags={"Admin - Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Institution data retrieved successfully"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function institutions()
    {
        try {
            // ====== Date Ranges for Percentage Increase ======
            $twoMonthsAgoStart = now()->subMonths(2)->startOfMonth();
            $twoMonthsAgoEnd   = now()->subMonths(2)->endOfMonth();
            $lastMonthStart    = now()->subMonth()->startOfMonth();
            $lastMonthEnd      = now()->subMonth()->endOfMonth();

            // ====== All Institutions ======
            $total_institutions = Institution::count();
            $last_month_total   = Institution::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
            $two_months_total   = Institution::whereBetween('created_at', [$twoMonthsAgoStart, $twoMonthsAgoEnd])->count();
            $total_institutions_percentage_increase = $two_months_total > 0
                ? (($last_month_total - $two_months_total) / $two_months_total) * 100
                : ($last_month_total > 0 ? 100 : 0);

            // ====== Federal ======
            $total_federal_institutions = Institution::where('category_type', 'federal')->count();
            $last_month_federal         = Institution::where('category_type', 'federal')
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
            $two_months_federal         = Institution::where('category_type', 'federal')
                ->whereBetween('created_at', [$twoMonthsAgoStart, $twoMonthsAgoEnd])->count();
            $total_federal_institutions_percentage_increase = $two_months_federal > 0
                ? (($last_month_federal - $two_months_federal) / $two_months_federal) * 100
                : ($last_month_federal > 0 ? 100 : 0);

            // ====== State ======
            $total_state_institutions = Institution::where('category_type', 'state')->count();
            $last_month_state         = Institution::where('category_type', 'state')
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
            $two_months_state         = Institution::where('category_type', 'state')
                ->whereBetween('created_at', [$twoMonthsAgoStart, $twoMonthsAgoEnd])->count();
            $total_state_institutions_percentage_increase = $two_months_state > 0
                ? (($last_month_state - $two_months_state) / $two_months_state) * 100
                : ($last_month_state > 0 ? 100 : 0);

            // ====== Unit ======
            $total_unit_institutions = Institution::where('category_type', 'unit')->count();
            $last_month_unit         = Institution::where('category_type', 'unit')
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
            $two_months_unit         = Institution::where('category_type', 'unit')
                ->whereBetween('created_at', [$twoMonthsAgoStart, $twoMonthsAgoEnd])->count();
            $total_unit_institutions_percentage_increase = $two_months_unit > 0
                ? (($last_month_unit - $two_months_unit) / $two_months_unit) * 100
                : ($last_month_unit > 0 ? 100 : 0);

            // ====== Members List ======
            $members_list = Institution::with('user:id,name,email')->latest()->get();

            $data = [
                'total_institutions' => $total_institutions,
                'total_institutions_percentage_increase' => round($total_institutions_percentage_increase, 2),

                'total_federal_institutions' => $total_federal_institutions,
                'total_federal_institutions_percentage_increase' => round($total_federal_institutions_percentage_increase, 2),

                'total_state_institutions' => $total_state_institutions,
                'total_state_institutions_percentage_increase' => round($total_state_institutions_percentage_increase, 2),

                'total_unit_institutions' => $total_unit_institutions,
                'total_unit_institutions_percentage_increase' => round($total_unit_institutions_percentage_increase, 2),

                'members_list' => $members_list
            ];

            return response()->json(['data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to fetch institution data.', 'message' => $e->getMessage()], 500);
        }
    }


    //Admin Application review (get single pending application)
    /**
     * @OA\Get(
     *     path="/api/v1/admin/applications/{user_id}",
     *     summary="Get single pending application",
     *     tags={"Admin - Applications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         description="ID of the user/institution to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Application retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="application", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */

    public function application($user_id)
    {
        try {
            $user = User::findOrFail($user_id);
            $institution = Institution::where('user_id', $user->id)->first();
            if (!$institution) {
                return response()->json(['error' => 'Institution not found for this user.'], 404);
            }
            return response()->json(['application' => $institution], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not found.'], 404);
        }
    }

    //Admin Applications dashboard
    /**
     * @OA\Post(
     *     path="/api/v1/admin/applications/{user_id}/action",
     *     summary="Approve or Reject Institution Application",
     *     tags={"Admin - Applications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         description="ID of the user/institution to act on",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"action"},
     *             @OA\Property(property="action", type="string", enum={"approve", "verifying", "reject"}, example="approve"),
     *            @OA\Property(property="user_id", type="integer", example=1),
     *            @OA\Property(property="rejection_reason", type="string", example="Incomplete documentation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Application status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Application approved successfully."),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function approveOrRejectApplication(Request $request, $user_id)
    {
        $request->validate([
            'action' => 'required|in:approve,verifying,reject',
            'user_id' => 'required|integer|exists:users,id',
            'rejection_reason' => 'nullable|string|max:255',
        ]);

        try {
            $user = User::findOrFail($user_id);
            $institution = Institution::where('user_id', $user->id)->first();

            if ($request->action === 'approve') {
                $user->is_approved = 1; // 1 means approved
                $institution->is_approved = 1;
                $institution->status = 'approved'; // Update status to approved
                $institution->rejection_reason = null;
                $message = 'Application approved successfully.';
                $adminMessage = "You approved {$user->name}'s application.";
            } else {
                $user->is_approved = 0;
                $institution->is_approved = 0;
                $institution->status = 'rejected'; // Update status to rejected
                $institution->rejection_reason = $request->rejection_reason ?? 'No reason provided';
                $message = 'Application rejected successfully.';
                $adminMessage = "You rejected {$user->name}'s application.";
            }

            $user->save();
            $institution->save();

            // Store notification for the user
            store_notification(
                $user->id,
                'Application Status Update',
                $message,
                'info',
                'application',
                Auth::id()
            );

            // Store notification for the admin
            store_notification(
                Auth::id(),
                'Application Status Update',
                $adminMessage,
                'info',
                'application',
                Auth::id()
            );

            return response()->json([
                'message' => $message,
                'user' => $user
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //Admin Financials Dashboard
    /**
     * @OA\Get(
     *     path="/api/v1/admin/financials",
     *     summary="Get financial statistics",
     *     description="Returns key financial metrics such as total revenue, levies, pending dues, success rate, and monthly revenue trends.",
     *     tags={"Admin - Dashboard"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Financial statistics retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_revenue", type="number", format="float", example=150000),
     *             @OA\Property(property="total_levies", type="number", format="float", example=45000),
     *             @OA\Property(property="pending_dues", type="number", format="float", example=25000),
     *             @OA\Property(property="succesful_payment_count", type="integer", example=120),
     *             @OA\Property(property="total_transaction_count", type="integer", example=150),
     *             @OA\Property(property="percentage_success_rate", type="number", format="float", example=80.0),
     *             @OA\Property(property="percentage_total_revenue", type="number", format="float", example=15.5),
     *             @OA\Property(property="percentage_total_levies", type="number", format="float", example=12.3),
     *             @OA\Property(property="percentage_pending_dues", type="number", format="float", example=-5.2),
     *             @OA\Property(
     *                 property="monthly_revenue",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="month", type="string", example="2025-03"),
     *                     @OA\Property(property="total", type="number", format="float", example=30000)
     *                 )
     *             ),
     *            @OA\Property(property="all_charges", type="array", @OA\Items(ref="#/components/schemas/Charges")),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function financials()
    {
        try {
            // ====== Date Ranges for Percentage Increase ======
            $twoMonthsAgoStart = now()->subMonths(2)->startOfMonth();
            $twoMonthsAgoEnd   = now()->subMonths(2)->endOfMonth();
            $lastMonthStart    = now()->subMonth()->startOfMonth();
            $lastMonthEnd      = now()->subMonth()->endOfMonth();

            // ====== Totals ======
            $total_revenue = Transaction::where('status', 'successful')->sum('amount');
            $total_levies  = Charges::where('type', 'levy')->where('status', 'paid')->sum('amount');
            $pending_dues  = Charges::where('type', 'due')->where('status', 'unpaid')->sum('amount');

            // ====== Counts ======
            $succesful_payment_count = Transaction::where('status', 'successful')->count();
            $total_transaction_count = Transaction::count();

            // ====== Percentages ======
            $percentage_success_rate = $total_transaction_count > 0
                ? round(($succesful_payment_count / $total_transaction_count) * 100, 2)
                : 0;

            // Compare last month vs two months ago
            $revenue_last_month = Transaction::where('status', 'successful')
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
                ->sum('amount');

            $revenue_two_months_ago = Transaction::where('status', 'successful')
                ->whereBetween('created_at', [$twoMonthsAgoStart, $twoMonthsAgoEnd])
                ->sum('amount');

            $levies_last_month = Charges::where('type', 'levy')->where('status', 'paid')
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
                ->sum('amount');

            $levies_two_months_ago = Charges::where('type', 'levy')->where('status', 'paid')
                ->whereBetween('created_at', [$twoMonthsAgoStart, $twoMonthsAgoEnd])
                ->sum('amount');

            $dues_last_month = Charges::where('type', 'due')->where('status', 'unpaid')
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
                ->sum('amount');

            $dues_two_months_ago = Charges::where('type', 'due')->where('status', 'unpaid')
                ->whereBetween('created_at', [$twoMonthsAgoStart, $twoMonthsAgoEnd])
                ->sum('amount');

            // Percentage changes
            $percentage_total_revenue = $revenue_two_months_ago > 0
                ? round((($revenue_last_month - $revenue_two_months_ago) / $revenue_two_months_ago) * 100, 2)
                : 0;

            $percentage_total_levies = $levies_two_months_ago > 0
                ? round((($levies_last_month - $levies_two_months_ago) / $levies_two_months_ago) * 100, 2)
                : 0;

            $percentage_pending_dues = $dues_two_months_ago > 0
                ? round((($dues_last_month - $dues_two_months_ago) / $dues_two_months_ago) * 100, 2)
                : 0;

            // ====== Graph Data for last 5 months ======
            $graph_data = [];
            for ($i = 4; $i >= 0; $i--) {
                $monthStart = now()->subMonths($i)->startOfMonth();
                $monthEnd   = now()->subMonths($i)->endOfMonth();
                $label      = $monthStart->format('M Y');

                $graph_data[] = [
                    'month'      => $label,
                    'revenue'    => Transaction::where('status', 'successful')
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->sum('amount'),
                    'levies'     => Charges::where('type', 'levy')->where('status', 'paid')
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->sum('amount'),
                    'pending_dues' => Charges::where('type', 'due')->where('status', 'unpaid')
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->sum('amount'),
                ];
            }

            $all_charges = Charges::paginate(10);

            return response()->json([
                'totals' => [
                    'total_revenue' => $total_revenue,
                    'total_levies'  => $total_levies,
                    'pending_dues'  => $pending_dues,
                ],
                'counts' => [
                    'successful_payments' => $succesful_payment_count,
                    'total_transactions'  => $total_transaction_count,
                ],
                'percentages' => [
                    'success_rate'        => $percentage_success_rate,
                    'total_revenue'       => $percentage_total_revenue,
                    'total_levies'        => $percentage_total_levies,
                    'pending_dues'        => $percentage_pending_dues,
                ],
                'graph_data' => $graph_data,
                'all_charges' => $all_charges
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error fetching financials',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v1/admin/notifications/{user_id}",
     *     operationId="getNotifications",
     *     summary="Get notifications for a user",
     *     tags={"Admin - Notifications"},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         description="ID of the user to retrieve notifications for",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Notifications retrieved successfully"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function notifications($userId)
    {
        try {
            // Fetch unread + read notifications
            $notifications = Notification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Mark unread as read
            Notification::where('user_id', $userId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            // Refresh the data so frontend sees updated values
            $notifications = Notification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['notifications' => $notifications], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to fetch notifications.'], 500);
        }
    }
}
