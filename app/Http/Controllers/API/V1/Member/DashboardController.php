<?php

namespace App\Http\Controllers\API\V1\Member;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Charges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
