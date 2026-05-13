<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agency extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'agencies';

    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function priceLists()
    {
        return $this->hasMany(PriceList::class, 'agency_id');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'agency_id');
    }
}
