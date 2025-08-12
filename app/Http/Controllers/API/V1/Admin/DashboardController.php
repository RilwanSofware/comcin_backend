<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Notification;
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
     *    tags={"Admin Dashboard"},
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(response=200, description="Dashboard data retrieved successfully"),
     *    @OA\Response(response=500, description="Internal server error")
     *     
     * )
     */
    public function index()
    {
        try {
            // Base query for institutions
            $total_application = Institution::count();
            $total_pending = Institution::where('is_approved', 0)->count();
            $total_approved = Institution::where('is_approved', 1)->count();
            $total_rejected = Institution::where('is_approved', 2)->count();

            // Pending applications with user details
            $pending_applications = Institution::where('is_approved', 0)
                ->with('user:id,name,email') // Assuming Institution has user() relation
                ->get();

            // Percentage calculations
            $total_institutions = Institution::count();
            $pending_percentage = $total_institutions > 0 ? ($total_pending / $total_institutions) * 100 : 0;
            $approved_percentage = $total_institutions > 0 ? ($total_approved / $total_institutions) * 100 : 0;
            $rejected_percentage = $total_institutions > 0 ? ($total_rejected / $total_institutions) * 100 : 0;

            $data = [
                'total_application' => $total_application,
                'total_pending' => $total_pending,
                'total_approved' => $total_approved,
                'total_rejected' => $total_rejected,
                'pending_applications' => $pending_applications,
                'pending_percentage' => round($pending_percentage, 2),
                'approved_percentage' => round($approved_percentage, 2),
                'rejected_percentage' => round($rejected_percentage, 2),
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
     *      tags={"Admin Dashboard"},
     *      @OA\Response(response=200, description="Membership data retrieved successfully"),
     *      @OA\Response(response=500, description="Internal server error")
     * * )
     */
    public function membership()
    {
        try {
            $total_members = User::where('role', 'member')->count();
            $total_pending = User::where('role', 'member')->where('is_approved', 0)->count();
            $total_approved = User::where('role', 'member')->where('is_approved', 1)->count();
            $total_rejected = User::where('role', 'member')->where('is_approved', 2)->count();
            $pending_applications = User::where('role', 'member')->where('is_approved', 0)->get();
            $data = [
                'total_members' => $total_members,
                'total_pending' => $total_pending,
                'total_approved' => $total_approved,
                'total_rejected' => $total_rejected,
                'pending_applications' => $pending_applications,
            ];
            return response()->json(['data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => 'Unable to fetch membership data.'],
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
     *     tags={"Admin Dashboard"},
     *     @OA\Response(response=200, description="Institution data retrieved successfully"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function institution()
    {
        try {
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to fetch institution data.'], 500);
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
            return response()->json(['application' => $user], 200);
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
     *             @OA\Property(property="action", type="string", enum={"approve", "reject"}, example="approve"),
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
            'action' => 'required|in:approve,reject',
            'user_id' => 'required|integer|exists:users,id',
            'rejection_reason' => 'nullable|string|max:255',
        ]);

        try {
            $user = User::findOrFail($user_id);

            if ($request->action === 'approve') {
                $user->is_approved = 1; // 1 means approved
                $user->rejection_reason = null;
                $message = 'Application approved successfully.';
                $adminMessage = "You approved {$user->name}'s application.";
            } else {
                $user->is_approved = 2; // 2 means rejected
                $user->rejection_reason = $request->rejection_reason ?? 'No reason provided';
                $message = 'Application rejected successfully.';
                $adminMessage = "You rejected {$user->name}'s application.";
            }

            $user->save();

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
            $notifications = Notification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Mark notifications as read
            Notification::where('user_id', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json(['notifications' => $notifications], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to fetch notifications.'], 500);
        }
    }
}
