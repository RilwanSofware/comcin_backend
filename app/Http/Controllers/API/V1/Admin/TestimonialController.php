<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v1/admin/testimonials",
     *     summary="Get all testimonials (admin only)",
     *     tags={"Admin - Testimonials"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of testimonials",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Testimonials retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Testimonial")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $testimonials = Testimonial::orderBy('created_at', 'desc')->paginate(10);
        return response()->json(['status'=>true,'message'=>'Testimonials retrieved successfully','data'=>$testimonials]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/testimonials",
     *     summary="Create a new testimonial (admin only)",
     *     tags={"Admin - Testimonials"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"author_name","author_email","rating","description"},
     *             @OA\Property(property="author_name", type="string", example="Jane Doe"),
     *             @OA\Property(property="author_email", type="string", format="email", example="jane@example.com"),
     *             @OA\Property(property="rating", type="integer", example=5),
     *             @OA\Property(property="description", type="string", example="Great experience working with this team."),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Testimonial created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Testimonial")
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'author_name'=>'required|string|max:255',
            'author_email'=>'required|email|max:255',
            'rating'=>'required|integer|min:1|max:5',
            'description'=>'required|string',
        ]);

        $testimonial = Testimonial::create([
            'author_name'=>$request->author_name,
            'author_email'=>$request->author_email,
            'rating'=>$request->rating,
            'description'=>$request->description,
            'status'=>'draft'
        ]);

        return response()->json(['status'=>true,'message'=>'Testimonial created successfully','data'=>$testimonial],201);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/testimonials/{id}",
     *     summary="Delete a testimonial (admin only)",
     *     tags={"Admin - Testimonials"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the testimonial",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Testimonial deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Testimonial deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->delete();

        return response()->json(['status'=>true,'message'=>'Testimonial deleted successfully']);
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/testimonials/{id}/publish",
     *     summary="Publish a testimonial (admin only)",
     *     tags={"Admin - Testimonials"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the testimonial",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Testimonial published successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Testimonial")
     *     )
     * )
     */
    public function publish($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->status = 'publish';
        $testimonial->save();

        return response()->json(['status'=>true,'message'=>'Testimonial published successfully','data'=>$testimonial]);
    }


}
