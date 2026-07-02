<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TestOtpVerifier extends Command
{
    protected $signature = 'patientlink:verify-otp {phone} {code}';
    protected $description = 'Verifies a cached OTP code against a phone number context';

    public function handle()
    {
        $phone = $this->argument('phone');
        $userTypedCode = $this->argument('code');

        $this->info("🔍 Checking system memory cache for phone: {$phone}...");
        $storedCode = Cache::get("sandbox_otp_{$phone}");

        if (!$storedCode) {
            $this->error("❌ Verification Failed: No active OTP found for this number. It may have expired or was never generated.");
            return Command::FAILURE;
        }

        $this->info("💾 Found stored system record code: {$storedCode}");
        $this->info("⌨️ User input provided: {$userTypedCode}");

        if ((string)$storedCode === (string)$userTypedCode) {
            $this->newLine();
            $this->info("🎉 ==========================================");
            $this->info("✅ SUCCESS! OTP IS VALID & MATCHED SUCCESSFULLY!");
            $this->info("🔒 Secure Data Channel Cleared for PatientLink Middleware.");
            $this->info("=============================================");
            
            Cache::forget("sandbox_otp_{$phone}");
            $this->warn("🗑️ OTP removed from cache tracking table to prevent reuse replay attacks.");
            
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->error("❌ ACCESS DENIED: The verification code typed does not match our records.");
        return Command::FAILURE;
    }
}