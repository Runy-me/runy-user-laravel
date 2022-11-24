<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomersOccupation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $primaryKey = 'customerId'; // or null

    public $incrementing = false;

    protected $table = 'customer_occupations';

    public function occupation(){
        return $this->hasOne( Occupation::class, 'id');
    }
}
