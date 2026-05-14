<?php

namespace App\Http\Controllers;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Http\Requests\PriceList\StorePriceListRequest;
use App\Http\Requests\PriceList\UpdatePriceListRequest;
use App\Models\Agency;
use App\Models\Item;
use App\Models\PriceList;

class PriceListController extends Controller
{
    public function index()
    {
        $today = today();

        $priceLists = PriceList::query()
            ->with(['agency', 'item', 'priceType'])
            ->orderByDesc('is_active')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->paginate(15);

        return view('price-list.index', compact('priceLists', 'today'));
    }

    public function create()
    {
        $agencies = Agency::query()->orderBy('name')->get();
        $items = Item::query()->orderBy('name')->get();
        $priceTypes = LookupHelper::getValuesByTypeCode(LookupCode::TYPE_PRICE_TYPE);

        return view('price-list.create', compact('agencies', 'items', 'priceTypes'));
    }

    public function store(StorePriceListRequest $request)
    {
        $priceList = PriceList::query()->create($request->validated());

        return redirect()
            ->route('price-lists.show', $priceList)
            ->with('success', 'Tao bang gia thanh cong.');
    }

    public function show(PriceList $priceList)
    {
        $today = today();
        $priceList->load(['agency', 'item', 'priceType']);

        return view('price-list.show', compact('priceList', 'today'));
    }

    public function edit(PriceList $priceList)
    {
        $agencies = Agency::query()->orderBy('name')->get();
        $items = Item::query()->orderBy('name')->get();
        $priceTypes = LookupHelper::getValuesByTypeCode(LookupCode::TYPE_PRICE_TYPE);

        return view('price-list.edit', compact('priceList', 'agencies', 'items', 'priceTypes'));
    }

    public function update(UpdatePriceListRequest $request, PriceList $priceList)
    {
        $priceList->update($request->validated());

        return redirect()
            ->route('price-lists.show', $priceList)
            ->with('success', 'Cap nhat bang gia thanh cong.');
    }

    public function destroy(PriceList $priceList)
    {
        $effectiveTo = $priceList->effective_to;
        $today = today();

        if (! $effectiveTo || $effectiveTo->gt($today)) {
            $effectiveTo = $today;
        }

        $priceList->update([
            'is_active' => false,
            'effective_to' => $effectiveTo,
        ]);

        return redirect()
            ->route('price-lists.index')
            ->with('success', 'Ngung hieu luc bang gia thanh cong.');
    }
}
