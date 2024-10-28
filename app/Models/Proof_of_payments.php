<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proof_of_payments extends Model
{
    protected $table = 'proof_of_payments';
    protected $primaryKey = 'Proof_ID';
    public $incrementing = true; 
    protected $keyType = 'int'; 
    protected $fillable = [
        'Proof_ID',
        'Payment_ID',
        'Proof_filename',
        'Upload_datetime'
    ];
}
