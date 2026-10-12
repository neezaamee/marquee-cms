<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestPralConnection extends Command
{
    protected $signature = 'pral:test-connection';
    protected $description = 'Test outbound connectivity and IP against PRAL Sandbox endpoint';

    public function handle(): int
    {
        $this->info("=== PRAL Sandbox Connectivity Diagnostic ===");

        // 1. Detect Outbound IP
        $this->newLine();
        $this->comment("1. Checking server outbound IP via ipify...");
        try {
            $ipResponse = Http::timeout(5)->withoutVerifying()->get('https://api.ipify.org?format=json');
            $outboundIp = $ipResponse->json('ip') ?? 'Unknown';
            $this->info("   -> Detected Server Outbound IP: {$outboundIp}");
        } catch (\Throwable $e) {
            $this->warn("   -> Could not detect outbound IP: " . $e->getMessage());
            $outboundIp = 'Unknown';
        }

        // 2. Test PRAL endpoint with clean Guzzle options (no custom cipher, without verifying)
        $endpoint = 'https://ims.pral.com.pk/ims/sandbox/api/Live/PostData';
        $this->newLine();
        $this->comment("2. Testing PRAL Sandbox endpoint ({$endpoint})...");

        // Test A: Clean default request
        $this->line("   [Test A] Standard Guzzle request (withoutVerifying)...");
        try {
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

            $response = curl_exec($ch);
            $errNo = curl_errno($ch);
            $errMsg = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errNo !== 0) {
                $this->error("   -> Connection Failed! cURL error ({$errNo}): {$errMsg}");
                if ($errNo === 35 || str_contains(strtolower($errMsg), 'eof') || str_contains(strtolower($errMsg), 'handshake')) {
                    $this->warn("   -> Diagnosis: PRAL firewall/WAF terminated the TLS handshake (unexpected EOF).");
                    $this->warn("      This almost always indicates PRAL is geo-blocking outbound IP [{$outboundIp}].");
                }
            } else {
                $this->info("   -> Connection Succeeded! HTTP Code: {$httpCode}");
                $this->line("   -> Response snippet: " . substr((string)$response, 0, 150));
            }
        } catch (\Throwable $e) {
            $this->error("   -> Exception: " . $e->getMessage());
        }

        $this->newLine();
        $this->info("=== Diagnostic Complete ===");
        return Command::SUCCESS;
    }
}
