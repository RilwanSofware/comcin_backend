<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Get all admins except the first one (id = 1 or first created).
     */

    /**
     * @OA\Get(
     *     path="/api/v1/admin/admins",
     *     tags={"Admin - Admins"},
     *     summary="Get all admins except the first",
     *     description="Fetches a paginated list of all admins, excluding the very first created admin.",
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
     *         description="List of admins retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="admin@example.com"),
     *                     @OA\Property(property="role", type="string", example="admin"),
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
        $admins = User::where('role', 'admin')->where('id', '=!', 1)->paginate(10);

        return response()->json($admins);
    }

    /**
     * Create a new admin.
     */

    /**
     * @OA\Post(
     *     path="/api/v1/admin/admins",
     *     tags={"Admin - Admins"},
     *     summary="Create a new admin",
     *     description="Creates a new admin with name, email, password, and role.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", example="jane@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123"),
     *             @OA\Property(property="role", type="string", example="admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Admin created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=5),
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", example="jane@example.com"),
     *             @OA\Property(property="role", type="string", example="admin"),
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
            $admin = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => 'admin',
                'is_active' => 1,
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Admin created successfully',
                'admin'   => $admin,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create admin',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an admin details.
     */

    /**
     * @OA\Put(
     *     path="/api/v1/admin/admins/{id}",
     *     tags={"Admin - Admins"},
     *     summary="Update an admin",
     *     description="Updates an admin’s details such as name, email, password, role, or status.",
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
     *             @OA\Property(property="role", type="string", example="super-admin"),
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
     *             @OA\Property(property="role", type="string", example="super-admin"),
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
        $admin = User::where('role', 'admin')->findOrFail($id);

        DB::beginTransaction();
        try {
            $admin->update($request->only(['name', 'email'])); // add fields as needed
            DB::commit();
            return response()->json(['message' => 'Admin updated successfully', 'admin' => $admin]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update admin', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete an admin.
     */
    /**
     * @OA\Delete(
     *     path="/api/v1/admin/admins/{id}",
     *     tags={"Admin - Admins"},
     *     summary="Delete an admin",
     *     description="Permanently delete an admin by ID (except the first super admin).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the admin to delete",
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
        $admin = User::where('role', 'admin')->findOrFail($id);

        DB::beginTransaction();
        try {
            $admin->delete();
            DB::commit();
            return response()->json(['message' => 'Admin deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete admin', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Make an admin inactive.
     */

    /**
     * @OA\Patch(
     *     path="/api/v1/admin/admins/{id}/deactivate",
     *     tags={"Admin - Admins"},
     *     summary="Deactivate an admin",
     *     description="Mark an admin as inactive instead of deleting.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the admin to deactivate",
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
        $admin = User::where('role', 'admin')->findOrFail($id);

        DB::beginTransaction();
        try {
            $admin->is_active = 0;
            $admin->save();
            DB::commit();
            return response()->json(['message' => 'Admin deactivated successfully', 'admin' => $admin]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to deactivate admin', 'error' => $e->getMessage()], 500);
        }
    }
}
