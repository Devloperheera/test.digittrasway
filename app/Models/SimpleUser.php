<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class SimpleUser extends Authenticatable implements JWTSubject
{
    protected $table = 'users';

    protected $fillable = [
        'contact_number', 'name', 'email', 'password', 'is_verified', 'is_completed'
    ];

    protected $hidden = [
        'password', 'remember_token'
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'is_completed' => 'boolean'
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
