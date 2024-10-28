<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Customers extends Model
{

    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'customers';
    protected $primaryKey = 'Cust_ID';
    public $incrementing = true; 
    protected $keyType = 'int'; 
    protected $fillable = [
        'Cust_ID',
        'Cust_lname',
        'Cust_fname',
        'Cust_mname',
        'Cust_phoneno',
        'Cust_address',
        'Cust_image',
        'Cust_email',
        'Cust_password'
    ];
}
