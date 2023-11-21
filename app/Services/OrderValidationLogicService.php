<?php

namespace App\Services;

use App\Models\Error;
use App\Models\CdCompany;
use Illuminate\Support\Facades\Log;

class OrderValidationLogicService
{
    private const PAYMENT_METHODS = ['Factoring', 'Other', 'Comcheck', 'ACH'];

    public function validateOrder(array $order, array $attachments): Error
    {
        $errorRecord = Error::firstOrNew(['order_id' => $order['id']]);
        $errorRecord->err_count = $errorRecord->exists ? $errorRecord->err_count + 1 : 1;
        $this->setValidationFlags($errorRecord, $order, $attachments);

        return $errorRecord;
    }

    private function setValidationFlags(Error $errorRecord, array $order, array $attachments): void
    {
        $companyName = $order['customer']['name'] ?? null;
        $companyEmail = $order['customer']['contact']['email'] ?? null;

        $errorRecord->fill([
            'err_loadid' => $this->isInvalid($order['number'] ?? null),
            'err_client' => $this->isInvalid($order['customer']['name'] ?? null),
            'err_amount' => $this->isAmountInvalid($order['price'] ?? 0),
            'err_attach' => !$this->hasValidPdfAttachment($attachments),
            'err_pickaddress' => $this->isInvalid($order['pickup']['venue']['address'] ?? null),
            'err_pickaddress_zip' => $this->isInvalid($order['pickup']['venue']['zip'] ?? null),
            'err_deladdress' => $this->isInvalid($order['delivery']['venue']['address'] ?? null),
            'err_deladdress_zip' => $this->isInvalid($order['delivery']['venue']['zip'] ?? null),
            'err_email' => !$this->isCompanyEmailValid($companyName, $companyEmail),
            'err_pickbol' => $this->isPhotoCountInvalid($order['vehicles'][0]['photos'] ?? []),
            'err_method' => $this->hasValidPaymentMethod($order['payment']['terms'] ?? null)
        ]);
    }

    private function isInvalid(?string $value): bool
    {
        return empty($value);
    }

    private function isAmountInvalid(float $amount): bool
    {
        return $amount < 100;
    }

    private function hasValidPdfAttachment(array $attachments): bool
    {
        foreach ($attachments as $attachment) {
            if (str_ends_with(strtolower($attachment['name'] ?? ''), '.pdf') &&
                filter_var($attachment['url'] ?? '', FILTER_VALIDATE_URL)) {
                return true;
            }
        }
        return false;
    }

    private function isCompanyEmailValid(?string $companyName, ?string $companyEmail): bool
    {
        if (!empty($companyEmail) && filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        if (!empty($companyName)) {
            $companyRecord = CdCompany::whereRaw('LOWER(company_name) = ?', [strtolower($companyName)])->first();

            return $companyRecord && !empty($companyRecord->company_email);
        }

        return false;
    }

    private function hasValidPaymentMethod(?string $terms): bool
    {
        return in_array(strtoupper($terms), array_map('strtoupper', self::PAYMENT_METHODS), true);
    }

    private function hasEmail(array $notes): bool
    {
        if (empty($notes)) {
            return false;
        }

        foreach ($notes as $note) {
            if (filter_var($note, FILTER_VALIDATE_EMAIL)) {
                return true;
            }
        }
        return false;
    }

    private function isPhotoCountInvalid(array $photos): bool
    {
        return count($photos) < 20;
    }
}
