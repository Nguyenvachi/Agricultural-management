<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    use HasFactory;

    protected $table = 'price_lists';

    protected $fillable = [
        'agency_id',
        'item_id',
        'price_type_id',
        'price',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function priceType()
    {
        return $this->belongsTo(SysLookupValue::class, 'price_type_id');
    }
}
