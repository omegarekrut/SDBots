<?php

namespace App\Http\Controllers;

use App\Services\OrderValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected OrderValidationService $orderValidationService;

    public function __construct(OrderValidationService $orderValidationService)
    {
        $this->orderValidationService = $orderValidationService;
    }

    public function validateOrder(Request $request): \Illuminate\Http\JsonResponse
    {
        $validatedData = $request->validate([
            'chat_id' => 'required|string',
            'carrier_name' => 'required|string',
            'order_id' => 'required|integer',
        ]);

        Log::info('Validated data:', $validatedData);

        try {
            // Assuming you want to pass the entire validated data to the service
            $result = $this->orderValidationService->validateOrder($validatedData);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred during validation: ' . $e->getMessage()], 500);
        }
    }
}
