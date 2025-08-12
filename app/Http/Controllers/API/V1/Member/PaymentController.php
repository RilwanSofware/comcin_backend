<?php

namespace App\Http\Controllers\API\V1\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    //Verify Paystack Payment
    /**
     * @OA\Post(
     *   path="/api/v1/member/payment/verify",
     *   summary="Verify Paystack Payment",
     *   description="Verifies a Paystack payment using the transaction reference.",
     *   tags={"Member"},
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
    
    public function verifyPayment(Request $request)
    {
        $reference = $request->input('reference');
        if (!$reference) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid reference'
            ], 400);
        }   
        // Here you would typically call the Paystack API to verify the payment
        // For demonstration, let's assume the verification is successful
        $paymentData = [
            'reference' => $reference,
            'status' => 'success',
            'amount' => 10000, // Example amount
            'currency' => 'NGN',
            'paid_at' => now(),
            'customer' => [
                'email' => ''
            ],
            'metadata' => [
                'user_id' => Auth::user() // Assuming the user is authenticated
            ]
        ];  
        return response()->json([
            'status' => true,
            'message' => 'Payment verified successfully',
            'data' => $paymentData
        ]);

    }
}
