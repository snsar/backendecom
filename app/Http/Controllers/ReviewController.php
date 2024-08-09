<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    // Fetch all reviews for a product
    public function index(Product $product)
    {
        $reviews = $product->reviews()->with('user')->get();
        return response()->json(['success' => true, 'data' => $reviews]);
    }

    // Store a new review
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $review = new Review();
        $review->product_id = $product->id;
        $review->user_id = Auth::id();
        $review->rating = $request->rating;
        $review->comment = $request->comment;
        $review->save();

        return response()->json(['success' => true, 'data' => $review], 201);
    }

    // Update an existing review
    public function update(Request $request, Review $review)
    {
        $this->authorize('update', $review);

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $review->rating = $request->rating;
        $review->comment = $request->comment;
        $review->save();

        return response()->json(['success' => true, 'data' => $review]);
    }

    // Delete a review
    public function destroy(Review $review)
    {
        $this->authorize('delete', $review);

        $review->delete();

        return response()->json(['success' => true, 'message' => 'Review deleted successfully']);
    }
}
