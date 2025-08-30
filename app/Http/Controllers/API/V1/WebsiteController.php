<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\News;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\WebsiteContent;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *     path="/api/v1/homepage",
     *     tags={"Website"},
     *     summary="Get homepage data",
     *     description="Retrieve data for the homepage including logo, members count, state coverage, news, testimonials, and members.",
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of homepage data",
     *         @OA\JsonContent(
     *             @OA\Property(property="logo", type="string", example="https://example.com/logo.png"),
     *             @OA\Property(property="members_count", type="integer", example=150),
     *             @OA\Property(property="state_coverage", type="integer", example=25),
     *             @OA\Property(property="news", type="array", @OA\Items(ref="#/components/schemas/News")),
     *             @OA\Property(property="testimonials", type="array", @OA\Items(ref="#/components/schemas/Testimonial")),
     *             @OA\Property(
     *                 property="members",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="institution_name", type="string", example="ABC University"),
     *                     @OA\Property(property="institution_logo", type="string", example="frontpageAssets/institutions/abc_logo.png"),
     *                     @OA\Property(property="operating_state", type="string", example="California"),
     *                     @OA\Property(property="is_approved", type="boolean", example=true),
     *                     @OA\Property(property="institution_type", type="string", example="University"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function homepage()
    {

        $logo = WebsiteContent::where('key', 'logo')->first();

        $members_count = Institution::count();
        $state_coverage = Institution::distinct('operating_state')->count('operating_state');

        $news = News::where('status', 'published')->orderBy('created_at', 'desc')->take(5)->get();
        $testimonials = Testimonial::where('status', 'approved')->orderBy('created_at', 'desc')->take(5)->get();

        $members = Institution::get([
            'institution_name',
            'institution_logo',
            'operating_state',
            'is_approved',
            'institution_type',
            'created_at'
        ]);

        return response()->json([
            'logo' => $logo ? $logo->value : null,
            'members_count' => $members_count,
            'state_coverage' => $state_coverage,
            'news' => $news,
            'testimonials' => $testimonials,
            'members' => $members,
        ], 200);
    }
}
