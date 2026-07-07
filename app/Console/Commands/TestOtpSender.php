<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use AfricasTalking\SDK\AfricasTalking;

class TestOtpSender extends Command
{
    protected $signature   = 'patientlink:test-otp {phone}';
    protected $description = 'Generates an OTP and sends it via Africa\'s Talking Sandbox SMS';

    public function handle()
    {
        $phone = $this->argument('phone');

        // ── SSL fix for Windows/XAMPP ──────────────────────────────
        $certPaths = [
            'C:\\php\\cacert.pem',
            'C:\\xampp\\php\\extras\\ssl\\cacert.pem',
            'C:\\xampp\\php\\cacert.pem',
        ];
        foreach ($certPaths as $path) {
            if (file_exists($path)) {
                putenv("CURL_CA_BUNDLE={$path}");
                putenv("SSL_CERT_FILE={$path}");
                $this->info("🔒 SSL cert loaded from: {$path}");
                break;
            }
        }

        // ── Step 1: Generate OTP ───────────────────────────────────
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->info("🔑 Generated OTP: {$otp}");

        // ── Step 2: Cache OTP for 5 minutes ───────────────────────
        Cache::put("otp_{$phone}", $otp, now()->addMinutes(5));
        $this->info("💾 OTP cached for 5 minutes.");

        // ── Step 3: Resolve & validate AT credentials ──────────────
        $username = config('services.africastalking.username') ?: 'sandbox';
        $apiKey   = config('services.africastalking.api_key');

        $this->info("🚀 Connecting to Africa's Talking Sandbox...");
        $this->line("   Using username: {$username}");

        if (empty($apiKey)) {
            $this->error("❌ Missing AT_API_KEY. Set it in your .env file, then run: php artisan config:clear");
            return Command::FAILURE;
        }

        try {
            $AT  = new AfricasTalking($username, $apiKey);
            $sms = $AT->sms();

            $message = "PatientLink: Your consent verification code is {$otp}. "
                     . "It expires in 5 minutes. Do not share this code.";

            $result = $sms->send([
                'to'      => $phone,
                'message' => $message,
            ]);

            $this->newLine();
            $this->info("✅ SUCCESS! Africa's Talking accepted the request.");
            $this->info("📱 Check the SMS Simulator on your Africa's Talking dashboard.");
            $this->newLine();

            dump($result);

            return Command::SUCCESS;

        } catch (\Exception $e) {

            // ── Real send failed — fall back to local simulation ───
            $this->newLine();
            $this->warn("⚠️  Real SMS failed. Running local simulation instead.");
            $this->warn("   Error: " . $e->getMessage());
            $this->newLine();

            $simulatedResponse = [
                'SMSMessageData' => [
                    'Message'    => 'Sent to 1/1 Total Recipients',
                    'Recipients' => [[
                        'statusCode' => 101,
                        'number'     => $phone,
                        'status'     => 'Success',
                        'cost'       => 'KES 0.0000',
                        'messageId'  => 'AT_MOCK_' . Str::random(10),
                        'text'       => "PatientLink OTP: {$otp}",
                    ]]
                ]
            ];

            $this->info("✅ LOCAL SIMULATION SUCCESS");
            $this->info("🔑 OTP for testing: {$otp}");
            $this->info("📋 Use this OTP to test the verify command:");
            $this->info("   php artisan patientlink:verify-otp {$phone} {$otp}");
            $this->newLine();

            dump($simulatedResponse);

            return Command::SUCCESS;
        }
    }
}