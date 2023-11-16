<?php

namespace App\Services;

use App\Models\Error;

class OrderValidationLogicService
{
    public function validateOrder(array $order): Error
    {
        $errorRecord = Error::firstOrNew(['order_id' => $order['id']]);
        $this->setValidationFlags($errorRecord, $order);
        return $errorRecord;
    }

    private function setValidationFlags(Error $errorRecord, array $order): void
    {
        $errorRecord->fill(attributes: [
            'err_loadid' => $this->isInvalid($order['vehicles'][0]['id'] ?? null),
            'err_client' => $this->isInvalid($order['customer']['name'] ?? null),
            'err_amount' => $order['price'] < 100,
            'err_attach' => $this->isInvalid($order['pdf_bol_url'] ?? null),
            'err_pickaddress' => $this->isAddressInvalid($order['pickup']['venue'] ?? []),
            'err_deladdress' => $this->isAddressInvalid($order['delivery']['venue'] ?? []),
            'err_email' => !$this->hasEmail($order['internal_notes'] ?? []),
            'err_pickbol' => count($order['vehicles'][0]['photos'] ?? []) < 20,
            'err_method' => $this->hasPaymentMethodError($order['vehicles'] ?? [])
        ]);
    }

    private function isInvalid($value): bool
    {
        return empty($value);
    }

    private function isAddressInvalid(array $venue): bool
    {
        return empty($venue['state']) || empty($venue['zip']);
    }

    private function hasPaymentMethodError(array $vehicles): bool
    {
        $paymentMethods = ['Factoring', 'Other', 'Comcheck', 'ACH'];
        foreach ($vehicles as $vehicle) {
            foreach ($paymentMethods as $method) {
                if (isset($vehicle[$method])) {
                    return true;
                }
            }
        }
        return false;
    }

    private function hasEmail(array $notes): bool
    {
        foreach ($notes as $note) {
            if (filter_var($note, FILTER_VALIDATE_EMAIL)) {
                return true;
            }
        }
        return false;
    }
}
