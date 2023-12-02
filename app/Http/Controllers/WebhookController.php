<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Pass the necessary data to the process order method
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Validate and extract necessary data from the webhook request
        $data = $request->validate([
            'order_id' => 'required|string',
            'subtotal_price' => 'required|numeric',
            'merchant_domain' => 'required|string',
            'discount_code' => 'nullable|string',
            'customer_email' => 'required|email',
            'customer_name' => 'required|string',
        ]);

        // Process the order using the OrderService
        $this->orderService->processOrder($data);

        // Return a JSON response indicating successful processing
        return response()->json(['message' => 'Order processed successfully']);
    }
}
