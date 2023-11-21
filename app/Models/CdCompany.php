<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CdCompany extends Model
{
    protected $table = 'cd_company_tb';
    protected $fillable = [
        'company_name', 'company_email', 'type', 'company_information',
        'contact_information', 'mc', 'broker_bond_information',
        'additional_info', 'company_hash', 'companyId', 'isActive',
        'rating', 'btx_id'
    ];

    public $timestamps = false;
}
