<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customers extends Model
{
    use HasFactory, HasUuids;
    
    public $timestamps = false;

    protected $table = 'customers';
    
    protected $fillable = ['name', 'email'];


    public $incrementing = false;

    protected $keyType = 'string';

    public function user(){
        return $this->hasOne( User::class, 'domainId');
    }

    public function address(){
        return $this->hasOne( User::class, 'domainId');
    }

    public function custommerOccupation(){
        return $this->belongsTo( CustomersOccupation::class, 'occupationId');
    }

}
