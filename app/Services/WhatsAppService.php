<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Simulate sending a WhatsApp message.
     */
    public static function send(string $phone, string $message): void
    {
        $logPath = storage_path('logs/whatsapp.log');
        $timestamp = now()->toDateTimeString();
        
        $logMessage = "[{$timestamp}] TO: {$phone} | MSG: {$message}" . PHP_EOL . str_repeat('-', 50) . PHP_EOL;
        
        // Ensure folder exists
        if (!file_exists(storage_path('logs'))) {
            mkdir(storage_path('logs'), 0755, true);
        }

        // Append to storage/logs/whatsapp.log
        file_put_contents($logPath, $logMessage, FILE_APPEND);
        
        // Also log to standard app log for verification
        Log::info("Simulated WhatsApp sent to {$phone}: {$message}");
    }
}
