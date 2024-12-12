<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class shipping_services extends Model
{
    protected $table = 'shipping_service_price';
    protected $primaryKey = 'ShipServ_ID';
    public $incrementing = true; 
    protected $keyType = 'int'; 
    protected $fillable = [
        'ShipServ_ID',
        'City_Address',
        'ShipServ_price'
    ];
}
