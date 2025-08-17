<?php

namespace App\Http\Controllers\API\V1\Member;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Charges;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{

    /**
     * Display the member dashboard.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * @OA\Get(
     *   path="/api/v1/member/dashboard",
     *  summary="Get Member Dashboard",
     *  description="Fetches the member's dashboard data including user and institution information.",
     *  tags={"Member Dashboard"},
     *  security={{"bearerAuth":{}}},
     *  @OA\Response(
     *     response=200,
     *     description="Successful retrieval of member dashboard data",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
     *       @OA\Property(property="institution", type="object", ref="#/components/schemas/Institution"),
     *       @OA\Property(property="pending_charges_count", type="integer", example=2),
     *       @OA\Property(property="next_payment_charges", type="array", @OA\Items(ref="#/components/schemas/Charges")),
     *       @OA\Property(property="certificate_count", type="integer", example=1),
     *       @OA\Property(property="latest_certificate", type="object", ref="#/components/schemas/Certificate"),
     *       @OA\Property(property="pending_charges", type="array", @OA\Items(ref="#/components/schemas/Charges"))
     *     )
     * ),
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized, user must be authenticated"
     * ),
     * @OA\Response(
     *     response=500,
     *     description="Internal server error"
     * )
     * )
     *  
     * */
    public function index()
    {
        $user = Auth::user();

        //get Institution
        $user->institution;
        $user->charges;


        $pending_charges_count = Charges::where('member_id', $user->id)
            ->where('status', 'unpaid')
            ->count();

        $next_payment_charges = Charges::where('member_id', $user->id)
            ->where('status', 'unpaid')
            ->orderBy('due_date', 'asc')
            ->get();

        $certificate_count = Certificate::where('member_id', $user->id)->count();
        // Get the latest certificate
        $latest_certificate = Certificate::where('member_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();


        $pending_charges = Charges::where('member_id', $user->id)
            ->where('status', 'unpaid')
            ->get();

        // Logic to retrieve member-specific dashboard data
        $data = [
            'user' => $user,
            'pending_charges_count' => $pending_charges_count,
            'next_payment_charges' => $next_payment_charges,
            'certificate_count' => $certificate_count,
            'latest_certificate' => $latest_certificate,
            'pending_charges' => $pending_charges,
        ];

        return response()->json($data);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/member/institution",
     *  summary="Get Member Institution",
     *  description="Fetches the institution information of the authenticated member.",
     *  tags={"Member Dashboard"},
     *  security={{"bearerAuth":{}}},
     *  @OA\Response(
     *     response=200,
     *     description="Successful retrieval of institution data",
     *     @OA\JsonContent(ref="#/components/schemas/Institution")
     * ),
     * @OA\Response(
     *     response=404,
     *     description="Institution not found"
     * ),
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized, user must be authenticated"
     * ),
     * @OA\Response(
     *     response=500,
     *     description="Internal server error"
     * )
     * )
     */
    public function institution()
    {
        $user = Auth::user();
        $institution = $user->institution;

        if (!$institution) {
            return response()->json(['message' => 'Institution not found'], 404);
        }

        return response()->json($institution);
    }

    /**
     * @OA\Get(
     * path="/api/v1/member/edit-institution",
     * summary="Edit Member Institution",
     * description="Allows the authenticated member to edit their institution information.",
     * tags={"Member Dashboard"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     *     response=200,
     *     description="Successful update of institution information",
     *    @OA\JsonContent(
     *      type="object",
     *     @OA\Property(property="message", type="string", example="Institution updated successfully"),
     *     @OA\Property(property="institution", type="object", ref="#/components/schemas/Institution")
     *    )
     * ),
     * @OA\Response(
     *     response=400,
     *     description="Validation error, invalid input data"
     * ),
     * @OA\Response(
     *     response=404,
     *    description="Institution not found"
     * ),
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized, user must be authenticated"
     * ),
     * @OA\Response(
     *     response=500,
     *     description="Internal server error"
     * )
     * )
     */

