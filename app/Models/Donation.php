<?php

   
  namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $table='donations';
    protected $fillable = [
    'amount',
    'payment_method',
    'payment_reference',
    'payment_account',
    'payment_status',
    'platform_fee',
    'charity_amount',
    'donor_id',
    'charity_id',
];
    
    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function charity()
    {
        return $this->belongsTo(User::class, 'charity_id');
    }
}


