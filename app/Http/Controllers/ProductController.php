<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCollectionResource;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->when($request->has('category_id') && $request->category_id !== '', function ($query) use ($request) {
                return $query->where('category_id', $request->category_id);
            })
            ->when($request->has('search') && $request->search !== '', function ($query) use ($request) {
                return $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->when($request->has('sort_by'), function ($query) use ($request) {
                switch ($request->sort_by) {
                    case 'price_asc':
                        return $query->orderBy('price', 'asc');
                    case 'price_desc':
                        return $query->orderBy('price', 'desc');
                    case 'name_asc':
                        return $query->orderBy('name', 'asc');
                    case 'name_desc':
                        return $query->orderBy('name', 'desc');
                    default:
                        return $query;
                }
            })
            ->paginate($request->get('per_page', 15));

        return $this->successResponse(
            new ProductCollectionResource($products),
            'Products retrieved successfully'
        );
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate image file
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }
        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $appUrl = config('app.url');
            $data['image_url'] = $appUrl . ':8000' . Storage::url($path);
        }
        $product = Product::create($data);

        return $this->successResponse(
            new ProductResource($product),
            'Product created successfully',
            201
        );
    }

    public function show($id)
    {
        $product = Product::with('category')->find($id);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        return $this->successResponse(
            new ProductResource($product),
            'Product retrieved successfully'
        );
    }

    public function update($id, Request $request)
    {
        $product = Product::find($id);
        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|nullable|numeric|min:0',
            'stock_quantity' => 'sometimes|nullable|integer|min:0',
            'category_id' => 'sometimes|nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate image file
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        $data = $validator->validated();
        // Handle the image upload
        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($product->image_url) {
                $oldImagePath = str_replace('/storage/', '', $product->image_url);
                Storage::disk('public')->delete($oldImagePath);
            }

            $path = $request->file('image')->store('products', 'public');
            $appUrl = config('app.url');
            $data['image_url'] = $appUrl . ':8000' . Storage::url($path);
        }
        $product->update($data);

        return $this->successResponse(
            new ProductResource($product),
            'Product updated successfully'
        );
    }

    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        // Delete the image if it exists
        if ($product->image_url) {
            $imagePath = str_replace('/storage/', '', $product->image_url);
            Storage::disk('public')->delete($imagePath);
        }

        $product->delete();

        return $this->successResponse(null, 'Product deleted successfully');
    }
}
