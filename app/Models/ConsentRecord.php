<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ConsentRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_nupi',
        'doctor_id',
        'facility_id',
        'otp_hash',
        'status',
        'expires_at',
        'resolved_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_nupi', 'nupi');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * FR-P03: Check whether the supplied OTP matches the hashed
     * OTP and the request has not expired.
     */
    public function verifyOtp(string $otpCode): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        if (Carbon::now()->greaterThan($this->expires_at)) {
            $this->update(['status' => 'expired', 'resolved_at' => now()]);
            return false;
        }

        return Hash::check($otpCode, $this->otp_hash);
    }
}
