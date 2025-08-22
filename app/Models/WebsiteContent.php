<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="WebsiteContent",
 *     type="object",
 *     title="Website Content",
 *     required={"section", "key", "value"},
 *     @OA\Property(property="section", type="string", example="homepage"),
 *     @OA\Property(property="key", type="string", example="hero_title"),
 *     @OA\Property(property="value", type="string", example="Welcome to our website"),
 *     @OA\Property(property="media", type="string", nullable=true, example="uploads/website/hero_banner.png"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */


class WebsiteContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'section',
        'key',
        'value',
        'media',
        'updated_by',
    ];
}
