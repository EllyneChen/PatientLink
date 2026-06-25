<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'location',
        'api_key',
        'status',
    ];

    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }

    public function admins()
    {
        return $this->hasMany(FacilityAdmin::class);
    }

    public function healthRecords()
    {
        return $this->hasMany(HealthRecord::class);
    }

    public function consentRecords()
    {
        return $this->hasMany(ConsentRecord::class);
    }
}
