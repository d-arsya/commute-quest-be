<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Halte extends Model
{
    protected $visible = ["name", "latitude", "longitude", "link", "buses"];
    public function getBusesAttribute()
    {
        return Bus::where('routes', 'LIKE', "%{$this->name}%")->pluck('name');
    }
}
