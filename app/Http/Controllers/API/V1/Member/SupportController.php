<?php

namespace App\Http\Controllers\API\V1\Member;

use App\Http\Controllers\Controller;
use App\Models\Support;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupportController extends Controller
{
    //get all support tickets
    /**
     * @OA\Get(
     *   path="/api/v1/member/support/tickets",
     *   summary="Get All Support Tickets",
     *   description="Retrieves all support tickets for the authenticated member.",
     *   tags={"Member - Support Tickets"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Response(
     *       response=200,
     *       description="Successful retrieval of support tickets."
     *   ),
     *   @OA\Response(
     *       response=401,
     *       description="Unauthorized"
     *   )
     * )
     */
    public function getAllSupportTickets(Request $request)
    {
        $user = Auth::user();

        $supportTickets = Support::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($supportTickets, 200);
    }

    //create a support ticket
    /**
     * @OA\Post(
     *   path="/api/v1/member/support/tickets",
     *   summary="Create Support Ticket",
     *   description="Creates a new support ticket for the authenticated member.",
     *   tags={"Member - Support Tickets"},
     *  security={{"bearerAuth": {}}},
     *  @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *             mediaType="multipart/form-data",
     *            @OA\Schema(
     *               required={"subject", "message"},
     *              @OA\Property(property="subject", type="string", example="Issue with Membership"),
     *             @OA\Property(property="message", type="string", example="I am having issues with my membership renewal."),
     *             @OA\Property(property="attachment", type="string", format="binary")
     *            )
     *      )
     * *  ),
     *   @OA\Response(
     *       response=201,
     *       description="Support ticket created successfully."
     *   ),
     *  @OA\Response(
     *      response=400,
     *      description="Bad Request - Validation errors or missing fields."
     *  ),
     *  @OA\Response(
     *      response=401,
     *     description="Unauthorized - User not authenticated."
     *  )
     * )
     * */
    public function createSupportTicket(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', // 2MB max
        ]);

        DB::beginTransaction();
        try {
            // Handle file upload
            $attachmentPath = 'uploads/' . $user->user_uid . '/supports/';
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = Str::random(10) . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path($attachmentPath), $filename);
                $attachmentPath = $attachmentPath . $filename;
            }

            // Create the support ticket
            $ticket = Support::create([
                'uuid'       => Str::uuid(),
                'user_id'    => $user ? $user->id : null,
                'name'       => $user ? $user->name : $request->input('name'),
                'email'      => $user ? $user->email : $request->input('email'),
                'subject'    => $request->input('subject'),
                'message'    => $request->input('message'),
                'attachment' => $attachmentPath,
                'status'     => 'pending',
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Support ticket created successfully.',
                'ticket'  => $ticket
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create support ticket.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    //get a support ticket
    /**
     * @OA\Get(
     *  path="/api/v1/member/support/tickets/{uuid}",
     * summary="Get Support Ticket",
     * description="Retrieves a specific support ticket by UUID for the authenticated member.",
     * tags={"Member - Support Tickets"},
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     *     name="uuid",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string"),   
     *    description="The UUID of the support ticket."
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Successful retrieval of the support ticket."
     * ),
     * @OA\Response(
     *     response=404,
     *     description="Support ticket not found."
     * ),
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized - User not authenticated."
     * )
     * )
     */
    public function getSupportTicket(Request $request, $uuid)
    {
        $user = Auth::user();
        $ticket = Support::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->first();
        if (!$ticket) {
            return response()->json(['message' => 'Support ticket not found.'], 404);
        }
        return response()->json($ticket, 200);
    }
}
