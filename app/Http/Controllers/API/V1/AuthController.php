<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Charges;
use App\Models\Institution;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(
 *     title="Comcin API",
 *     version="1.0.0",
 *     description="API documentation for the Coalition of Micro-lending and Cooperative Institutions in Nigeria (Comcin)",
 *     @OA\Contact(
 *         email="support@comcin.ng"
 *     )
 * ),
 *  @OA\Tag(
 *     name="Auth",
 *     description="Authentication related endpoints"
 * ),
 * @OA\Tag(
 *     name="Admin",
 *     description="Admin related endpoints"
 * ),
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter token in format **Bearer &lt;token&gt;**"
 * ),
 *     security={{"bearerAuth": {}}}
 * )
 * 
 */


class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     security={},
     * 
     *     tags={"Auth"},
     *     summary="Login user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", example="admin@example.com"),
     *             @OA\Property(property="password", type="string", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/forgot-password",
     *     tags={"Auth"},
     *     summary="Request password reset",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="admin@comcin.ng")
     *         )
     *     ),
     *     @OA\Response(response=200, description="OTP sent if user exists")
     * )
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $user = User::where('email', $request->email)->first();

            if ($user) {
                // Send OTP to user
                $this->sendOtpToUser($user);
            } else {
                // Optional: log the attempt for audit/security
                Log::info("Password reset requested for non-existing email: {$request->email}");
            }

            return response()->json(['message' => 'an OTP has been sent.']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to process request.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/verify-otp",
     *     tags={"Auth"},
     *     summary="Verify OTP",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "otp"},
     *             @OA\Property(property="email", type="string", example="admin@comcin.ng"),
     *             @OA\Property(property="otp", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(response=200, description="OTP verified"),
     *     @OA\Response(response=400, description="Invalid OTP")
     * )
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string',
        ]);

        $user = User::where('email', $request->email)->where('otp', $request->otp)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        return response()->json(['message' => 'OTP verified. Proceed to reset password.']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reset-password",
     *     tags={"Auth"},
     *     summary="Reset password using OTP",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "otp", "password", "password_confirmation"},
     *             @OA\Property(property="email", type="string", example="admin@comcin.ng"),
     *             @OA\Property(property="otp", type="string", example="123456"),
     *             @OA\Property(property="password", type="string", example="newpassword"),
     *             @OA\Property(property="password_confirmation", type="string", example="newpassword")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset successful"),
     *     @OA\Response(response=400, description="Invalid data")
     * )
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'otp'                   => 'required|string',
            'password'              => 'required|confirmed|min:6',
        ]);

        $user = User::where('email', $request->email)->where('otp', $request->otp)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid email or OTP'], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'otp' => null,
        ]);

        return response()->json(['message' => 'Password reset successful']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     summary="Register a new institution and create a user account",
     *     description="Creates a user and institution record with uploaded files in a single transaction.",
     *      tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={
     *                     "full_name", "email", "password", "password_confirmation",
     *                     "institution_name", "institution_type", "date_of_establishment",
     *                     "registration_number", "regulatory_body", "operating_state",
     *                     "designation", "official_email", "phone_number",
     *                     "id_card", "certificate_of_registration", "operational_license", "payment_receipt",
     *                     "membership_agreement", "terms_agreement"
     *                 },
     *                 @OA\Property(property="full_name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                 @OA\Property(property="password", type="string", format="password", example="password123"),
     *                 @OA\Property(property="password_confirmation", type="string", format="password", example="password123"),
     *
     *                 @OA\Property(property="institution_name", type="string", example="ABC Microfinance"),
     *                 @OA\Property(property="institution_type", type="string", enum={"Microfinance", "Cooperative", "Other"}, example="Microfinance"),
     *                 @OA\Property(property="category_type", type="string", enum={"unit", "state", "federal"}, example="state"),
     *                 @OA\Property(property="date_of_establishment", type="string", format="date", example="2020-05-15"),
     *                 @OA\Property(property="registration_number", type="string", example="REG-123456"),
     *                 @OA\Property(property="regulatory_body", type="string", example="Central Bank"),
     *                 @OA\Property(property="operating_state", type="string", example="Lagos"),
     *                 @OA\Property(property="head_office", type="string", example="123 Main Street, Lagos"),
     *                 @OA\Property(property="business_operation_address", type="string", example="45 Business Rd, Abuja"),
     *                 @OA\Property(property="website_url", type="string", format="url", example="https://www.example.com"),
     *                 @OA\Property(property="descriptions", type="string", example="A cooperative offering microfinance services."),
     *
     *                 @OA\Property(property="designation", type="string", example="CEO"),
     *                 @OA\Property(property="official_email", type="string", format="email", example="ceo@abc.com"),
     *                 @OA\Property(property="phone_number", type="string", example="+2348012345678"),
     *
     *                 @OA\Property(property="id_card", type="string", format="binary"),
     *                 @OA\Property(property="institution_logo", type="string", format="binary"),
     *                 @OA\Property(property="certificate_of_registration", type="string", format="binary"),
     *                 @OA\Property(property="operational_license", type="string", format="binary"),
     *                 @OA\Property(property="constitution", type="string", format="binary"),
     *                 @OA\Property(property="latest_annual_report", type="string", format="binary"),
     *                 @OA\Property(property="letter_of_intent", type="string", format="binary"),
     *                 @OA\Property(property="board_resolution", type="string", format="binary"),
     *                 @OA\Property(property="passport_photograph", type="string", format="binary"),
     *                 @OA\Property(property="other_supporting_document", type="string", format="binary"),
     *                 @OA\Property(property="payment_receipt", type="string", format="binary", description="Optional payment receipt upload"),
     *
     *                 @OA\Property(property="membership_agreement", type="boolean", example=true),
     *                 @OA\Property(property="terms_agreement", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User and Institution created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User and Institution created successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Validation failed.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */

    public function register(Request $request)
    {
        try {

            DB::beginTransaction();
            $request->validate([
                // User account info
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'phone_number' => 'required|string',
                'designation' => 'required|string',


                // Institution info
                'institution_name' => 'required|string',
                'institution_type' => 'required|in:Microfinance,Cooperative,Other',
                'category_type' => 'nullable|in:unit,state,federal',
                'date_of_establishment' => 'required|date',
                'registration_number' => 'required|string',
                'regulatory_body' => 'required|string',
                'operating_state' => 'required|string',
                'head_office' => 'nullable|string',
                'business_operation_address' => 'nullable|string',
                'website_url' => 'nullable|string',
                'descriptions' => 'nullable|string',

                // Representative
                'id_card' => 'required|file|mimes:jpeg,png,pdf',

                // Files
                'institution_logo' => 'nullable|file|mimes:jpeg,png',

                'certificate_of_registration' => 'required|file|mimes:pdf,jpeg,png',
                'operational_license' => 'required|file|mimes:pdf,jpeg,png',
                'constitution' => 'nullable|file|mimes:pdf,jpeg,png',
                'latest_annual_report' => 'nullable|file|mimes:pdf,jpeg,png',
                'letter_of_intent' => 'nullable|file|mimes:pdf,jpeg,png',
                'board_resolution' => 'nullable|file|mimes:pdf,jpeg,png',
                'passport_photograph' => 'nullable|file|mimes:jpeg,png',
                'other_supporting_document' => 'nullable|file|mimes:pdf,jpeg,png',

                // Agreements
                'membership_agreement' => 'required|in:true,false',
                'terms_agreement' => 'required|in:true,false',

                // Optional payment receipt
                'payment_receipt' => 'nullable|file|mimes:pdf,jpeg,png',
            ]);

            $userUid = (string) Str::uuid();
            $uploadPath = public_path('uploads/' . $userUid . '/institutions');

            //if true or false retun 0 or 1
            // $request->merge([
            //     'membership_agreement' => $request->membership_agreement ? 1 : 0,
            //     'terms_agreement' => $request->terms_agreement ? 1 : 0
            // ]);

            // Normalize agreements to boolean 0/1
            $membershipAgreement = $request->membership_agreement === 'true' ? 1 : 0;
            $termsAgreement = $request->terms_agreement === 'true' ? 1 : 0;

            // Create folder if not exists
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Move uploaded files (store as paths)
            $paths = [];
            $fileFields = [
                'institution_logo' => 'logo.png',
                'certificate_of_registration' => 'certificate.pdf',
                'operational_license' => 'license.pdf',
                'constitution' => 'constitution.pdf',
                'id_card' => 'id_card.pdf',
                'latest_annual_report' => 'latest_annual_report.pdf',
                'letter_of_intent' => 'letter_of_intent.pdf',
                'board_resolution' => 'board_resolution.pdf',
                'passport_photograph' => 'passport_photo.jpg',
                'other_supporting_document' => 'other_document.pdf',
            ];

            foreach ($fileFields as $field => $filename) {
                if ($request->hasFile($field)) {
                    $request->file($field)->move($uploadPath, $filename);
                    $paths[$field] = 'uploads/' . $userUid . '/institutions/' . $filename;
                } else {
                    $paths[$field] = null;
                }
            }

            // Create user (only personal data)
            $user = User::create([
                'name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'user_uid' => $userUid,
                'role' => 'member',
                'is_active' => true,
                'is_verified' => false,
                'designation' => $request->designation,
                'phone_number' => $request->phone_number,
                'id_card' => $paths['id_card'],

            ]);

            // Create institution
            Institution::create([
                'user_id' => $user->id,
                'institution_uid' => $userUid,
                'institution_name' => $request->institution_name,
                'institution_type' => $request->institution_type,
                'category_type' => $request->category_type,
                'date_of_establishment' => $request->date_of_establishment,
                'registration_number' => $request->registration_number,
                'regulatory_body' => $request->regulatory_body,
                'operating_state' => $request->operating_state,
                'institution_logo' => $paths['institution_logo'],
                'certificate_of_registration' => $paths['certificate_of_registration'],
                'operational_license' => $paths['operational_license'],
                'constitution' => $paths['constitution'],
                'latest_annual_report' => $paths['latest_annual_report'],
                'letter_of_intent' => $paths['letter_of_intent'],
                'board_resolution' => $paths['board_resolution'],
                'passport_photograph' => $paths['passport_photograph'],
                'other_supporting_document' => $paths['other_supporting_document'],
                'membership_agreement' => $membershipAgreement,
                'terms_agreement' => $termsAgreement,
                'head_office' => $request->head_office,
                'business_operation_address' => $request->business_operation_address,
                'phone_number' => $request->phone_number,
                'website_url' => $request->website_url,
                'descriptions' => $request->descriptions,
                'is_approved' => false,
            ]);

            // Create initial charges if payment receipt provided
            $charge = $transaction = null;
            if ($request->hasFile('payment_receipt')) {
                [$charge, $transaction] = $this->createCharges(
                    $user,
                    $request->category_type,
                    null,
                    $request->file('payment_receipt')
                );
            }


            DB::commit();

            // Send email notification
            $this->sendOtpToUser($user);

            return response()->json([
                'message' => 'Institution registered successfully. Awaiting approval.',
                'user' => $user,
                'charges' => $charge,
                'transaction' => $transaction
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration failed: ' . $e->getMessage(), [
                'user' => $request->email,
                'exception' => $e
            ]);
            // Return a generic error message to avoid leaking sensitive info
            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v1/verify-email/{user_uuid}/{otp}",
     *     summary="Verify user email using UUID and OTP",
     *     description="Validates a user's email address using a UUID and OTP. Marks the account as verified if the code is valid and not expired.",
     *     tags={"Auth"},
     *     @OA\Parameter(
     *         name="user_uuid",
     *         in="path",
     *         description="The UUID of the user",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000")
     *     ),
     *     @OA\Parameter(
     *         name="otp",
     *         in="path",
     *         description="The one-time verification code sent to the user's email",
     *         required=true,
     *         @OA\Schema(type="string", example="123456")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email verified successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired verification code",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid verification code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Account already verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Account already verified")
     *         )
     *     )
     * )
     */
    public function verifyEmail($user_uuid, $otp)
    {
        // Find the user by UUID and OTP
        $user = User::where('user_uid', $user_uuid)
            ->where('otp', $otp)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid verification code'], 400);
        }

        // Check if already verified
        if ($user->is_verified) {
            return response()->json(['message' => 'Account already verified'], 409);
        }
        // Check if OTP is expired
        if ($user->otp_expires_at < now()) {
            return response()->json(['message' => 'Verification code expired'], 400);
        }
        // Clear OTP and expiry
        $user->otp = null;
        $user->otp_expires_at = null;

        // Mark user as verified
        $user->is_verified = 1;
        $user->email_verified_at = now(); // Set email verified timestamp
        $user->save();

        return response()->json(['message' => 'Email verified successfully']);
    }


    /**
     * @OA\Post(
     *     path="/api/v1/logout",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     summary="Logout user",
     *     @OA\Response(response=200, description="Logout successful"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout successful']);
    }


    private function sendOtpToUser($user)
    {
        // Generate 6-digit OTP
        $otp = rand(100000, 999999);

        // Save OTP and expiry to user
        $user->otp = $otp;
        $user->otp_expires_at = now()->addHour(); // expires in 1 hour
        $user->save();

        // Create verification link
        $verificationLink = "https://comcin.com.ng/verify/{$user->user_uid}/{$otp}";

        // Send OTP and link via email
        $emailBody = "Your OTP is: {$otp}\n\n"
            . "Click the link below to verify your email:\n"
            . "{$verificationLink}\n\n"
            . "Note: This code/link will expire in 1 hour.";

        Mail::raw($emailBody, function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Your OTP Code and Verification Link');
        });
    }

    private function createCharges($user, $category_type, $amount, $receiptFile = null)
    {
        // Determine charge amount if not passed
        if (!$amount) {
            if ($category_type === 'unit') {
                $amount = 20000;
            } elseif ($category_type === 'state') {
                $amount = 50000;
            } elseif ($category_type === 'federal') {
                $amount = 100000;
            } else {
                $amount = 0;
            }
        }

        // Create charge
        $charge = Charges::create([
            'member_id'   => $user->id,
            'title'       => 'Annual Dues',
            'description' => 'Annual Registration Due',
            'type'        => 'due',
            'amount'      => $amount,
            'status'      => 'unpaid',
            'due_date'    => now(),
            'created_by'  => $user->id,
        ]);

        // Handle receipt upload
        $receiptPath = null;
        if ($receiptFile) {
            $receiptDir = 'uploads/' . $user->user_uid . '/receipts/';
            if (!file_exists(public_path($receiptDir))) {
                mkdir(public_path($receiptDir), 0755, true);
            }
            $filename = Str::random(10) . '_' . time() . '.' . $receiptFile->getClientOriginalExtension();
            $receiptFile->move(public_path($receiptDir), $filename);
            $receiptPath = $receiptDir . $filename;
        }

        // Create transaction
        $transaction = Transaction::create([
            'member_id'    => $user->id,
            'charge_id'    => $charge->id,
            'reference'    => strtoupper(Str::random(12)),
            'amount'       => $amount,
            'status'       => 'pending',
            'method'       => 'bank_transfer',
            'narration'    => null,
            'paid_at'      => now(),
            'recorded_by'  => $user->id,
            'receipt_file' => $receiptPath,
        ]);

        // Notifications
        store_notification(
            $user->id,
            'Payment Recorded',
            'Your manual payment has been recorded successfully.',
            'success',
            'payment',
            1
        );

        store_notification(
            1,
            'Payment Recorded',
            'A manual payment has been recorded for member: ' . $user->name,
            'info',
            'payment',
            $user->id
        );

        return [$charge, $transaction];
    }
}
