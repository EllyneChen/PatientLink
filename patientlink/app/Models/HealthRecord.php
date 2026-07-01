<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HealthRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'patient_nupi',
        'facility_id',
        'doctor_id',
        'summary',
        'encrypted',
    ];

    protected $casts = [
        'summary' => 'array',
        'encrypted' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_nupi', 'nupi');
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
