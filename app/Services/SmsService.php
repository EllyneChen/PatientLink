<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Support\Facades\Log;

/**
 * SmsService
 * 
 * Handles OTP delivery via Africa's Talking SMS gateway.
 * Implements FR-S01 and FR-S02.
 */
class SmsService
{
    private $sms;

    public function __construct()
    {
        $AT = new AfricasTalking(
            config('services.africastalking.username'),
            config('services.africastalking.api_key')
        );
        $this->sms = $AT->sms();
    }

    /**
     * Send OTP to patient's phone number.
     * FR-S01: Deliver OTP message to patient's registered phone.
     */
    public function sendOtp(string $phone, string $otpCode): bool
    {
        try {
            $message = "PatientLink: Your consent verification code is {$otpCode}. "
                     . "It expires in " . config('services.africastalking.otp_expiry') . " minutes. "
                     . "Do not share this code with anyone.";

            $result = $this->sms->send([
                'to'      => $phone,
                'message' => $message,
                'from'    => config('services.africastalking.sender_id'),
            ]);

            Log::info('OTP SMS sent', [
                'phone'  => $phone,
                'result' => $result,
            ]);

            // FR-S02: Return delivery status
            return $result['status'] === 'success';

        } catch (\Exception $e) {
            Log::error('OTP SMS failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}