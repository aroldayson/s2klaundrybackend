<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'Payment_ID';
    public $incrementing = true; 
    protected $keyType = 'int'; 
    protected $fillable = [
        'Payment_ID',
        'Admin_ID',
        'Transac_ID',
        'Amount',
        'Mode_of_Payment',
        'Datetime_of_Payment'
    ];
}
