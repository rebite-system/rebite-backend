<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Food;

class Claim extends Model
{ 
  protected $table='claim';
    protected $fillable = [
        'charity_id',
        'food_id',
        'quantity',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'charity_id');
    }
    public function charity()
{
    return $this->belongsTo(User::class, 'charity_id');
}

   
    public function food()
    {
        return $this->belongsTo(Food::class, 'food_id');
    }
}
