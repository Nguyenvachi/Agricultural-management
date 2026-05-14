<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isEffectiveOn(?CarbonInterface $date = null): bool
    {
        $date = $date ? Carbon::instance($date) : now();

        if (! $this->is_active) {
            return false;
        }

        if ($this->effective_from && $this->effective_from->gt($date)) {
            return false;
        }

        if ($this->effective_to && $this->effective_to->lt($date)) {
            return false;
        }

        return true;
    }

    public function effectiveStatus(?CarbonInterface $date = null): string
    {
        $date = $date ? Carbon::instance($date) : now();

        if (! $this->is_active) {
            return 'INACTIVE';
        }

        if ($this->effective_from && $this->effective_from->gt($date)) {
            return 'UPCOMING';
        }

        if ($this->effective_to && $this->effective_to->lt($date)) {
            return 'EXPIRED';
        }

        return 'CURRENT';
    }
}
