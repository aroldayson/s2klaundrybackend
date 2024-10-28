<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Laundrycategories extends Model
{
    protected $table = 'laundry_categories';
    protected $primaryKey = 'Categ_ID';
    public $incrementing = true; 
    protected $keyType = 'int'; 
    protected $fillable = [
        'Categ_ID',
        'Category',
        'Price'
    ];
}
