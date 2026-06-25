<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MohAdmin extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'moh_admins';

    protected $fillable = [
        'user_id',
        'region',
        'clearance_level',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
