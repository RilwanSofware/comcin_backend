<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteContent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    /**
     * General Settings (Get/Update)
     */

    /**
     * @OA\Get(
     *     path="/api/v1/aadmin/settings/general",
     *     tags={"Admin - Settings"},
     *     summary="Get general settings",
     *     description="Retrieve general settings such as organization name, logo, contact email, and phone number.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="General settings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="General settings retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="organization_name", type="string", example="Comcin"),
     *                 @OA\Property(property="logo", type="string", example="uploads/settings/logo.png"),
     *                 @OA\Property(property="contact_email", type="string", example="info@comcin.org"),
     *                 @OA\Property(property="phone_number", type="string", example="+2348000000000")
     *             )
     *         )
     *     )
     * )
     *
     * @OA\Post(
     *     path="/api/v1/admin/settings/general",
     *     tags={"Admin - Settings"},
     *     summary="Update general settings",
     *     description="Update general settings such as organization name, logo, contact email, and phone number.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"organization_name", "contact_email", "phone_number"},
     *             @OA\Property(property="organization_name", type="string", example="Comcin"),
     *             @OA\Property(property="logo", type="string", format="binary", description="Logo file (jpg, jpeg, png)"),
     *             @OA\Property(property="contact_email", type="string", format="email", example="info@comcin.org"),
     *             @OA\Property(property="phone_number", type="string", example="+2348000000000")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="General settings updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="General settings updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    public function updateGeneral(Request $request)
    {
        if ($request->isMethod('get')) {
            $settings = WebsiteContent::where('section', 'general')->pluck('value', 'key');
            return response()->json([
                'status' => true,
                'message' => 'General settings retrieved successfully',
                'data' => $settings
            ]);
        }

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'organization_name' => 'required|string|max:255',
                'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'contact_email' => 'required|email',
                'phone_number' => 'required|string|max:20',
            ]);

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $filename = Str::random(10) . '.' . $request->logo->getClientOriginalExtension();
                $request->logo->move(public_path('uploads/settings'), $filename);
                $data['logo'] = 'uploads/settings/' . $filename;
            }

            foreach ($data as $key => $value) {
                if ($key === 'logo') {
                    $this->saveSetting('general', $key, null, $value);
                } else {
                    $this->saveSetting('general', $key, $value);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'General settings updated successfully'
            ], 200);
        }
    }

    /**
     * Security Settings (Get/Update)
     */

    /**
     * @OA\Get(
     *     path="/api/v1/admin/settings/security",
     *     tags={"Admin - Settings"},
     *     summary="Get Security Settings",
     *     description="Retrieve current security settings (2FA, password length, alert on failed attempt).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Security settings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="enable_2fa", type="boolean", example=true),
     *             @OA\Property(property="min_password_length", type="integer", example=8),
     *             @OA\Property(property="alert_on_failed_attempt", type="boolean", example=true)
     *         )
     *     )
     * )
     *
     * @OA\Post(
     *     path="/api/v1/admin/settings/security",
     *     tags={"Admin - Settings"},
     *     summary="Update Security Settings",
     *     description="Update security preferences such as 2FA, password length, and failed attempt alerts.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="enable_2fa", type="boolean", example=true),
     *             @OA\Property(property="min_password_length", type="integer", example=10),
     *             @OA\Property(property="alert_on_failed_attempt", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Security settings updated successfully"
     *     )
     * )
     */


    public function updateSecurity(Request $request)
    {
        if ($request->isMethod('get')) {
            $settings = WebsiteContent::where('section', 'security')->pluck('value', 'key');
            return response()->json([
                'status' => true,
                'message' => 'Security settings retrieved successfully',
                'data' => $settings
            ]);
        }

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'enable_2fa' => 'required|boolean',
                'min_password_length' => 'required|integer|min:6',
                'alert_failed_attempt' => 'required|boolean',
            ]);

            foreach ($data as $key => $value) {
                $this->saveSetting('security', $key, $value);
            }

            return response()->json([
                'status' => true,
                'message' => 'Security settings updated successfully'
            ], 200);
        }
    }

    /**
     * Notification Settings (Get/Update)
     */

    /**
     * @OA\Get(
     *     path="/api/v1/admin/settings/notifications",
     *     tags={"Admin - Settings"},
     *     summary="Get Notification Preferences",
     *     description="Retrieve notification preferences such as new member alerts, payment confirmations, and compliance reminders.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Notification preferences retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="new_member", type="boolean", example=true),
     *             @OA\Property(property="payment_confirmation", type="boolean", example=true),
     *             @OA\Property(property="compliance_reminder", type="boolean", example=false)
     *         )
     *     )
     * )
     *
     * @OA\Post(
     *     path="/api/v1/admin/settings/notifications",
     *     tags={"Admin - Settings"},
     *     summary="Update Notification Preferences",
     *     description="Update preferences for receiving system notifications.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="new_member", type="boolean", example=true),
     *             @OA\Property(property="payment_confirmation", type="boolean", example=false),
     *             @OA\Property(property="compliance_reminder", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification preferences updated successfully"
     *     )
     * )
     */

    public function updateNotification(Request $request)
    {
        if ($request->isMethod('get')) {
            $settings = WebsiteContent::where('section', 'notification')->pluck('value', 'key');
            return response()->json([
                'status' => true,
                'message' => 'Notification settings retrieved successfully',
                'data' => $settings
            ]);
        }

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'new_member' => 'required|boolean',
                'payment_confirmation' => 'required|boolean',
                'compliance_reminder' => 'required|boolean',
            ]);

            foreach ($data as $key => $value) {
                $this->saveSetting('notification', $key, $value);
            }

            return response()->json([
                'status' => true,
                'message' => 'Notification settings updated successfully'
            ], 200);
        }
    }

    /**
     * Super Admin Settings (profile from User model)
     */

    /**
     * @OA\Get(
     *     path="/api/v1/admin/settings/super-admin",
     *     tags={"Admin - Settings"},
     *     summary="Get Super Admin Settings",
     *     description="Retrieve Super Admin details like profile picture, email, and password update settings.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Super Admin settings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="profile_picture", type="string", example="uploads/profile/admin123.png"),
     *             @OA\Property(property="email", type="string", example="admin@example.com")
     *         )
     *     )
     * )
     *
     * @OA\Post(
     *     path="/api/v1/admin/settings/super-admin",
     *     tags={"Admin - Settings"},
     *     summary="Update Super Admin Settings",
     *     description="Update Super Admin profile picture, email, or password.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="profile_picture", type="string", format="binary"),
     *                 @OA\Property(property="email", type="string", example="newadmin@example.com"),
     *                 @OA\Property(property="password", type="string", example="NewPassword123!"),
     *                 @OA\Property(property="password_confirmation", type="string", example="NewPassword123!")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Super Admin settings updated successfully"
     *     )
     * )
     */

    public function updateSuperAdmin(Request $request)
    {
        $user = Auth::user();
        $user = User::find($user->id);

        if ($request->isMethod('get')) {
            return response()->json([
                'status' => true,
                'message' => 'Super admin profile retrieved successfully',
                'data' => $user
            ]);
        }

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'profile_pic' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'email' => 'nullable|email|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:6|confirmed',
            ]);

            if ($request->hasFile('profile_pic')) {
                $filename = Str::random(10) . '.' . $request->profile_pic->getClientOriginalExtension();
                $request->profile_pic->move(public_path('uploads/profile'), $filename);
                $data['profile_pic'] = 'uploads/profile/' . $filename;
            }

            if (!empty($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            }

            $user->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Super admin profile updated successfully'
            ], 200);
        }
    }

    /**
     * Save setting helper
     */
    private function saveSetting($section, $key, $value = null, $media = null)
    {
        $user = Auth::user();
        WebsiteContent::updateOrCreate(
            ['section' => $section, 'key' => $key],
            ['value' => $value, 'media' => $media, 'updated_by' => $user->id]
        );
    }
}
