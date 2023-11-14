<?php

namespace App\Services;

class ErrorMessageService
{
    public static function getErrorMessages(): array
    {
        return [
            'err_loadid' => '🆔 **Empty or NULL Load ID**',
            'err_client' => '👥 **There is no client**',
            'err_amount' => '💰 **Price less than 100**',
            'err_attach' => '📎 **PDF BOL URL is missing or empty**',
            'err_pickaddress' => '🏠 **Pickup address state or zip is missing**',
            'err_deladdress' => '🚚 **Delivery address state or zip is missing**',
            'err_email' => '📧 **No email found in internal notes**',
            'err_pickbol' => '📷 **Less than 20 photos in vehicle data**',
            'err_method' => '💳 **Invalid payment method in vehicle data**',
        ];
    }
}
