<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cashdetails extends Model
{
    protected $table = 'cash';
    protected $primaryKey = 'Cash_ID';
    public $incrementing = true; 
    protected $keyType = 'int'; 
    protected $fillable = [
        'Cash_ID',
        'Admin_ID',
        'Staff_iD',
        'Initial_amount',
        'Remittance',
        'Datetime_InitialAmo',
        'Datetime_Remittance',
        'Received_datetime'
    ];
}
