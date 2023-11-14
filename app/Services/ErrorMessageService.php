<?php

namespace App\Services;

class ErrorMessageService
{
    public static function getErrorMessages(): array
    {
        return [
            'err_loadid' => '<b>🆔 Empty or NULL Load ID</b>',
            'err_client' => '<b>👥 There is no client</b>',
            'err_amount' => '<b>💰 Price less than 100</b>',
            'err_attach' => '<b>📎 PDF BOL URL is missing or empty</b>',
            'err_pickaddress' => '<b>🏠 Pickup address state or zip is missing</b>',
            'err_deladdress' => '<b>🚚 Delivery address state or zip is missing</b>',
            'err_email' => '<b>📧 No email found in internal notes</b>',
            'err_pickbol' => '<b>📷 Less than 20 photos in vehicle data</b>',
            'err_method' => '<b>💳 Invalid payment method in vehicle data</b>',
        ];
    }
}
