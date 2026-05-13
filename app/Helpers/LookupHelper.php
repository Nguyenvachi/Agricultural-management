<?php

namespace App\Helpers;

use App\Models\SysLookupType;
use App\Models\SysLookupValue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class LookupHelper
{
    public static function getTypeId(string $typeCode): int
    {
        $cacheKey = 'lookup:type_id:' . $typeCode;

        return Cache::rememberForever($cacheKey, function () use ($typeCode) {
            return (int) SysLookupType::query()
                ->where('code', $typeCode)
                ->value('id');
        });
    }

    public static function getValueId(string $typeCode, string $valueCode): int
    {
        $cacheKey = 'lookup:value_id:' . $typeCode . ':' . $valueCode;

        return Cache::rememberForever($cacheKey, function () use ($typeCode, $valueCode) {
            $typeId = self::getTypeId($typeCode);

            return (int) SysLookupValue::query()
                ->where('type_id', $typeId)
                ->where('code', $valueCode)
                ->value('id');
        });
    }

    /** @return Collection<int, SysLookupValue> */
    public static function getValuesByTypeCode(string $typeCode): Collection
    {
        $cacheKey = 'lookup:values:' . $typeCode;

        return Cache::rememberForever($cacheKey, function () use ($typeCode) {
            $typeId = self::getTypeId($typeCode);

            return SysLookupValue::query()
                ->where('type_id', $typeId)
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->orderBy('display_name')
                ->get();
        });
    }
}
