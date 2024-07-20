<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
        'shipping_address',
        'created_at',
        'updated_at'
    ];
    
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
