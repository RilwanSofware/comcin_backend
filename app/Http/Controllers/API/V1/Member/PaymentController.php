<?php

namespace App\Http\Controllers\API\V1\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Charge;
use App\Models\Charges;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use Faker\Provider\ar_EG\Payment;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaymentController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/v1/member/payment",
     *   summary="Get Payment Methods",
     *   description="Fetches all active payment methods available for the member.",
     *   tags={"Member - Payments"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *       response=200,
     *       description="Successful retrieval of payment methods",
     *       @OA\JsonContent(
     *           type="array",
     *           @OA\Items(ref="#/components/schemas/PaymentMethod")
     *       )
     *   ),
     *   @OA\Response(
     *       response=401,
     *       description="Unauthorized, user must be authenticated",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status", type="boolean", example=false),
     *           @OA\Property(property="message", type="string", example="Unauthorized")
     *       )
     *   ),
     *   @OA\Response(
     *       response=500,
     *       description="Internal server error",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status", type="boolean", example=false),
     *           @OA\Property(property="message", type="string", example="An error occurred while fetching payment methods")
     *       )
     *   )
     * )
     */

    public function getPaymentMethods()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        // Fetch active payment methods
        $payment_methods = PaymentMethod::where('status', 'active')->get();

        return response()->json($payment_methods, 201);
    }

    //Verify Paystack Payment
    /**
     * @OA\Post(
     *   path="/api/v1/member/payment/paystack/verify",
     *   summary="Verify Paystack Payment",
     *   description="Verifies a Paystack payment using the transaction reference.",
     *   tags={"Member - Payments"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *       name="reference",
     *       in="query",
     *       required=true,
     *       @OA\Schema(type="string"),
     *       description="The Paystack transaction reference to verify"
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful verification of Paystack payment",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status", type="boolean", example=true),
     *           @OA\Property(property="message", type="string", example="Payment verified successfully"),
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad request, invalid or missing reference",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status", type="boolean", example=false),
     *           @OA\Property(property="message", type="string", example="Invalid reference")
     *       )
     *   ),
     *   @OA\Response(
     *       response=401,
     *       description="Unauthorized, user must be authenticated",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status", type="boolean", example=false),
     *           @OA\Property(property="message", type="string", example="Unauthorized")
     *       )
     *   ),
     *   @OA\Response(
     *       response=500,
     *       description="Internal server error",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status", type="boolean", example=false),
     *           @OA\Property(property="message", type="string", example="An error occurred while verifying payment")
     *       )
     *   )
     * )
     */

    public function verifyPaystackPayment(Request $request)
    {
        $reference = $request->input('reference');

        try {
            $payment   = Paystack::getPaymentData($reference);
            $data = $payment['data'];

            if ($data['status'] !== 'success') {
                return response()->json(['message' => 'Payment not successful'], 400);
            }

            // Extract metadata (user_id, charge_id, payment_method_id)
            $metadata = $data['metadata'] ?? [];
            $userId   = $metadata['user_id'] ?? null;
            $chargeId = $metadata['charge_id'] ?? null;
            $paymentMethodId = $metadata['payment_method_id'] ?? null;

            if (!$userId || !$chargeId) {
                return response()->json(['message' => 'Invalid metadata received'], 400);
            }

            // Check if user exists
            $user = User::find($userId);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            DB::beginTransaction();

            // Check if transaction already exists (avoid duplicates)
            $existing = Transaction::where('reference', $data['reference'])->first();
            if ($existing) {
                DB::rollBack();
                return response()->json(['message' => 'Transaction already recorded'], 409);
            }

            // Save transaction
            $transaction = Transaction::create([
                'member_id'        => $userId,
                'charge_id'        => $chargeId,
                'reference'        => $data['reference'],
                'amount'           => $data['amount'] / 100, // Paystack returns amount in kobo
                'status'           => 'successful',
                'method'           => 'paystack',
                'narration'        => $data['gateway_response'] ?? null,
                'paid_at'          => now(),
                'recorded_by'      => Auth::id(),
                'payment_method_id' => $paymentMethodId
            ]);

            DB::commit();

            //notification
            // Store notification for the user
            store_notification(
                $userId,
                'Payment Verified',
                'Your payment has been verified successfully.',
                'success',
                'payment',
                1
            );

            // Store notification for the admin
            store_notification(
                1,
                'Payment Verified',
                'A payment has been verified for member: ' . $user->name,
                'info',
                'payment',
                $userId
            );

            return response()->json([
                'message'     => 'Payment verified successfully',
                'transaction' => $transaction
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Verification failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *  path="/api/v1/member/payment/manual",
     *   summary="Record Manual Payment",
     *   description="Records a manual payment made by the member.",
     *   tags={"Member - Payments"},
     *   security={{"sanctum":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *   @OA\MediaType(
     *             mediaType="multipart/form-data",
     *            @OA\Schema(
     *               required={"member_id", "amount", "receipt"},
     *              @OA\Property(property="member_id", type="integer", example=1, description="ID of the member making the payment"),
     *             @OA\Property(property="charge_id", type="integer", example=1, description="ID of the charge being paid (optional)"),
     *            @OA\Property(property="amount", type="number", format="float", example=1000.00, description="Amount being paid"),
     *            @OA\Property(property="receipt", type="string", format="binary", description="Receipt file for the payment")
     *            )
     *       )
     *   ),
     * *   @OA\Response(
     *      response=201,
     *     description="Manual payment recorded successfully",
     *    @OA\JsonContent(
     *          type="object",
     *         @OA\Property(property="message", type="string", example="Manual payment recorded successfully"),
     *        @OA\Property(property="charge", ref="#/components/schemas/Charges"),
     *       @OA\Property(property="transaction", ref="#/components/schemas/Transaction")
     * *     )
     *  ),
     * * @OA\Response(
     *     response=400,
     *    description="Bad request, validation errors",
     *   @OA\JsonContent(
     *        type="object",
     *       @OA\Property(property="message", type="string", example="Validation failed"),
     *      @OA\Property(property="errors", type="object", additionalProperties={"type":"string"})
     * *    )
     * * ),
     * * @OA\Response(
     *     response=403,
     *    description="Forbidden, charge does not belong to this member",
     *   @OA\JsonContent(
     *       type="object",
     *      @OA\Property(property="message", type="string", example="Charge does not belong to this member")
     *    ) 
     * * ),
     * * @OA\Response(
     *     response=401,
     *   description="Unauthorized, user must be authenticated",
     *  @OA\JsonContent(
     *      type="object",
     *     @OA\Property(property="message", type="string", example="Unauthorized")
     * *   )
     * * ),
     *  * @OA\Response(
     *      response=500,
     *      description="Internal server error",
     *     @OA\JsonContent(
     *        type="object",
     *       @OA\Property(property="message", type="string", example="Payment failed"),
     *      @OA\Property(property="error", type="string", example="Error message")
     *     )
     *      
     *   )
     * 
     * )
     *           
     */

    public function manualPayment(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'charge_id' => 'nullable|exists:charges,id',
            'member_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'receipt' => 'required|file|mimes:pdf,jpeg,png',
        ]);

        try {
            DB::beginTransaction();

            $charge = null;

            // If charge_id provided, fetch and validate ownership
            if ($request->charge_id) {
                $charge = Charges::findOrFail($request->charge_id);

                if ($charge->member_id != $request->member_id) {
                    DB::rollBack();
                    return response()->json(['message' => 'Charge does not belong to this member'], 403);
                }
            } else {
                // No charge_id provided, check if member already has a charge
                $charge = Charges::where('member_id', $request->member_id)->first();

                // If no charge exists → first annual due
                if (!$charge) {
                    $charge = Charges::create([
                        'member_id'   => $request->member_id,
                        'title'       => 'Annual Dues',
                        'description' => 'Annual Registration Due',
                        'type'        => 'due',
                        'amount'      => $request->amount,
                        'status'      => 'unpaid',
                        'due_date'    => now(),
                        'created_by'  => $user->id ?? null,
                    ]);
                }
            }

            // Handle receipt upload
            $receiptPath = null;
            if ($request->hasFile('receipt')) {
                $file = $request->file('receipt');
                $receiptDir = 'uploads/' . $user->id . '/receipts/';
                if (!file_exists(public_path($receiptDir))) {
                    mkdir(public_path($receiptDir), 0755, true);
                }
                $filename = Str::random(10) . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path($receiptDir), $filename);
                $receiptPath = $receiptDir . $filename;
            }

            // Create transaction
            $transaction = Transaction::create([
                'member_id'   => $request->member_id,
                'charge_id'   => $charge->id,
                'reference'   => strtoupper(Str::random(12)),
                'amount'      => $request->amount, // use input amount instead of charge->amount
                'status'      => 'pending',
                'method'      => 'bank_transfer',
                'narration'   => null,
                'paid_at'     => now(),
                'recorded_by' => $user->id ?? null,
                'receipt_file' => $receiptPath,
            ]);

            DB::commit();

            //notification
            // Store notification for the user
            store_notification(
                $user->id,
                'Payment Recorded',
                'Your manual payment has been recorded successfully.',
                'success',
                'payment',
                1
            );

            // Store notification for the admin
            store_notification(
                1,
                'Payment Recorded',
                'A manual payment has been recorded for member: ' . $user->name,
                'info',
                'payment',
                $user->id
            );

            return response()->json([
                'message'     => 'Manual payment recorded successfully',
                'charge'      => $charge,
                'transaction' => $transaction
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Payment failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
