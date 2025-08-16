<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="Charges",
 *  type="object",
 *  required={"id", "member_id", "title", "type", "amount"},
 *  @OA\Property(property="id", type="integer", example=1),
 *  @OA\Property(property="member_id", type="integer", example=1),
 *  @OA\Property(property="title", type="string", example="Monthly Dues"),
 *  @OA\Property(property="description", type="string", example="Monthly membership dues"),
 *  @OA\Property(property="type", type="string", enum={"due", "levy", "fine"}, example="due"),
 *  @OA\Property(property="amount", type="number", format="float", example=100.00),
 *  @OA\Property(property="status", type="string", enum={"unpaid", "paid"}, example="unpaid"),
 *  @OA\Property(property="due_date", type="string", format="date", example="2025-08-11"),
 *  @OA\Property(property="paid_at", type="string", format="date-time", example="2025-08-11T10:00:00Z"),
 *  @OA\Property(property="created_by", type="integer", example=1)
 * )
*/
class Charges extends Model
{
    protected $fillable = [
        'member_id', // foreign key to User
        'title', // e.g., Monthly Dues, Welfare Levy
        'description', // optional description of the charge
        'type', // e.g., due, levy, fine
        'amount', // amount of the charge
        'description', // description of the charge
        'type', // type of charge (e.g., fixed, percentage)
        'status', // status of the charge (e.g., active, inactive)
        'due_date', // optional due date for the charge
        'paid_at', // timestamp when the charge was paid
        'created_by', // user who created the charge
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'charge_id');
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
