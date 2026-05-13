<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SysLookupType extends Model
{
    use HasFactory;

    protected $table = 'sys_lookup_types';

    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    public function values()
    {
        return $this->hasMany(SysLookupValue::class, 'type_id');
    }
}
