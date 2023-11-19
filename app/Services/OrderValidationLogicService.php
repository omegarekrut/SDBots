<?php

namespace App\Services;

use App\Models\Error;
use Illuminate\Support\Facades\Log;

class OrderValidationLogicService
{
    const PAYMENT_METHODS = ['Factoring', 'Other', 'Comcheck', 'ACH'];

    public function validateOrder(array $order, array $attachments): Error
    {
        $errorRecord = Error::firstOrNew(['order_id' => $order['id']]);
        $this->setValidationFlags($errorRecord, $order, $attachments);
        return $errorRecord;
    }

    private function setValidationFlags(Error $errorRecord, array $order, array $attachments): void
    {
        $errorRecord->fill(attributes: [
            'err_loadid' => $this->isInvalid($order['number'] ?? null),
            'err_client' => $this->isInvalid($order['customer']['name'] ?? null),
            'err_amount' => $order['price'] < 100,
            'err_attach' => $this->hasValidPdfAttachment($attachments),
            'err_pickaddress' => $this->isAddressInvalid($order['pickup']['venue']['address'] ?? null),
            'err_pickaddress_zip' => $this->isZipInvalid($order['pickup']['venue']['zip'] ?? null),
            'err_deladdress' => $this->isAddressInvalid($order['delivery']['venue']['address'] ?? null),
            'err_deladdress_zip' => $this->isZipInvalid($order['delivery']['venue']['zip'] ?? null),
            'err_email' => !$this->hasEmail($order['internal_notes'] ?? []),
            'err_pickbol' => count($order['vehicles'][0]['photos'] ?? []) < 20, //$this->isInvalid($order['pdf_bol_url'] ?? null)
            'err_method' => $this->hasPaymentMethodError($order['payment']['terms'] ?? null)
        ]);
    }

    private function isInvalid($value): bool
    {
        return empty($value);
    }

    private function isAddressInvalid(?string $address): bool
    {
        return empty($address);
    }

    private function isZipInvalid(?string $zip): bool
    {
        return empty($zip);
    }

    private function hasValidPdfAttachment(array $attachments): bool
    {
        foreach ($attachments as $attachment) {
            if (str_ends_with(strtolower($attachment['name'] ?? ''), '.pdf') &&
                filter_var($attachment['url'] ?? '', FILTER_VALIDATE_URL)) {
                return false;
            }
        }
        return true;
    }

    private function hasPaymentMethodError(?string $terms): bool
    {
        if ($terms === null) {
            return true;
        }

        $termsUpper = strtoupper($terms);
        foreach (self::PAYMENT_METHODS as $method) {
            if (strtoupper($method) === $termsUpper) {
                return true;
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
