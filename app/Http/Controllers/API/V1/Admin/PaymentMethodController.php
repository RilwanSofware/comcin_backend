<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Payment Methods",
 *     description="Manage payment methods"
 * )
 */

class PaymentMethodController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/payment-methods",
     *     summary="Get all payment methods",
     *     tags={"Admin - Payment Method"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of payment methods")
     * )
     */
    public function index()
    {
        return response()->json(PaymentMethod::all());
    }


    /**
     * @OA\Get(
     *     path="/api/v1/admin/payment-methods/{id}",
     *     summary="Get a single payment method",
     *     tags={"Admin - Payment Method"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Single payment method")
     * )
     */

    public function show($id)
    {
        $paymentMethod = PaymentMethod::find($id);

        if (!$paymentMethod) {
            return response()->json(['error' => 'Payment method not found'], 404);
        }

        return response()->json($paymentMethod);
    }



    /**
     * @OA\Post(
     *     path="/api/v1/admin/payment-methods",
     *     summary="Create a new payment method",
     *     tags={"Admin - Payment Method"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "slug", "logo", "mode",  "is_active"},
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="logo", type="file"),
     *                 @OA\Property(property="mode", type="string", enum={"test", "live"}),
     *                @OA\Property(property="test_public_key", type="string"),
     *                @OA\Property(property="test_secret_key", type="string"),
     *                @OA\Property(property="live_public_key", type="string"),
     *                @OA\Property(property="live_secret_key", type="string"),
     *                @OA\Property(property="account_name", type="string"),
     *                @OA\Property(property="account_number", type="string"),
     *                @OA\Property(property="bank_name", type="string"),
     *                @OA\Property(property="currency", type="string", default="NGN"),
     *                 @OA\Property(property="is_active", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment method created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="payment_method", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     )
     * )
     */

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:payment_methods,slug',
            'logo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'mode' => 'required|in:test,live',
            'test_public_key' => 'nullable|string',
            'test_secret_key' => 'nullable|string',
            'live_public_key' => 'nullable|string',
            'live_secret_key' => 'nullable|string',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'is_active' => 'required|boolean',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $logoName = 'logo_' . time() . '.' . $logo->getClientOriginalExtension();
            $logo->move(public_path('assets/uploads/payment-logos/'), $logoName);
            $logoPath = 'assets/uploads/payment-logos/' . $logoName;
        }

        $paymentMethod = PaymentMethod::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'logo' => $logoPath,
            'mode' => $request->mode,
            'test_public_key' => $request->test_public_key,
            'test_secret_key' => $request->test_secret_key,
            'live_public_key' => $request->live_public_key,
            'live_secret_key' => $request->live_secret_key,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'bank_name' => $request->bank_name,
            'currency' => $request->currency ?? 'NGN',
            'is_active' => $request->is_active,
        ]);

        return response()->json([
            'message' => 'Payment method created successfully.',
            'payment_method' => $paymentMethod
        ], 201);
    }


    /**
     * @OA\Put(
     *     path="/api/v1/admin/payment-methods/{id}",
     *     tags={"Admin - Payment Method"},
     *     summary="Update a payment method",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "mode"},
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="logo", type="file"),
     *                 @OA\Property(property="mode", type="string", enum={"test", "live"}),
     *                 @OA\Property(property="test_public_key", type="string"),
     *                 @OA\Property(property="test_secret_key", type="string"),
     *                 @OA\Property(property="live_public_key", type="string"),
     *                 @OA\Property(property="live_secret_key", type="string"),
     *                 @OA\Property(property="account_name", type="string"),
     *                 @OA\Property(property="account_number", type="string"),
     *                 @OA\Property(property="bank_name", type="string"),
     *                 @OA\Property(property="currency", type="string"),
     *                 @OA\Property(property="is_active", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment method updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment method not found"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'slug' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'mode' => 'required|in:test,live',
            'test_public_key' => 'nullable|string',
            'test_secret_key' => 'nullable|string',
            'live_public_key' => 'nullable|string',
            'live_secret_key' => 'nullable|string',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $filename = 'logo_' . time() . '.' . $file->getClientOriginalExtension();
                $path = 'uploads/payment-methods';
                $file->move(public_path($path), $filename);
                $paymentMethod->logo = $path . '/' . $filename;
            }

            $paymentMethod->name = $request->name;
            $paymentMethod->slug = $request->slug ?? Str::slug($request->name);
            $paymentMethod->mode = $request->mode;
            $paymentMethod->test_public_key = $request->test_public_key;
            $paymentMethod->test_secret_key = $request->test_secret_key;
            $paymentMethod->live_public_key = $request->live_public_key;
            $paymentMethod->live_secret_key = $request->live_secret_key;
            $paymentMethod->account_name = $request->account_name;
            $paymentMethod->account_number = $request->account_number;
            $paymentMethod->bank_name = $request->bank_name;
            $paymentMethod->currency = $request->currency ?? 'NGN';
            $paymentMethod->is_active = $request->is_active ?? false;

            $paymentMethod->save();

            return response()->json([
                'message' => 'Payment method updated successfully',
                'data' => $paymentMethod
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the payment method',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Delete(
     *     path="/api/v1/admin/payment-methods/{id}",
     *     summary="Delete a payment method",
     *     tags={"Admin - Payment Method"},
     *    security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Payment method deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy($id)
    {
        $paymentMethod = PaymentMethod::find($id);

        if (!$paymentMethod) {
            return response()->json(['error' => 'Payment method not found'], 404);
        }

        try {
            $paymentMethod->delete();

            return response()->json(['message' => 'Payment method deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete payment method', 'message' => $e->getMessage()], 500);
        }
    }
}
