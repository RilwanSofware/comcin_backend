<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 *  @OA\Schema(
 *    schema="Certificate",
 *    type="object",
 *    required={"id", "user_id", "title", "issued_at"},
 *    @OA\Property(property="id", type="integer", example=1),
 *    @OA\Property(property="user_id", type="integer", example=1),
 *    @OA\Property(property="title", type="string", example="Membership Certificate"),
 *    @OA\Property(property="description", type="string", example="Certificate of Membership"),
 *    @OA\Property(property="issued_at", type="string", format="date-time", example="2025-08-11T10:00:00Z"),
 *    @OA\Property(property="expires_at", type="string", format="date-time", example="2025-08-11T10:00:00Z"),
 *    @OA\Property(property="status", type="string", enum={"active", "expired"}, example="active"),
 *    @OA\Property(property="file_path", type="string", example="uploads/certificates/certificate12345.pdf")
 * )
 */


class Certificate extends Model
{
    protected $fillable = [
        'user_id', // foreign key to User
        'title', // e.g., Membership Certificate, Training Completion
        'description', // optional description of the certificate
        'issued_at', // date when the certificate was issued
        'expires_at', // optional expiration date for the certificate
        'status', // status of the certificate (e.g., active, expired)
        'file_path', // path to the certificate file
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
