<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionDetails extends Model
{
    protected $table = 'transaction_details';
    protected $primaryKey = 'TransacDet_ID';
    public $incrementing = true; 
    protected $keyType = 'int'; 
    protected $fillable = [
        'TransacDet_ID',
        'Categ_ID',
        'Transac_ID',
        'Qty',
        'Weight',
        'Price'
    ];
}
