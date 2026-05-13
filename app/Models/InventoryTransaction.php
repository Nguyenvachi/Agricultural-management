<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $table = 'inventory_transactions';

    protected $fillable = [
        'agency_id',
        'item_id',
        'order_id',
        'transaction_type_id',
        'quantity_change',
        'balance_before',
        'balance_after',
        'reference_type',
        'note',
        'created_by',
    ];

    protected $casts = [
        'quantity_change' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function transactionType()
    {
        return $this->belongsTo(SysLookupValue::class, 'transaction_type_id');
    }
}
