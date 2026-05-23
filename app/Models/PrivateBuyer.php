<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivateBuyer extends Model
{
    protected $fillable = ['name', 'phone', 'notes'];
}
