<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddLaundry_services extends Model
{
    protected $table = 'addlaundry_services';
    protected $primaryKey = 'Addlaundryserv_ID';
    public $incrementing = true; 
    protected $keyType = 'int'; 
    protected $fillable = [
        'Addlaundryserv_ID',
        'TransacDet_ID',
        'AddLaundryServ_name',
        'AddLaundryServ_price'
    ];
}
