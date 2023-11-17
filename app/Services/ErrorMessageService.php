<?php

namespace App\Services;

class ErrorMessageService
{
    public static function getErrorMessages(): array
    {
        return [
            'err_loadid' => '🆔 *Empty Load ID*',
            'err_client' => '👥 *There is no client*',
            'err_amount' => '💰 *Price less than 100*',
            'err_attach' => '📎 *PDF is missing or empty \(DispatchSheet\)*',
            'err_pickaddress' => '🏠 *Pickup address is missing*',
            'err_pickaddress_zip' => '🏠 *Pickup address zip code is missing*',
            'err_deladdress' => '🚚 *Delivery address is missing*',
            'err_deladdress_zip' => '🚚 *Delivery address zip code is missing*',
            'err_email' => '📧 *No email found in internal notes*',
            'err_pickbol' => '📷 *Less than 20 photos in vehicle data*',
            'err_method' => '💳 *Invalid payment method in vehicle data*',
        ];
    }
}
