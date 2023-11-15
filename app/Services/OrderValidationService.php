<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class OrderValidationService
{
    public function validateOrder($orderID): array
    {
        try {
            $exitCode = Artisan::call('validate:order-data', ['orderID' => $orderID]);

            return [
                'success' => $exitCode === 0,
                'message' => $exitCode === 0 ? 'Validation successful.' : 'Validation failed.'
            ];
        } catch (\Exception $e) {
            Log::error('Error in order validation: ' . $e->getMessage());
            throw $e;
        }
    }
}
