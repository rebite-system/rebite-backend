<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Food extends Model
{
    protected $table = 'food';

    protected $fillable = [
        'title',
        'category',
        'quantity',
        'expiry',
        'pickup_from',
        'pickup_until',
        'notes',
        'status',
        'restaurant_id',
        'image',
    ];

    public function restaurant()
    {
        return $this->belongsTo(User::class, 'restaurant_id');
    }
}