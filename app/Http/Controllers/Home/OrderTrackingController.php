<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderTrackingController extends Controller
{
    public function create()
    {
        return view('pages.home.orders.track');
    }

    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:32'],
            'customer_email' => ['required', 'email', 'max:255'],
        ]);

        $order = Order::with(['items', 'payment'])
            ->where('order_number', $validated['order_number'])
            ->first();

        if (! $order || ! hash_equals(strtolower($order->customer_email), strtolower($validated['customer_email']))) {
            throw ValidationException::withMessages([
                'order' => 'We could not find an order matching those details.',
            ]);
        }

        return view('pages.home.orders.track', compact('order'));
    }
}
