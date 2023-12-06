<?php

namespace App\Http\Controllers;

use App\Services\AffiliateService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    protected $orderService;

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
        // Validate the incoming request data
        $request->validate([
            'order_id' => 'required|string',
            'subtotal_price' => 'required|numeric',
            'merchant_domain' => 'required|string',
            'discount_code' => 'nullable|string',
            'customer_email' => 'required|email',
            'customer_name' => 'required|string',
        ]);

        // Get the validated data from the request
        $data = $request->only([
            'order_id',
            'subtotal_price',
            'merchant_domain',
            'discount_code',
            'customer_email',
            'customer_name',
        ]);
        return $request;
        // Pass the data to the processOrder method in the OrderService
        $this->orderService->processOrder($data);

        // Respond with a JSON success message
        return response()->json(['message' => 'Order processed successfully']);
    }
}
