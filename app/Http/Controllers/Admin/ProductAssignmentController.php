<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductAssignmentController extends Controller
{
    /**
     * Display the assignment form.
     */
    public function index()
    {
        return view('admin.products.assign');
    }

    /**
     * Search products by name for the dynamic dropdown.
     */
    public function searchProducts(Request $request)
    {
        $query = $request->get('q');

        if (!$query) {
            return response()->json([]);
        }

        $products = Product::where('name', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'tagline', 'logo', 'link']); // Include 'link' for favicon fallback

        return response()->json($products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'tagline' => $product->tagline,
                'logo_url' => $product->logo_url,
            ];
        }));
    }

    /**
     * Search users by name, username, or email for the dynamic dropdown.
     */
    public function searchUsers(Request $request)
    {
        $query = $request->get('q');

        if (!$query) {
            return response()->json([]);
        }

        $users = User::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%");
        })
            ->limit(10)
            ->get(['id', 'name', 'email', 'google_avatar']);

        return response()->json($users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => null, // Username column doesn't exist in users table
                'email' => $user->email,
                'avatar' => $user->google_avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name),
            ];
        }));
    }

    /**
     * Assign a product to a user.
     */
    public function assign(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $product = Product::findOrFail($request->product_id);
        $user = User::findOrFail($request->user_id);

        if ($product->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => "The product '{$product->name}' is already assigned to '{$user->name}'."
            ], 422);
        }

        try {
            DB::transaction(function () use ($product, $user) {
                $product->user_id = $user->id;
                $product->save();
            });

            return response()->json([
                'success' => true,
                'message' => "Successfully assigned '{$product->name}' to '{$user->name}'."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during assignment: ' . $e->getMessage()
            ], 500);
        }
    }
}
