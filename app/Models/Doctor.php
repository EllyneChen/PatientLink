<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'facility_id',
        'licence_no',
        'specialisation',
        'phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function consentRequests()
    {
        return $this->hasMany(ConsentRecord::class, 'doctor_id');
    }
}
