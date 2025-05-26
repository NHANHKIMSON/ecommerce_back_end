<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a listing of orders for authenticated user
     */
    public function index()
    {
        $orders = Order::with(['items.product'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return response()->json($orders);
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request)
{

    if (!Auth::check()) {
        return response()->json([
            'message' => 'User not authenticated',
        ], 401);
    }

    $validated = $request->validate([
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
        'shipping_address' => 'required|string|max:255',
        'billing_address' => 'required|string|max:255',
    ]);

    try {
        $total = 0;
        $orderItems = [];

        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);

            // FIX: Use 'instock' instead of 'stock'
            if (!$product || $product->instock < $item['quantity']) {
                return response()->json([
                    'message' => 'Product unavailable or insufficient stock',
                    'product_id' => $item['product_id']
                ], 400);
            }

            $itemTotal = $product->price * $item['quantity'];
            $total += $itemTotal;

            $orderItems[] = [
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'unit_price' => $product->price,
                'total_price' => $itemTotal
            ];

            // Deduct the ordered quantity
            $product->decrement('instock', $item['quantity']);
        }

        $order = Order::create([
            'user_id' => Auth::id(),
            'total_amount' => $total,
            'status' => 'pending',
            'shipping_address' => $validated['shipping_address'],
            'billing_address' => $validated['billing_address'],
        ]);

        $order->items()->createMany($orderItems);

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order->load('items.product')
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Order processing failed',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Display the specified order
     */
    public function show(Order $order)
    {
        // Verify order belongs to authenticated user
        if ($order->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json($order->load('items.product'));
    }

    /**
     * Cancel an order
     */
    public function cancel(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Only allow cancellation for pending orders
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Order cannot be cancelled at this stage'
            ], 400);
        }

        $order->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order
        ]);
    }
}