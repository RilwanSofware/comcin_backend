<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *  schema="PaymentMethod",
 *  type="object",
 *  title="PaymentMethod",
 *  required={"name", "slug", "mode"},
 *      @OA\Property(property="id", type="integer", example=1),
 *      @OA\Property(property="name", type="string", example="Paystack"),  
 *      @OA\Property(property="slug", type="string", example="paystack"),
 *      @OA\Property(property="logo", type="string", nullable=true, example="uploads/payment_methods/paystack.png"),
 *      @OA\Property(property="mode", type="string", enum={"test", "live"}, example="test"),
 *      @OA\Property(property="test_public_key", type="string", example="pk_test_1234567890"),
 *      @OA\Property(property="test_secret_key", type="string", example="sk_test_1234567890"),
 *      @OA\Property(property="live_public_key", type="string", example="pk_live_1234567890"),
 *      @OA\Property(property="live_secret_key", type="string", example="sk_live_1234567890"),
 *      @OA\Property(property="account_name", type="string", nullable=true, example="Comcin Ltd"),
 *      @OA\Property(property="account_number", type="string", nullable=true, example="1234567890"),
 *      @OA\Property(property="bank_name", type="string", nullable=true, example="First Bank"),
 *      @OA\Property(property="currency", type="string", example="NGN"),
 *      @OA\Property(property="is_active", type="boolean", example=true),
 *      @OA\Property(property="created_at", type="string", format="date-time"),
 *      @OA\Property(property="updated_at", type="string", format="date-time"),
 * )  
 * */

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'mode',
        'test_public_key',
        'test_secret_key',
        'live_public_key',
        'live_secret_key',


        'account_name',
        'account_number',
        'bank_name',
        'currency',

        'is_active',
    ];
}
