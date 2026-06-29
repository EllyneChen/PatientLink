<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    //  Role-specific relationships 
    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function facilityAdmin()
    {
        return $this->hasOne(FacilityAdmin::class);
    }

    public function mohAdmin()
    {
        return $this->hasOne(MohAdmin::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    //  JWTSubject implementation 
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Custom claims embedded in the JWT — used by RoleMiddleware
     * to scope access per FR-D01 / FR-P01 / FR-FA01 / FR-M01.
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
        ];
    }
}
