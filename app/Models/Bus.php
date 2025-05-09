<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    protected $visible = ['name', 'routes'];
    protected $casts = [
        'routes' => 'array'
    ];
}
