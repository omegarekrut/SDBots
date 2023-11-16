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

    public function validateOrder($id): \Illuminate\Http\JsonResponse
    {
        try {
            $result = $this->orderValidationService->validateOrder($id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred during validation.'], 500);
        }
    }
}
