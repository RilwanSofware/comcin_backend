<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class UserController extends Controller
{
     /**
     * Get all members except the first one (id = 1 or first created).
     */

    /**
     * @OA\Get(
     *     path="/api/v1/admin/members",
     *     tags={"Admin - Members"},
     *     summary="Get all members except the first",
     *     description="Fetches a paginated list of all members.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of members retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="member@example.com"),
     *                     @OA\Property(property="role", type="string", example="member"),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=42)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */

    public function index()
    {
        $members = User::where('role', 'member')
            ->orderBy('id', 'asc')
            ->skip(1) // skip the first member
            ->get();

        return response()->json($members);
    }

    /**
     * Create a new member.
     */

    /**
     * @OA\Post(
     *     path="/api/v1/admin/members",
     *     tags={"Admin - Members"},
     *     summary="Create a new member",
     *     description="Creates a new member with name, email, password, and role.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", example="jane@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123"),
     *             @OA\Property(property="role", type="string", example="member")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Admin created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=5),
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", example="jane@example.com"),
     *             @OA\Property(property="role", type="string", example="member"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        DB::beginTransaction();
        try {
            $member = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => 'member',
                'is_active' => 1,
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Admin created successfully',
                'member'   => $member,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create member',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an member details.
     */

    /**
     * @OA\Put(
     *     path="/api/v1/admin/members/{id}",
     *     tags={"Admin - Members"},
     *     summary="Update an member",
     *     description="Updates an member’s details such as name, email, password, role, or status.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Admin ID",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", example="jane_new@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="role", type="string", example="member"),
     *             @OA\Property(property="is_active", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Admin updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=5),
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", example="jane_new@example.com"),
     *             @OA\Property(property="role", type="string", example="member"),
     *             @OA\Property(property="is_active", type="boolean", example=false),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Admin not found"
     *     )
     * )
     */

    public function update(Request $request, $id)
    {
        $member = User::where('role', 'member')->findOrFail($id);

        DB::beginTransaction();
        try {
            $member->update($request->only(['name', 'email'])); // add fields as needed
            DB::commit();
            return response()->json(['message' => 'Admin updated successfully', 'member' => $member]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update member', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete an member.
     */
    /**
     * @OA\Delete(
     *     path="/api/v1/admin/members/{id}",
     *     tags={"Admin - Members"},
     *     summary="Delete an member",
     *     description="Permanently delete an member by ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the member to delete",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Admin deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Admin deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Admin not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Admin not found")
     *         )
     *     )
     * )
     */

    public function destroy($id)
    {
        $member = User::where('role', 'member')->findOrFail($id);

        DB::beginTransaction();
        try {
            $member->delete();
            DB::commit();
            return response()->json(['message' => 'Admin deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete member', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Make an member inactive.
     */

    /**
     * @OA\Patch(
     *     path="/api/v1/admin/members/{id}/deactivate",
     *     tags={"Admin - Members"},
     *     summary="Deactivate an member",
     *     description="Mark an member as inactive instead of deleting.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the member to deactivate",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Admin deactivated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Admin deactivated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Admin not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Admin not found")
     *         )
     *     )
     * )
     */

    public function deactivate($id)
    {
        $member = User::where('role', 'member')->findOrFail($id);

        DB::beginTransaction();
        try {
            $member->is_active = 0;
            $member->save();
            DB::commit();
            return response()->json(['message' => 'Admin deactivated successfully', 'member' => $member]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to deactivate member', 'error' => $e->getMessage()], 500);
        }
    }
}
