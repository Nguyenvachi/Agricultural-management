<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'items';

    protected $fillable = [
        'category_id',
        'code',
        'name',
        'unit',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function priceLists()
    {
        return $this->hasMany(PriceList::class, 'item_id');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'item_id');
    }
}
