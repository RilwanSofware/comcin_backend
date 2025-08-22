<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Support;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportController extends Controller
{
    /**
     * View all support tickets (paginated)
     */
    /**
     * @OA\Get(
     *     path="/api/v1/admin/support-tickets",
     *     summary="Get all support tickets with stats",
     *     description="Retrieve all support tickets with pagination and statistics (counts and weekly growth).",
     *     tags={"Admin - Support Tickets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Support tickets retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Support tickets retrieved successfully"),
     *             @OA\Property(property="stats", type="object",
     *                 @OA\Property(property="total", type="integer", example=120),
     *                 @OA\Property(property="resolved", type="integer", example=50),
     *                 @OA\Property(property="pending", type="integer", example=60),
     *                 @OA\Property(property="cancelled", type="integer", example=10)
     *             ),
     *             @OA\Property(property="growth", type="object",
     *                 @OA\Property(property="total", type="integer", example=+3),
     *                 @OA\Property(property="resolved", type="integer", example=+1),
     *                 @OA\Property(property="pending", type="integer", example=+2),
     *                 @OA\Property(property="cancelled", type="integer", example=0)
     *             ),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            // Stats
            $total_ticket = Support::count();
            $resolved_ticket = Support::where('status', 'resolved')->count();
            $pending_ticket = Support::where('status', 'pending')->count();
            $cancelled_ticket = Support::where('status', 'cancelled')->count();

            // Growth (last 7 days)
            $weekStart = now()->subDays(7);

            $total_growth = Support::where('created_at', '>=', $weekStart)->count();
            $resolved_growth = Support::where('status', 'resolved')->where('created_at', '>=', $weekStart)->count();
            $pending_growth = Support::where('status', 'pending')->where('created_at', '>=', $weekStart)->count();
            $cancelled_growth = Support::where('status', 'cancelled')->where('created_at', '>=', $weekStart)->count();

            // Tickets with relation
            $tickets = Support::with('user:id,name,email')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'status' => true,
                'message' => 'Support tickets retrieved successfully',
                'stats' => [
                    'total' => $total_ticket,
                    'resolved' => $resolved_ticket,
                    'pending' => $pending_ticket,
                    'cancelled' => $cancelled_ticket,
                ],
                'growth' => [
                    'total' => $total_growth,
                    'resolved' => $resolved_growth,
                    'pending' => $pending_growth,
                    'cancelled' => $cancelled_growth,
                ],
                'data' => $tickets
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * View a single support ticket by ID
     */

    /**
     * @OA\Get(
     *      path="/api/v1/admin/support-tickets/{id}",
     *     tags={"Admin - Support Tickets"},
     *     summary="View a single support ticket",
     *     description="Retrieve details of a specific support ticket by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Support ticket ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Support ticket retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Support ticket retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Login issue"),
     *                 @OA\Property(property="description", type="string", example="I cannot login to my account"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-08-15T12:34:56Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-08-16T08:45:23Z"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="johndoe@example.com")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Support ticket not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No query results for model [Support] 1")
     *         )
     *     )
     * )
     */

    public function show($id)
    {
        $ticket = Support::with('user:id,name,email')->findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'Support ticket retrieved successfully',
            'data' => $ticket
        ]);
    }

    /**
     * Approve a support ticket
     */

    /**
     * @OA\Post(
     *     path="/api/v1/admin/support-tickets/{id}/action",
     *     summary="Approve or Reject a Support Ticket",
     *     description="Allows an admin to approve or reject a support ticket. No rejection reason is required.",
     *      tags={"Admin - Support Tickets"},
     *     security={{"bearerAuth":{}}},
     * 
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Support Ticket ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     * 
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"action"},
     *             @OA\Property(
     *                 property="action",
     *                 type="string",
     *                 enum={"approve","reject"},
     *                 description="Action to perform on the ticket",
     *                 example="approve"
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="Ticket status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ticket approved successfully."),
     *             @OA\Property(property="ticket", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="approved"),
     *                 @OA\Property(property="user_id", type="integer", example=10)
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=400,
     *         description="Invalid action",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid action provided.")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=404,
     *         description="Ticket not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Support ticket not found.")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Something went wrong.")
     *         )
     *     )
     * )
     */

    public function approveOrRejectTicket(Request $request, $ticket_id)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        try {
            $ticket = Support::findOrFail($ticket_id);

            if ($request->action === 'approve') {
                $ticket->status = 'approved';
                $message = 'Ticket approved successfully.';
                $adminMessage = "You approved ticket #{$ticket->id}.";
            } else {
                $ticket->status = 'rejected';
                $message = 'Ticket rejected successfully.';
                $adminMessage = "You rejected ticket #{$ticket->id}.";
            }

            $ticket->save();

            // Notify the ticket owner
            store_notification(
                $ticket->user_id,
                'Support Ticket Status Update',
                $message,
                'info',
                'support_ticket',
                Auth::id()
            );

            // Notify the admin (optional)
            store_notification(
                Auth::id(),
                'Support Ticket Status Update',
                $adminMessage,
                'info',
                'support_ticket',
                Auth::id()
            );

            return response()->json([
                'message' => $message,
                'ticket' => $ticket
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
