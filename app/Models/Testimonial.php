<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Testimonial",
 *     type="object",
 *     title="Testimonial",
 *     description="A user testimonial entry",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         readOnly=true,
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="author_name",
 *         type="string",
 *         example="John Doe"
 *     ),
 *     @OA\Property(
 *         property="author_email",
 *         type="string",
 *         format="email",
 *         nullable=true,
 *         example="johndoe@example.com"
 *     ),
 *     @OA\Property(
 *         property="rating",
 *         type="integer",
 *         format="int32",
 *         description="Rating value from 1 to 5",
 *         minimum=1,
 *         maximum=5,
 *         example=5
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="The testimonial content",
 *         example="This platform has really improved my workflow!"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"draft", "published"},
 *         example="published"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         readOnly=true,
 *         example="2025-08-16T12:45:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         readOnly=true,
 *         example="2025-08-16T12:55:00Z"
 *     )
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
