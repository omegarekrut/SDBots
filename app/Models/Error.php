<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Error extends Model
{
    protected $table = 'errors';
    protected $fillable = [
        'order_id',
        'err_loadid',
        'err_client',
        'err_amount',
        'err_attach',
        'err_pickaddress',
        'err_deladdress',
        'err_email',
        'err_pickbol',
        'err_pdelbol',
        'err_method',
        'err_count',
        'error_message'
    ];

    public $incrementing = false;

    protected $primaryKey = 'order_id';

    protected $casts = [
        'err_loadid' => 'integer',
        'err_client' => 'integer',
        'err_amount' => 'integer',
        'err_attach' => 'integer',
        'err_pickaddress' => 'integer',
        'err_deladdress' => 'integer',
        'err_email' => 'integer',
        'err_pickbol' => 'integer',
        'err_pdelbol' => 'integer',
        'err_method' => 'integer',
        'err_count' => 'integer',
        'error_message' => 'string'
    ];

    public function hasErrors()
    {
    }
}
