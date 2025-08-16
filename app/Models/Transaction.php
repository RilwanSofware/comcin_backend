<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="Transaction",
 *   type="object",
 *   required={"member_id", "amount", "transaction_type", "status"},
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="member_id", type="integer", example=1),
 *   @OA\Property(property="charge_id", type="integer", example=1),
 *   @OA\Property(property="reference", type="string", example="TXN-20250807-XYZ123"),
 *   @OA\Property(property="amount", type="number", format="float", example=100.00),
 *   @OA\Property(property="transaction_type", type="string", enum={"deposit", "withdrawal", "transfer"}, example="deposit"),
 *   @OA\Property(property="status", type="string", enum={"pending", "completed", "failed"}, example="completed"),
 *   @OA\Property(property="description", type="string", example="Payment for monthly dues"),
 *   @OA\Property(property="payment_method_id", type="integer", example=1),
 *   @OA\Property(property="receipt_file", type="string", nullable=true, example="uploads/receipts/txn12345.png")
 * )
 */
class Transaction extends Model
{
    //

    protected $fillable = [
        'member_id', // foreign key to User
        'charge_id', // foreign key to Charge (if applicable)
        'reference', // unique transaction reference
        'amount',
        'transaction_type', // e.g., deposit, withdrawal, transfer
        'status', // e.g., pending, completed, failed
        'reference', // unique transaction reference
        'description', // optional description of the transaction
        'recorded_by', // user who recorded the transaction
        'payment_method_id', // foreign key to PaymentMethod
        'receipt_file', // optional URL to the transaction receipt
    ];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }
    public function charge()
    {
        return $this->belongsTo(Charges::class, 'charge_id');
    }
}
