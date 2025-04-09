<?php

namespace App\Http\Controllers\Api;

use App\Contracts\OrderServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Jobs\UpdateOrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderServiceInterface $orderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Order::query();

        if ($request->has('name')) {
            $query->where('name', 'like', "%{$request->input('name')}%");
        }

        // TODO: Weitere Filter implementieren (z.B. nach Status, Datum)
        // FIXME: Bei sehr vielen Orders sollten wir hier pagination einbauen
        $query->orderBy('name')
            ->orderBy('created_at', 'desc');

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Order::getTypes())],
        ]);

        $order = Order::create($validated);
        $order = $this->orderService->createOrder($order);

        return response()->json($order, 201);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json($order);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(Order::getStatuses())],
        ]);

        if (isset($validated['status'])) {
            $order->status = $validated['status'];
            $order->save();
        } else {
            // If no status is provided, dispatch the job to check and update the status
            UpdateOrderStatus::dispatch($order);
        }

        return response()->json($order);
    }

    public function destroy(Order $order): JsonResponse
    {
        if ($order->status !== Order::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'Only completed orders can be deleted',
            ], 422);
        }

        if ($order->external_id) {
            // Könnte fehlschlagen - im Echtbetrieb vielleicht in eine Queue auslagern?
            // Erstmal so lassen, API kann ja wiederholt aufgerufen werden.
            $this->orderService->deleteOrder($order->external_id);
        }

        $order->delete();

        return response()->json(null, 204);
    }
} 