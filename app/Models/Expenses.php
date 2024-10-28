<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expenses extends Model
{
    protected $table = 'expenses';
    protected $primaryKey = 'Expense_ID';
    public $incrementing = true; 
    protected $keyType = 'int'; 
    protected $fillable = [
        'Expense_ID',
        'Admin_ID',
        'Amount',
        'Desc_reason',
        'Receipt_filenameing',
        'Datetime_taken'
    ];
}
