<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WebsiteContentController extends Controller
{


    /**
     * Display a listing of all website content.
     */

    /**
     * @OA\Get(
     *     path="/api/v1/admin/website-content",
     *     tags={"Admin - Website Content"},
     *     summary="Get all website content",
     *     description="Fetch all website content entries.",
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/WebsiteContent"))
     *     )
     * )
     */
    public function index()
    {
        $contents = WebsiteContent::all();
        return response()->json($contents, 200);
    }

    /**
     * Store new website content (by section + key).
     */

    /**
     * @OA\Post(
     *     path="/api/v1/admin/website-content",
     *     tags={"Admin - Website Content"},
     *     summary="Create website content",
     *     description="Create a new website content entry with optional media upload",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"section","key","value"},
     *                 @OA\Property(property="section", type="string", example="homepage"),
     *                 @OA\Property(property="key", type="string", example="hero_title"),
     *                 @OA\Property(property="value", type="string", example="Welcome to our website"),
     *                 @OA\Property(property="media", type="file", description="Optional media file")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Website content created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'section' => 'required|string',
            'key'     => 'required|string',
            'value'   => 'nullable|string',
            'media'   => 'nullable|file|mimes:jpg,jpeg,png,gif,svg,webp|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $content = new WebsiteContent();
            $content->section = $request->section;
            $content->key     = $request->key;
            $content->value   = $request->value;

            // Handle Media Upload
            if ($request->hasFile('media')) {
                $file = $request->file('media');
                $filename = Str::slug($request->key) . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = 'uploads/website-content';
                $file->move(public_path($path), $filename);

                $content->media = $path . '/' . $filename;
            }

            $content->save();

            DB::commit();
            return response()->json(['message' => 'Website content created successfully', 'data' => $content], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Show specific content by section + key.
     */

    /**
     * @OA\Get(
     *     path="/api/v1/admin/website-content/{section}/{key}",
     *     tags={"Admin - Website Content"},
     *     summary="Get website content by section and key",
     *     @OA\Parameter(name="section", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="key", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Website content retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="section", type="string"),
     *             @OA\Property(property="key", type="string"),
     *             @OA\Property(property="value", type="string"),
     *             @OA\Property(property="media", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Content not found")
     * )
     */

    public function show($section, $key)
    {
        $content = WebsiteContent::where('section', $section)
            ->where('key', $key)
            ->first();

        if (!$content) {
            return response()->json(['message' => 'Content not found'], 404);
        }

        return response()->json($content, 200);
    }

    /**
     * Update content by section + key.
     */

    /**
     * @OA\Put(
     *     path="/api/v1/admin/website-content/{section}/{key}",
     *     tags={"Admin - Website Content"},
     *     summary="Update website content",
     *     description="Update website content by section and key with optional media replacement",
     *     @OA\Parameter(name="section", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="key", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="value", type="string", example="Updated website title"),
     *                 @OA\Property(property="media", type="file", description="Optional media file")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Website content updated successfully"
     *     ),
     *     @OA\Response(response=404, description="Content not found")
     * )
     */

    public function update(Request $request, $section, $key)
    {
        $request->validate([
            'value'   => 'nullable|string',
            'media'   => 'nullable|file|mimes:jpg,jpeg,png,gif,svg,webp|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $content = WebsiteContent::where('section', $section)->where('key', $key)->firstOrFail();

            $content->value = $request->value ?? $content->value;

            // Handle Media Upload
            if ($request->hasFile('media')) {
                $file = $request->file('media');
                $filename = $content->key . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = 'uploads/website-content';

                // Delete old file if exists
                if ($content->media && file_exists(public_path($content->media))) {
                    unlink(public_path($content->media));
                }

                $file->move(public_path($path), $filename);
                $content->media = $path . '/' . $filename;
            }

            $content->save();

            DB::commit();
            return response()->json(['message' => 'Website content updated successfully', 'data' => $content]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Delete content by section + key.
     */

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/website-content/{section}/{key}",
     *     tags={"Admin - Website Content"},
     *     summary="Delete website content",
     *     @OA\Parameter(name="section", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="key", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Website content deleted successfully"
     *     ),
     *     @OA\Response(response=404, description="Content not found")
     * )
     */
    public function destroy($section, $key)
    {
        $content = WebsiteContent::where('section', $section)
            ->where('key', $key)
            ->first();

        if (!$content) {
            return response()->json(['message' => 'Content not found'], 404);
        }
        // Delete old file if exists
        if ($content->media && file_exists(public_path($content->media))) {
            unlink(public_path($content->media));
        }

        $content->delete();

        return response()->json(['message' => 'Content deleted successfully'], 200);
    }
}
