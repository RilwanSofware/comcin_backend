<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /** 
     * @OA\Get(
     *     path="/api/v1/admin/news",
     *     tags={"Admin - News"},
     *     summary="Get all news articles",
     *     description="Retrieve a list of all news articles.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of news articles",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/News"))    
     *    ),
     *    @OA\Response(response=401, description="Unauthorized" ),
     *    @OA\Response(response=500, description="Server error" )
     * )
     */
    public function index()
    {
        $news = News::all();
        return response()->json($news);
    }

    /**
     * Store a newly created resource in storage.
     */
    /** 
     * @OA\Post(
     *     path="/api/v1/admin/news",
     *     tags={"Admin - News"},
     *     summary="Create a news article",
     *     description="Create a new news article.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "category", "content", "status"},
     *             @OA\Property(property="title", type="string", example="New Feature Released"),
     *             @OA\Property(property="category", type="string", example="Updates"),
     *             @OA\Property(property="summary", type="string", nullable=true, example="We have released a new feature..."),
     *             @OA\Property(property="content", type="string", example="Full content of the news article..."),
     *             @OA\Property(property="image", type="string", nullable=true, example="frontpageAssets/news/news_image.png"),
     *             @OA\Property(property="status", type="string", enum={"draft", "published", "archived"}, example="published"),
     *             @OA\Property(property="read_time", type="string", nullable=true, example="5 min")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="News article created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/News")
     *     ),
     *     @OA\Response(response=400, description="Bad request" ),
     *     @OA\Response(response=401, description="Unauthorized" ),
     *     @OA\Response(response=500, description="Server error" )
     * )
     */
    public function store(Request $request)
    {

        $user = Auth::user();

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'summary' => 'nullable|string|max:500',
            'content' => 'required|string',
            'image' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published,archived',
            'read_time' => 'nullable|string|max:50',
        ]);

        $slug = Str::slug($request->title);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = $slug . '_' . $file->getClientOriginalName();
            $filePath = 'uploads/news/' . $filename;
            $file->move(public_path('uploads/news'), $filename);
            $request->merge(['image' => $filePath]);
        }

        $news = News::create([
            'title' => $request->title,
            'slug' => $slug,
            'category' => $request->category,
            'summary' => $request->summary,
            'content' => $request->content,
            'image' => $request->image ?? null,
            'status' => $request->status,
            'admin_id' => $user->id,
            'read_time' => $request->read_time,
        ]);

        return response()->json(['message' => 'News article created successfully', 'data' => $news], 201);
    }

    /**
     * Display the specified resource.
     */
    /** 
     * @OA\Get(
     *     path="/api/v1/admin/news/{id}",
     *     tags={"Admin - News"},
     *     summary="Get a single news article",
     *     description="Retrieve a single news article by its ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, 
     *     @OA\Schema(type="integer"), description="ID of the news article to retrieve" ),
     *     @OA\Response( response=200, description="Successful retrieval of the news article", ),
     *     @OA\Response( response=401, description="Unauthorized" ),
     *     @OA\Response( response=404, description="News article not found" ),
     *     @OA\Response( response=500, description="Server error" )
     * )
     */
    public function show(string $id)
    {
        $singlenews = News::find($id);
        if (!$singlenews) {
            return response()->json(['message' => 'News article not found'], 404);
        }
        return response()->json($singlenews);
    }

    /**
     * Update the specified resource in storage.
     */

    /**
     * @OA\Put(
     *     path="/api/v1/admin/news/{id}",
     *     tags={"Admin - News"},
     *     summary="Update a news article",
     *     description="Update an existing news article by its ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, 
     *     @OA\Schema(type="integer"), description="ID of the news article to update" ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Feature Released"),
     *             @OA\Property(property="category", type="string", example="Updates"),
     *             @OA\Property(property="summary", type="string", nullable=true, example="We have updated a feature..."),
     *             @OA\Property(property="content", type="string", example="Full updated content of the news article..."),
     *             @OA\Property(property="image", type="string", nullable=true, example="frontpageAssets/news/updated_news_image.png"),
     *             @OA\Property(property="status", type="string", enum={"draft", "published", "archived"}, example="published"),
     *             @OA\Property(property="read_time", type="string", nullable=true, example="7 min")    
     *    )
     *    ),    
     *    @OA\Response( response=200, description="News article updated successfully", ),
     *    @OA\Response( response=400, description="Bad request" ),
     *    @OA\Response( response=401, description="Unauthorized" ),
     *    @OA\Response( response=404, description="News article not found" ),
     *    @OA\Response( response=500, description="Server error" )
     * )
     * 
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        $news = News::find($id);
        if (!$news) {
            return response()->json(['message' => 'News article not found'], 404);
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:100',
            'summary' => 'nullable|string|max:500',
            'content' => 'sometimes|required|string',
            'image' => 'nullable|string|max:255',
            'status' => 'sometimes|required|in:draft,published,archived',
            'read_time' => 'nullable|string|max:50',
        ]);

        if ($request->has('title')) {
            $news->title = $request->title;
            $news->slug = Str::slug($request->title);
        }
        if ($request->has('category')) {
            $news->category = $request->category;
        }
        if ($request->has('summary')) {
            $news->summary = $request->summary;
        }
        if ($request->has('content')) {
            $news->content = $request->content;
        }
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = $news->slug . '_' . $file->getClientOriginalName();
            $filePath = 'uploads/news/' . $filename;
            $file->move(public_path('uploads/news'), $filename);
            $news->image = $filePath;
        }
        if ($request->has('status')) {
            $news->status = $request->status;
        }
        if ($request->has('read_time')) {
            $news->read_time = $request->read_time;
        }
        $news->admin_id = $user->id; // Update admin_id to the user making the update
        $news->save();

        return response()->json(['message' => 'News article updated successfully', 'data' => $news]);
    }

    /**
     * Remove the specified resource from storage.
     */
    /** 
     * @OA\Delete(
     *     path="/api/v1/admin/news/{id}",
     *     tags={"Admin - News"},
     *     summary="Delete a news article",
     *     description="Delete an existing news article by its ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, 
     *     @OA\Schema(type="integer"), description="ID of the news article to delete" ),
     *     @OA\Response( response=200, description="News article deleted successfully", ),
     *     @OA\Response( response=401, description="Unauthorized" ),
     *     @OA\Response( response=404, description="News article not found" ),
     *     @OA\Response( response=500, description="Server error" )
     * )
     */
    public function destroy(string $id)
    {
        $news = News::find($id);
        if (!$news) {
            return response()->json(['message' => 'News article not found'], 404);
        }
        //delete news image from storage
        if ($news->image && file_exists(public_path($news->image))) {
            unlink(public_path($news->image));
        }
        $news->delete();
        return response()->json(['message' => 'News article deleted successfully']);
    }

    /**
     * Publish a news article.
     */
    /** 
     * @OA\Post(
     *     path="/api/v1/admin/news/{id}/publish",
     *     tags={"Admin - News"},
     *     summary="Publish a news article",
     *     description="Publish an existing news article by its ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, 
     *     @OA\Schema(type="integer"), description="ID of the news article to publish   " ),
     *     @OA\Response( response=200, description="News article published successfully", ),
     *     @OA\Response( response=401, description="Unauthorized" ),
     *     @OA\Response( response=404, description="News article not found" ),
     *     @OA\Response( response=500, description="Server error" )
     * )
     */
    public function publish(string $id)
    {
        $news = News::find($id);
        if (!$news) {
            return response()->json(['message' => 'News article not found'], 404);
        }
        $news->status = 'published';
        $news->save();
        return response()->json(['message' => 'News article published successfully', 'data' => $news]);
    }
}
