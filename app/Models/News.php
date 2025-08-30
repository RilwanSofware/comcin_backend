<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** 
* @OA\Schema(
*     schema="News",
*     type="object",
*     title="News",
*     description="A news article entry",
*     @OA\Property(property="id", type="integer", readOnly=true, example=1 ),
*     @OA\Property(property="title", type="string", example="New Feature Released" ),
*     @OA\Property(property="slug", type="string", example="new-feature-released" ),
*     @OA\Property(property="category", type="string", example="Updates" ),
*     @OA\Property(property="summary", type="string", nullable=true,example="We have released a new feature..." ),
*     @OA\Property(property="content", type="string", example="Full content of the news article..." ),
*     @OA\Property(property="image", type="string", example="frontpageAssets/news/news_image.png"),
*     @OA\Property(property="status", type="string", enum={"draft", "published", "archived"}, example="published" ),
*     @OA\Property(property="admin_id", type="string", nullable=true, example="1" ),
*     @OA\Property(property="read_time", type="string", nullable=true, example="5 min" ),
*     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true, example="2024-01-01T12:00:00Z" ),
*     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true, example="2024-01-01T12:00:00Z" )
* )     
*/
class News extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'category',
        'summary',
        'content',
        'image',
        'status',
        'admin_id',
        'read_time',
    ];
}
