<?php

namespace App\Services;

use App\Models\BookingFinalBill;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FbrPosService
{
    /**
     * Official PRAL testing sandbox token from PRA POS Component Manual (Jan 2026).
     */
    public const PRA_SANDBOX_TOKEN = '24d8fab3-f2e9-398f-ae17-b387125ec4a2';

    /**
     * Synchronize Booking Final Bill with FBR / PRA / regional tax API.
     */
    public function syncFinalBill(BookingFinalBill $finalBill): array
    {
        $booking = $finalBill->booking;
        if (!$booking) {
            return [
                'success' => false,
                'message' => 'No booking associated with this final bill.'
            ];
        }

        $branch = $booking->hall->branch ?? null;
        if (!$branch || !$branch->fbr_pos_id) {
            return [
                'success' => false,
                'message' => 'POS ID not configured for this branch.'
            ];
        }

        $marquee = $branch->marquee ?? $booking->marquee;
        $taxAuthority = strtoupper($marquee->tax_authority ?? 'FBR');
        $isSandbox = (bool) $branch->fbr_sandbox_mode;
        $connectionType = strtolower($branch->pos_connection_type ?? 'cloud'); // 'cloud' or 'local'

        // Tax rate calculations
        $subtotal = (float)$finalBill->subtotal ?: 1.0;
        $taxRate = round((($finalBill->tax_amount / $subtotal) * 100), 2);
        $usin = $finalBill->usin ?: ('USIN' . $finalBill->id . '-' . rand(100000, 999999));

        if ($taxAuthority === 'PRA') {
            return $this->syncPraInvoice($finalBill, $booking, $branch, $taxRate, $usin, $isSandbox, $connectionType);
        }

        return $this->syncFbrInvoice($finalBill, $booking, $branch, $taxRate, $usin, $isSandbox);
    }

    /**
     * Handle synchronization with Punjab Revenue Authority (PRA) e-IMS.
     */
    protected function syncPraInvoice(
        BookingFinalBill $finalBill,
        $booking,
        $branch,
        float $taxRate,
        string $usin,
        bool $isSandbox,
        string $connectionType
    ): array {
        if ($connectionType === 'local') {
            // Mode A: Local Software Fiscal Device (SFD) on localhost:8524
            $endpoint = 'http://localhost:8524/api/IMSFiscal/GetInvoiceNumberByModel';
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];
        } else {
            // Mode B: Direct Cloud Web API to PRAL
            $endpoint = $isSandbox
                ? 'https://ims.pral.com.pk/ims/sandbox/api/Live/PostData'
                : 'https://ims.pral.com.pk/ims/production/api/Live/PostData';

            $token = $branch->fbr_pos_key;
            if ($isSandbox && (empty($token) || strlen(trim($token)) <= 12)) {
                $token = self::PRA_SANDBOX_TOKEN;
            }

            $headers = [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];
        }

        // Line items for PRA
        $items = [];
        if ($finalBill->package_amount > 0) {
            $items[] = [
                'ItemCode' => $booking->package?->package_code ?? 'PKG-01',
                'ItemName' => 'Menu Package: ' . ($booking->package?->package_name ?? 'Banquet'),
                'Quantity' => (float)$finalBill->guest_count,
                'PCTCode' => '99010000',
                'TaxRate' => (float)$taxRate,
                'SaleValue' => (float)$finalBill->package_amount,
                'TotalAmount' => (float)($finalBill->package_amount + $finalBill->tax_amount),
                'TaxCharged' => (float)$finalBill->tax_amount,
                'Discount' => 0.0,
                'FurtherTax' => 0.0,
                'InvoiceType' => 1,
                'RefUSIN' => null,
            ];
        }

        if ($finalBill->hall_charges > 0) {
            $items[] = [
                'ItemCode' => 'HALL-RENT',
                'ItemName' => 'Banquet Hall Rental Charges',
                'Quantity' => 1.0,
                'PCTCode' => '99010000',
                'TaxRate' => 0.0,
                'SaleValue' => (float)$finalBill->hall_charges,
                'TotalAmount' => (float)$finalBill->hall_charges,
                'TaxCharged' => 0.0,
                'Discount' => 0.0,
                'FurtherTax' => 0.0,
                'InvoiceType' => 1,
                'RefUSIN' => null,
            ];
        }

        foreach ($finalBill->extraServices as $addon) {
            $items[] = [
                'ItemCode' => 'ADDON-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $addon->service_name), 0, 8)),
                'ItemName' => $addon->service_name,
                'Quantity' => (float)$addon->quantity,
                'PCTCode' => '99010000',
                'TaxRate' => 0.0,
                'SaleValue' => (float)$addon->total_price,
                'TotalAmount' => (float)$addon->total_price,
                'TaxCharged' => 0.0,
                'Discount' => 0.0,
                'FurtherTax' => 0.0,
                'InvoiceType' => 1,
                'RefUSIN' => null,
            ];
        }

        if (empty($items)) {
            $items[] = [
                'ItemCode' => 'SVC-GEN',
                'ItemName' => 'Banquet Services',
                'Quantity' => 1.0,
                'PCTCode' => '99010000',
                'TaxRate' => (float)$taxRate,
                'SaleValue' => (float)$finalBill->subtotal,
                'TotalAmount' => (float)$finalBill->grand_total,
                'TaxCharged' => (float)$finalBill->tax_amount,
                'Discount' => (float)$finalBill->discount_amount,
                'FurtherTax' => 0.0,
                'InvoiceType' => 1,
                'RefUSIN' => null,
            ];
        }

        // PRA JSON payload matching Section 6.1 & 6.4.3 of PRA Manual
        $payload = [
            'InvoiceNumber' => '',
            'POSID' => is_numeric($branch->fbr_pos_id) ? (int)$branch->fbr_pos_id : $branch->fbr_pos_id,
            'USIN' => $usin,
            'DateTime' => now()->format('Y-m-d H:i:s'),
            'BuyerPNTN' => null,
            'BuyerCNIC' => null,
            'BuyerName' => $booking->customer?->full_name ?? 'Guest',
            'BuyerPhoneNumber' => $booking->customer?->phone_number ?? '03000000000',
            'TotalBillAmount' => (float)$finalBill->grand_total,
            'TotalQuantity' => (float)count($items),
            'TotalSaleValue' => (float)$finalBill->subtotal,
            'TotalTaxCharged' => (float)$finalBill->tax_amount,
            'Discount' => (float)$finalBill->discount_amount,
            'FurtherTax' => 0.0,
            'PaymentMode' => 1,
            'RefUSIN' => null,
            'InvoiceType' => 1,
            'Items' => $items,
        ];

        try {
            $response = Http::timeout(10)
                ->withoutVerifying()
                ->withHeaders($headers)
                ->post($endpoint, $payload);

            $data = $response->json() ?? [];

            $isSuccess = $response->successful() && (
                (isset($data['Code']) && (string)$data['Code'] === '100') ||
                (isset($data['ResponseCode']) && (int)$data['ResponseCode'] === 100)
            );

            if ($isSuccess) {
                $invoiceNumber = $data['InvoiceNumber'] ?? $data['FBRInvoiceNumber'] ?? ('PRA-' . rand(1000000, 9999999));
                $qrUrl = "https://e.pra.punjab.gov.pk/VerifyInvoice?InvoiceNo={$invoiceNumber}";

                $finalBill->update([
                    'fbr_invoice_number' => $invoiceNumber,
                    'fbr_sync_status' => 'synced',
                    'fbr_sync_time' => now(),
                    'usin' => $usin,
                    'qr_code' => $qrUrl,
                    'fbr_response_message' => 'Invoice uploaded successfully to PRA e-IMS.',
                ]);

                return [
                    'success' => true,
                    'fbr_invoice_number' => $invoiceNumber,
                    'usin' => $usin,
                ];
            } else {
                $errorMsg = $data['Response'] ?? $data['ResponseMessage'] ?? $data['fault']['message'] ?? 'PRA validation error response.';
                Log::warning("PRA Sync Error for Bill ID {$finalBill->id}: " . $errorMsg);

                $finalBill->update([
                    'fbr_sync_status' => 'failed',
                    'fbr_sync_time' => now(),
                    'fbr_response_message' => $errorMsg,
                ]);

                return [
                    'success' => false,
                    'message' => $errorMsg,
                ];
            }
        } catch (\Throwable $e) {
            Log::error("PRA Connection Exception: " . $e->getMessage());

            $finalBill->update([
                'fbr_sync_status' => 'failed',
                'fbr_sync_time' => now(),
                'fbr_response_message' => 'Network/Connection error: ' . $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Network connection to PRA failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle synchronization with Federal Board of Revenue (FBR).
     */
    protected function syncFbrInvoice(
        BookingFinalBill $finalBill,
        $booking,
        $branch,
        float $taxRate,
        string $usin,
        bool $isSandbox
    ): array {
        $endpoint = $isSandbox
            ? 'https://sandbox.fbr.gov.pk/ims/api/v1/Invoice/PostInvoice'
            : 'https://api.fbr.gov.pk/ims/api/v1/Invoice/PostInvoice';

        $items = [];
        if ($finalBill->package_amount > 0) {
            $items[] = [
                'ItemCode' => $booking->package?->package_code ?? 'PKG',
                'ItemName' => 'Menu Package: ' . ($booking->package?->package_name ?? 'Custom'),
                'Quantity' => $finalBill->guest_count,
                'PCTCode' => '9901.0000',
                'TaxRate' => $taxRate,
                'SaleValue' => (float)$finalBill->package_amount,
                'TaxCharged' => (float)$finalBill->tax_amount,
                'TotalAmount' => (float)($finalBill->package_amount + $finalBill->tax_amount),
            ];
        }

        if ($finalBill->hall_charges > 0) {
            $items[] = [
                'ItemCode' => 'HALL-RENT',
                'ItemName' => 'Banquet Hall Rental Charges',
                'Quantity' => 1,
                'PCTCode' => '9901.0000',
                'TaxRate' => 0.00,
                'SaleValue' => (float)$finalBill->hall_charges,
                'TaxCharged' => 0.00,
                'TotalAmount' => (float)$finalBill->hall_charges,
            ];
        }

        foreach ($finalBill->extraServices as $addon) {
            $items[] = [
                'ItemCode' => 'ADDON-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $addon->service_name), 0, 8)),
                'ItemName' => $addon->service_name,
                'Quantity' => $addon->quantity,
                'PCTCode' => '9901.0000',
                'TaxRate' => 0.00,
                'SaleValue' => (float)$addon->total_price,
                'TaxCharged' => 0.00,
                'TotalAmount' => (float)$addon->total_price,
            ];
        }

        $payload = [
            'InvoiceNumber' => 'SYS-' . $finalBill->id . '-' . date('YmdHis'),
            'POSID' => $branch->fbr_pos_id,
            'USIN' => $usin,
            'DateTime' => now()->toDateTimeString(),
            'BuyerName' => $booking->customer->full_name,
            'BuyerPhoneNumber' => $booking->customer->phone_number,
            'TotalQuantity' => count($items),
            'TotalBillAmount' => (float)$finalBill->grand_total,
            'TotalSaleValue' => (float)$finalBill->subtotal,
            'TotalTaxCharged' => (float)$finalBill->tax_amount,
            'Discount' => (float)$finalBill->discount_amount,
            'FurtherTax' => 0.0,
            'TaxRate' => $taxRate,
            'PaymentMode' => 1,
            'Items' => $items,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $branch->fbr_pos_key,
                'Content-Type' => 'application/json',
            ])->post($endpoint, $payload);

            $data = $response->json();

            if ($response->successful() && isset($data['ResponseCode']) && $data['ResponseCode'] == 100) {
                $fbrInvoiceNumber = $data['FBRInvoiceNumber'] ?? ('FBR-' . rand(1000000, 9999999));
                $usin = $data['USIN'] ?? $payload['USIN'];
                $qrUrl = "https://verification.fbr.gov.pk/verify?invoice={$fbrInvoiceNumber}&usin={$usin}";

                $finalBill->update([
                    'fbr_invoice_number' => $fbrInvoiceNumber,
                    'fbr_sync_status' => 'synced',
                    'fbr_sync_time' => now(),
                    'usin' => $usin,
                    'qr_code' => $qrUrl,
                    'fbr_response_message' => 'Invoice uploaded successfully to FBR/regional database.',
                ]);

                return [
                    'success' => true,
                    'fbr_invoice_number' => $fbrInvoiceNumber,
                    'usin' => $usin,
                ];
            } else {
                $errorMsg = $data['ResponseMessage'] ?? 'Unknown FBR error validation response.';
                Log::warning("FBR Sync Error for Bill ID {$finalBill->id}: " . $errorMsg);

                $finalBill->update([
                    'fbr_sync_status' => 'failed',
                    'fbr_sync_time' => now(),
                    'fbr_response_message' => $errorMsg,
                ]);

                return [
                    'success' => false,
                    'message' => $errorMsg,
                ];
            }
        } catch (\Throwable $e) {
            Log::error("FBR Connection Exception: " . $e->getMessage());

            $finalBill->update([
                'fbr_sync_status' => 'failed',
                'fbr_sync_time' => now(),
                'fbr_response_message' => 'Network/Connection error: ' . $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Network connection failed.'
            ];
        }
    }
}
