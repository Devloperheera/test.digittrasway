<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller{
    public function addReview(Request $request)
{
    // If request has no data at all
    if (!$request->all()) {
        return response()->json([
            'status'  => false,
            'message' => 'No data provided',
        ], 422);
    }

    // Validate incoming data
    $validator = \Validator::make($request->all(), [
        'user_id'  => 'required|integer',
        'vendor_id'  => 'required|integer',
        'booking_id' => 'required|integer',
        'rating'     => 'required|integer|min:1|max:5',
        'comment'    => 'nullable|string',
    ]);

    // If validation fails
    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => 'Validation error',
            'errors'  => $validator->errors(),
        ], 422);
    }

    $data = $validator->validated();

    // Create Review
    $review = Review::create($data);

    return response()->json([
        'status'  => true,
        'message' => 'Review added successfully',
        'review'  => $review,
    ], 201);
}

}