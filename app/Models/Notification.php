<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Notification",
 *     type="object",
 *     title="Notification",
 *     description="Notification model for storing messages sent to users",
 *     required={"user_id", "title", "type", "category", "reference", "content", "created_by"},
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         description="ID of the user the notification belongs to",
 *         example=5
 *     ),
 *     @OA\Property(
 *         property="created_by",
 *         type="integer",
 *         description="Admin or officer who created the notification",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         description="Short title of the notification",
 *         example="Payment Confirmation"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         enum={"info", "warning", "error"},
 *         description="Type of notification",
 *         example="info"
 *     ),
 *     @OA\Property(
 *         property="category",
 *         type="string",
 *         enum={"system", "user", "transaction", "application"},
 *         description="Category of notification",
 *         example="transaction"
 *     ),
 *     @OA\Property(
 *         property="reference",
 *         type="string",
 *         description="Unique reference for the notification",
 *         example="NOTIF-20250807-XYZ123"
 *     ),
 *     @OA\Property(
 *         property="read_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Timestamp when the notification was read",
 *         example="2025-08-07T14:30:00Z"
 *     ),
 *     @OA\Property(
 *         property="content",
 *         type="string",
 *         description="Detailed message body of the notification",
 *         example="Your payment has been confirmed. Thank you."
 *     ),
 *     @OA\Property(
 *         property="view_status",
 *         type="boolean",
 *         description="View status of the notification. 0 = Not viewed, 1 = Viewed",
 *         example=false
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         readOnly=true,
 *         example="2025-08-07T13:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         readOnly=true,
 *         example="2025-08-07T13:00:00Z"
 *     )
 * )
 */


class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'created_by',
        'type',
        'category',
        'reference',
        'read_at',
        'content',
        'view_status',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'view_status' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
