<?php

namespace App\Services;

use App\Models\BookingFinalBill;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FbrPosService
{
    /**
     * Synchronize Booking Final Bill with FBR / regional tax API.
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
                'message' => 'FBR POS ID not configured for this branch.'
            ];
        }

        // FBR POS API endpoint
        $endpoint = $branch->fbr_sandbox_mode 
            ? 'https://sandbox.fbr.gov.pk/ims/api/v1/Invoice/PostInvoice' 
            : 'https://api.fbr.gov.pk/ims/api/v1/Invoice/PostInvoice';

        // Tax rate calculations
        $subtotal = (float)$finalBill->subtotal ?: 1.0;
        $taxRate = round((($finalBill->tax_amount / $subtotal) * 100), 2);

        // Prepare line items
        $items = [];
        // 1. Menu Package / Plates charges
        if ($finalBill->package_amount > 0) {
            $items[] = [
                'ItemCode' => $booking->package?->package_code ?? 'PKG',
                'ItemName' => 'Menu Package: ' . ($booking->package?->package_name ?? 'Custom'),
                'Quantity' => $finalBill->guest_count,
                'PCTCode' => '9901.0000', // Regional Service Code for Banquets
                'TaxRate' => $taxRate,
                'SaleValue' => (float)$finalBill->package_amount,
                'TaxCharged' => (float)$finalBill->tax_amount,
                'TotalAmount' => (float)($finalBill->package_amount + $finalBill->tax_amount),
            ];
        }

        // 2. Hall Rent
        if ($finalBill->hall_charges > 0) {
            $items[] = [
                'ItemCode' => 'HALL-RENT',
                'ItemName' => 'Banquet Hall Rental Charges',
                'Quantity' => 1,
                'PCTCode' => '9901.0000',
                'TaxRate' => 0.00, // Hall rent is often tax exempt or built-in
                'SaleValue' => (float)$finalBill->hall_charges,
                'TaxCharged' => 0.00,
                'TotalAmount' => (float)$finalBill->hall_charges,
            ];
        }

        // 3. Add-on services
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
            'USIN' => 'USIN' . $finalBill->id . '-' . rand(100000, 999999),
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
            'PaymentMode' => 1, // Cash / Bank Transfer
            'Items' => $items,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $branch->fbr_pos_key,
                'Content-Type' => 'application/json',
            ])->post($endpoint, $payload);

            $data = $response->json();

            if ($response->successful() && isset($data['ResponseCode']) && $data['ResponseCode'] == 100) {
                // Success path
                $fbrInvoiceNumber = $data['FBRInvoiceNumber'] ?? ('FBR-' . rand(1000000, 9999999));
                $usin = $data['USIN'] ?? $payload['USIN'];
                
                // Construct standard verification URL for QR codes
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
                // Failed FBR response
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