    public function editInstitution(Request $request)
    {
        $user = Auth::user();
        $userUid = $user->uid;
        $institution = $user->institution;

        try {


            //edit every field in the institution
            DB::beginTransaction();
            $request->validate([

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
                'website_url' => 'nullable|url',
                'descriptions' => 'nullable|string',

                // Representative
                'id_card' => 'required|file|mimes:jpeg,png,pdf',

                // Files
                'institution_logo' => 'nullable|file|mimes:jpeg,png',
                'certificate_of_registration' => 'required|file|mimes:pdf,jpeg,png',
                'operational_license' => 'required|file|mimes:pdf,jpeg,png',
                'constitution' => 'required|file|mimes:pdf,jpeg,png',
                'latest_annual_report' => 'nullable|file|mimes:pdf,jpeg,png',
                'letter_of_intent' => 'nullable|file|mimes:pdf,jpeg,png',
                'board_resolution' => 'nullable|file|mimes:pdf,jpeg,png',
                'passport_photograph' => 'nullable|file|mimes:jpeg,png',
                'other_supporting_document' => 'nullable|file|mimes:pdf,jpeg,png',


            ]);

            $uploadPath = public_path('uploads/' . $userUid . '/institutions');
            // Move uploaded files (store as paths)
            $paths = [];
            $fileFields = [
                'institution_logo' => 'logo.png',
                'certificate_of_registration' => 'certificate.pdf',
                'operational_license' => 'license.pdf',
                'constitution' => 'constitution.pdf',
                'id_card' => 'id_card.png',
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

            // Update institution
            $institution->update([
                'user_id' => $user->id,
                'institution_uid' => (string) Str::uuid(),
                'institution_name' => $request->institution_name,
                'institution_type' => $request->institution_type,
                'category_type' => $request->category_type,
                'date_of_establishment' => $request->date_of_establishment,
                'registration_number' => $request->registration_number,
                'regulatory_body' => $request->regulatory_body,
                'operating_state' => $request->operating_state,
                'institution_logo' => $paths['institution_logo'],
                'id_card' => $paths['id_card'],
                'certificate_of_registration' => $paths['certificate_of_registration'],
                'operational_license' => $paths['operational_license'],
                'constitution' => $paths['constitution'],
                'latest_annual_report' => $paths['latest_annual_report'],
                'letter_of_intent' => $paths['letter_of_intent'],
                'board_resolution' => $paths['board_resolution'],
                'passport_photograph' => $paths['passport_photograph'],
                'other_supporting_document' => $paths['other_supporting_document'],
                'membership_agreement' => $request->membership_agreement,
                'terms_agreement' => $request->terms_agreement,
                'head_office' => $request->head_office,
                'business_operation_address' => $request->business_operation_address,
                'phone_number' => $request->phone_number,
                'website_url' => $request->website_url,
                'descriptions' => $request->descriptions,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Institution updated successfully',
                'institution' => $institution,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error updating institution: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *   path="/api/v1/member/financials",
     *  summary="Get Member Financials",
     *  description="Fetches the financial information of the authenticated member including pending and paid charges.",
     *  tags={"Member Dashboard"},
     *  security={{"bearerAuth":{}}},
     *  @OA\Response(
     *     response=200,
     *     description="Successful retrieval of financial data",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="pending_charges", type="array", @OA\Items(ref="#/components/schemas/Charges")),
     *       @OA\Property(property="paid_charges", type="array", @OA\Items(ref="#/components/schemas/Charges")),
     *       @OA\Property(property="total_pending_amount", type="number", format="float", example=100.00),
     *       @OA\Property(property="total_paid_amount", type="number", format="float", example=200.00)
     *     )
     * ),
     * @OA\Response(
     *     response=404,
     *     description="Institution not found"
     * ),
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized, user must be authenticated"
     * ),
     * @OA\Response(
     *     response=500,
     *     description="Internal server error"
     * )
     * )
     */
    public function financials()
    {
        $user = Auth::user();
        $institution = $user->institution;

        if (!$institution) {
            return response()->json(['message' => 'Institution not found'], 404);
        }

        $pending_charges = Charges::where('member_id', $user->id)
            ->where('status', 'unpaid')
            ->get();

        $paid_charges = Charges::where('member_id', $user->id)
            ->where('status', 'paid')
            ->get();

        $data = [
            'pending_charges' => $pending_charges,
            'paid_charges' => $paid_charges,
            'total_pending_amount' => $pending_charges->sum('amount'),
            'total_paid_amount' => $paid_charges->sum('amount'),
        ];


        return response()->json($data);
    }

    /**
     * @OA\Get(
     *  path="/api/v1/member/certificates",
     * summary="Get Member Certificates",
     * description="Fetches the certificates associated with the authenticated member.",
     * tags={"Member Dashboard"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     *     response=200,
     *     description="Successful retrieval of certificates",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(ref="#/components/schemas/Certificate")
     *     )
     * ),
     * @OA\Response(
     *     response=404,
     *     description="No certificates found"
     * ),
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized, user must be authenticated"
     * ),
     * @OA\Response(
     *     response=500,
     *     description="Internal server error"
     * )
     * )
     */
    public function certificates()
    {
        $user = Auth::user();
        $certificates = Certificate::where('member_id', $user->id)->get();

        if ($certificates->isEmpty()) {
            return response()->json(['message' => 'No certificates found'], 404);
        }

        return response()->json($certificates);
    }

    /**
     * @OA\Get(
     * path="/api/v1/member/notifications",
     * summary="Get Member Notifications",
     * description="Fetches the notifications for the authenticated member.",
     * tags={"Member Dashboard"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     *     response=200,
     *     description="Successful retrieval of notifications",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Notification"))
     * ),
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized, user must be authenticated"
     * ),
     * @OA\Response(
     *     response=500,
     *     description="Internal server error"
     * )
     * )
     */
    public function notifications()
    {
        $user = Auth::user();


        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    /**
     * @OA\Post(
     * path="/api/v1/member/notifications/mark-as-read",
     * summary="Mark Notification as Read",
     * description="Marks a notification as read for the authenticated member.",
     * tags={"Member Dashboard"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         required={"notification_id"},
     *         @OA\Property(property="notification_id", type="integer", example=1)
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Notification marked as read successfully"
     * ),
     * @OA\Response(
     *     response=404,
     *     description="Notification not found"
     * ),
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized, user must be authenticated"
     * ),
     * @OA\Response(
     *     response=500,
     *     description="Internal server error"
     * )
     * )
     */
    public function markNotificationAsRead(Request $request)
    {
        $user = Auth::user();
        $notificationId = $request->input('notification_id');
        $notification = Notification::where('user_id', $user->id)
            ->where('id', $notificationId)
            ->first();
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }
        $notification->read_at = now();
        $notification->save();
        return response()->json(['message' => 'Notification marked as read successfully']);
    }
}
