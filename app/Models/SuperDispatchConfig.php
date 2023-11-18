<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuperDispatchConfig extends Model
{
    protected $table = 'superdispach_conf';

    protected $fillable = [
        'client_secret',
        'client_id',
        'api_name',
    ];
    public $timestamps = false;
}
