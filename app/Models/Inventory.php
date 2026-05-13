<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventories';

    protected $fillable = [
        'agency_id',
        'item_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
