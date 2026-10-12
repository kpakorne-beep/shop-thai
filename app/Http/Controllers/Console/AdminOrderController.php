<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Services\OrderNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending_payment,processing,shipped,delivered,cancelled'],
            'payment_status' => ['nullable', 'in:awaiting_transfer,pending_review,confirmed,rejected,cancelled'],
        ]);

        $orders = Order::with('payment')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('order_number', 'like', '%'.$search.'%')
                        ->orWhere('customer_email', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['payment_status'] ?? null, function ($query, $paymentStatus) {
                $query->whereHas('payment', function ($query) use ($paymentStatus) {
                    $query->where('status', $paymentStatus);
                });
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(20)
            ->appends(array_filter($filters, function ($value) {
                return $value !== null && $value !== '';
            }));

        return $this->view('pages.admin.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items', 'payment.reviewer']);

        return $this->view('pages.admin.orders.show', compact('order'));
    }

    public function proof(Order $order)
    {
        $order->load('payment');
        abort_unless($order->payment && $order->payment->proof_path, 404);

        return Storage::disk('local')->download($order->payment->proof_path);
    }

    public function reviewPayment(Request $request, Order $order, OrderNotificationService $notifications)
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:confirmed,rejected'],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['decision'] === Payment::STATUS_REJECTED && empty(trim($validated['review_note'] ?? ''))) {
            throw ValidationException::withMessages([
                'review_note' => 'Provide a reason when rejecting payment proof.',
            ]);
        }

        DB::transaction(function () use ($order, $validated, $request) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $payment = $lockedOrder->payment()->lockForUpdate()->firstOrFail();

            if ($payment->status !== Payment::STATUS_PENDING_REVIEW
                || $lockedOrder->status !== Order::STATUS_PENDING_PAYMENT) {
                throw ValidationException::withMessages([
                    'decision' => 'This payment is no longer awaiting review.',
                ]);
            }

            $payment->update([
                'status' => $validated['decision'],
                'reviewed_by' => $request->user()->id,
                'review_note' => $validated['review_note'] ?? null,
            ]);

            if ($validated['decision'] === Payment::STATUS_CONFIRMED) {
                $lockedOrder->update(['status' => Order::STATUS_PROCESSING]);
            }
        });

        $message = $validated['decision'] === Payment::STATUS_CONFIRMED
            ? 'Your payment has been confirmed.'
            : 'Your payment proof was rejected. Please review the note and submit a new proof.';
        $notifications->send($order->fresh(), $message);

        return redirect()->route('admin.orders.show', $order)->with('status', 'Payment review saved.');
    }

    public function updateStatus(Request $request, Order $order, OrderNotificationService $notifications)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:shipped,delivered,cancelled'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($order, $validated) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $payment = $lockedOrder->payment()->lockForUpdate()->first();

            if ($validated['status'] === Order::STATUS_SHIPPED) {
                if ($lockedOrder->status !== Order::STATUS_PROCESSING) {
                    throw ValidationException::withMessages([
                        'status' => 'Only paid orders being processed can be marked as shipped.',
                    ]);
                }

                $lockedOrder->update([
                    'status' => Order::STATUS_SHIPPED,
                    'tracking_number' => $validated['tracking_number'] ?? null,
                    'shipped_at' => now(),
                ]);

                return;
            }

            if ($validated['status'] === Order::STATUS_DELIVERED) {
                if ($lockedOrder->status !== Order::STATUS_SHIPPED) {
                    throw ValidationException::withMessages([
                        'status' => 'Only shipped orders can be marked as delivered.',
                    ]);
                }

                $lockedOrder->update([
                    'status' => Order::STATUS_DELIVERED,
                    'delivered_at' => now(),
                ]);

                return;
            }

            if ($lockedOrder->status !== Order::STATUS_PENDING_PAYMENT
                || ($payment && $payment->status === Payment::STATUS_CONFIRMED)) {
                throw ValidationException::withMessages([
                    'status' => 'Only unpaid orders can be cancelled. Paid order cancellation requires a refund workflow.',
                ]);
            }

            $items = OrderItem::where('order_id', $lockedOrder->id)
                ->whereNotNull('product_id')
                ->orderBy('product_id')
                ->get();
            $productIds = $items->pluck('product_id')->unique()->sort()->values();
            $products = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                if ($product) {
                    $product->increment('stock', $item->quantity);
                }
            }

            $lockedOrder->update(['status' => Order::STATUS_CANCELLED]);
            if ($payment) {
                $payment->update([
                    'status' => Payment::STATUS_CANCELLED,
                    'reviewed_by' => null,
                ]);
            }
        });

        $messages = [
            Order::STATUS_SHIPPED => 'Your order has been shipped.',
            Order::STATUS_DELIVERED => 'Your order has been marked as delivered.',
            Order::STATUS_CANCELLED => 'Your order has been cancelled.',
        ];
        $notifications->send($order->fresh(), $messages[$validated['status']]);

        return redirect()->route('admin.orders.show', $order)->with('status', 'Order status updated.');
    }
}
