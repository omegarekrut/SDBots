<?php

namespace App\Services;

class ErrorMessageService
{
    public static function getErrorMessages(): array
    {
        return [
            'err_loadid' => '🆔 *bold \*Empty or NULL Load ID*',
            'err_client' => '👥 *bold \*There is no client*',
            'err_amount' => '💰 *bold \*Price less than 100*',
            'err_attach' => '📎 *bold \*PDF BOL URL is missing or empty*',
            'err_pickaddress' => '🏠 *bold \*Pickup address state or zip is missing*',
            'err_deladdress' => '🚚 *bold \*Delivery address state or zip is missing*',
            'err_email' => '📧 *bold \*No email found in internal notes*',
            'err_pickbol' => '📷 *bold \*Less than 20 photos in vehicle data*',
            'err_method' => '💳 *bold \*Invalid payment method in vehicle data*',
        ];
    }
}
