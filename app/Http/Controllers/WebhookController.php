<?php

namespace App\Http\Controllers;

use App\Services\OrderValidationService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    protected OrderValidationService $orderValidationService;

    public function __construct(OrderValidationService $orderValidationService)
    {
        $this->orderValidationService = $orderValidationService;
    }

    public function validateOrder(Request $request): \Illuminate\Http\JsonResponse
    {
        $validatedData = $request->validate(['orderID' => 'required']);

        try {
            $result = $this->orderValidationService->validateOrder($validatedData['orderID']);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred during validation.'], 500);
        }
    }
}
