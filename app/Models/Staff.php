<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{

    use HasFactory;
    protected $fillable = [
        'first_name',
        'last_name',
        'father_name',
        'gender',
        'email',
        'phone',
        'tazkira',
        'degree',
        'field_of_education',
        'name_of_university',
        'work_experience',
        'name_of_organization',
        'position',
        'duration',
        'company_id' => 2,
        'ref1',
        'ref2',
        'ref3',
    ];
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

}
