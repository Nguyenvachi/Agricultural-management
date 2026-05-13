<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'order_code',
        'agency_id',
        'to_agency_id',
        'reference_order_id',
        'user_id',
        'order_type_id',
        'status_id',
        'total_amount',
        'note',
        'order_date',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'order_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function toAgency()
    {
        return $this->belongsTo(Agency::class, 'to_agency_id');
    }

    public function orderType()
    {
        return $this->belongsTo(SysLookupValue::class, 'order_type_id');
    }

    public function status()
    {
        return $this->belongsTo(SysLookupValue::class, 'status_id');
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }
}
