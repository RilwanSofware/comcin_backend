<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Institution",
 *     type="object",
 *     required={"institution_name", "institution_type", "registration_number"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="institution_name", type="string", example="ABC Microfinance"),
 *     @OA\Property(property="institution_type", type="string", enum={"Microfinance", "Cooperative", "Other"}, example="Microfinance"),
 *     @OA\Property(property="category_type", type="string", enum={"Unit", "State", "Federal"}, example="Unit"),
 *     @OA\Property(property="date_of_establishment", type="string", format="date", example="2020-05-15"),
 *     @OA\Property(property="registration_number", type="string", example="REG-123456"),
 *     @OA\Property(property="regulatory_body", type="string", example="Central Bank"),
 *     @OA\Property(property="operating_state", type="string", example="Lagos"),
 *     @OA\Property(property="head_office", type="string", example="123 Main Street, Lagos"),
 *     @OA\Property(property="business_operation_address", type="string", example="45 Business Rd, Abuja"),
 *     @OA\Property(property="website_url", type="string", format="url", example="https://www.example.com"),
 *     @OA\Property(property="descriptions", type="string", example="A cooperative offering microfinance services."),
 *
 *     @OA\Property(property="institution_logo", type="string", example="uploads/institutions/uuid/logo.png"),
 *     @OA\Property(property="institution_banner", type="string", example="uploads/institutions/uuid/banner.png"),
 * 
 *     @OA\Property(property="certificate_of_registration", type="string", example="uploads/institutions/uuid/certificate.pdf"),
 *     @OA\Property(property="operational_license", type="string", example="uploads/institutions/uuid/license.pdf"),
 *     @OA\Property(property="constitution", type="string", example="uploads/institutions/uuid/constitution.pdf"),
 *     @OA\Property(property="latest_annual_report", type="string", example="uploads/institutions/uuid/annual_report.pdf"),
 *     @OA\Property(property="letter_of_intent", type="string", example="uploads/institutions/uuid/letter_of_intent.pdf"),
 *     @OA\Property(property="board_resolution", type="string", example="uploads/institutions/uuid/board_resolution.pdf"),
 *     @OA\Property(property="passport_photograph", type="string", example="uploads/institutions/uuid/passport.jpg"),
 *     @OA\Property(property="other_supporting_document", type="string", example="uploads/institutions/uuid/supporting_doc.pdf"),
 *
 *     @OA\Property(property="membership_agreement", type="boolean", example=true),
 *     @OA\Property(property="terms_agreement", type="boolean", example=true),
 *
 *    @OA\Property(property="status", type="string", enum={"pending", "verifying", "approved", "rejected"}, example="pending"),
 *     @OA\Property(property="is_approved", type="integer", example=0),
 *     @OA\Property(property="rejection_reason", type="string", nullable=true, example="Incomplete documents"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-08-11T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-08-11T10:00:00Z")
 * )
 */

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'institution_name',
        'institution_type',
        'category_type',
        'date_of_establishment',
        'registration_number',
        'regulatory_body',
        'operating_state',
        'institution_logo',
        'institution_banner',
        'head_office',
        'business_operation_address',
        'website_url',
        'descriptions',
        'certificate_of_registration',
        'operational_license',
        'constitution',
        'latest_annual_report',
        'letter_of_intent',
        'board_resolution',
        'passport_photograph',
        'other_supporting_document',
        'membership_agreement',
        'terms_agreement',
        'status', // e.g., 'pending', 'approved', 'rejected', 'verifying'
        'is_approved',
        'rejection_reason',
    ];

    protected $casts = [
        'membership_agreement' => 'boolean',
        'terms_agreement'      => 'boolean',
        'is_approved'          => 'boolean',
        'date_of_establishment'=> 'date',
    ];

    /**
     * Relation: Institution belongs to a user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
