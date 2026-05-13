<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SysLookupValue extends Model
{
    use HasFactory;

    protected $table = 'sys_lookup_values';

    protected $fillable = [
        'type_id',
        'code',
        'display_name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(SysLookupType::class, 'type_id');
    }

    public function priceLists(): HasMany
    {
        return $this->hasMany(PriceList::class, 'price_type_id');
    }
}
