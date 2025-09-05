<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Testimonial",
 *     type="object",
 *     title="Testimonial",
 *     description="A user testimonial entry",
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1 ),
 *     @OA\Property(property="user_name", type="string", example="John Doe" ),
 *     @OA\Property(property="content", type="string", example="This is a great service!" ),
 *     @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="approved" ),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true, example="2024-01-01T12:00:00Z" ),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true, example="2024-01-01T12:00:00Z" )
 * )
 */

class Testimonial extends Model
{

    protected $fillable = [
        'author_name',
        'author_email',
        'rating',
        'description',
        'status',
    ];

}
