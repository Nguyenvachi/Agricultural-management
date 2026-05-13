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
        $priceLists = PriceList::query()
            ->with(['agency', 'item', 'priceType'])
            ->orderByDesc('id')
            ->paginate(15);

        return view('price-list.index', compact('priceLists'));
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
            ->with('success', 'Tạo bảng giá thành công.');
    }

    public function show(PriceList $priceList)
    {
        $priceList->load(['agency', 'item', 'priceType']);

        return view('price-list.show', compact('priceList'));
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
            ->with('success', 'Cập nhật bảng giá thành công.');
    }

    public function destroy(PriceList $priceList)
    {
        // DB-first: bảng price_lists không có deleted_at, nên 'xóa' sẽ hiểu là ngừng hiệu lực
        $priceList->update([
            'is_active' => 0,
        ]);

        return redirect()
            ->route('price-lists.index')
            ->with('success', 'Ngừng hiệu lực bảng giá thành công.');

        $priceList->delete();

        return redirect()
            ->route('price-lists.index')
            ->with('success', 'Xóa bảng giá thành công.');
    }
}
