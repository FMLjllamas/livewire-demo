<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CountryOfBirth extends Model
{

    use HasFactory;

    protected $table = 'country_of_birth';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];
}
