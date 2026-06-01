<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [

        'name',

        'email',

        'password',

        'role',

        'is_banned',

        'phone',

        
        'location',
        'latitude',
        'longitude',

        'cuisine',

        'is_approved',

        'card_last4',

        'vodafone_number',

        'instapay_address',

    ];

    protected $hidden = [

        'password',

        'remember_token',

    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [

            'email_verified_at' => 'datetime',

            'password' => 'hashed',

        ];
    }
}