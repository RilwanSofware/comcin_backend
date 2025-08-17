<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *  schema="Support",
 * type="object",
 * required={"uuid", "user_id", "name", "email", "subject", "message", "status"},
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="uuid", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
 * @OA\Property(property="user_id", type="integer", example=1),
 * @OA\Property(property="name", type="string", example="John Doe"),
 * @OA\Property(property="email", type="string", format="email", example="member@example.com"),
 * @OA\Property(property="subject", type="string", example="Issue with Membership"),
 * @OA\Property(property="message", type="string", example="I am having trouble accessing my membership benefits."),
 * @OA\Property(property="status", type="string", enum={"pending", "resolved", "cancelled"}, example="pending"),
 * @OA\Property(property="attachment", type="string", nullable=true, example="uploads/support/attachment.png"),
 * @OA\Property(property="resolved_at", type="string", format="date-time", nullable=true, example="2025-08-17T12:00:00Z"),
 * @OA\Property(property="cancelled_at", type="string", format="date-time", nullable=true, example="2025-08-17T12:00:00Z"),
 * @OA\Property(property="created_at", type="string", format="date-time", example="2025-08-17T12:00:00Z"),
 * @OA\Property(property="updated_at", type="string", format="date-time", example="2025-08-17T12:00:00Z")
 * )
 */
class Support extends Model
{
    
    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'email',
        'subject',
        'message',
        'status',
        'attachment',
        'resolved_at',
        'cancelled_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
