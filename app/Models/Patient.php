<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'nupi',
        'dob',
        'phone',
        'next_of_kin_name',
        'next_of_kin_phone',
        'data_sharing_consent',
    ];

    protected $casts = [
        'dob' => 'date',
        'data_sharing_consent' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function healthRecords()
    {
        return $this->hasMany(HealthRecord::class, 'patient_nupi', 'nupi');
    }

    public function consentRecords()
    {
        return $this->hasMany(ConsentRecord::class, 'patient_nupi', 'nupi');
    }
}
