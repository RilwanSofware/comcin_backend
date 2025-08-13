<?php

namespace App\Http\Controllers\API\V1\Member;

use App\Http\Controllers\Controller;
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
     *         type="object",
     *         @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
     *         @OA\Property(property="institution", type="object", ref="#/components/schemas/Institution")
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


        // Logic to retrieve member-specific dashboard data
        $data = [
            $user
        ];

        return response()->json($data);
    }
}
