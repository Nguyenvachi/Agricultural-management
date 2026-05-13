<?php

namespace App\Http\Controllers;

use App\Http\Requests\Item\StoreItemRequest;
use App\Http\Requests\Item\UpdateItemRequest;
use App\Models\Category;
use App\Models\Item;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::query()
            ->with('category')
            ->orderByDesc('id')
            ->paginate(15);

        return view('item.index', compact('items'));
    }

    public function create()
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get();

        return view('item.create', compact('categories'));
    }

    public function store(StoreItemRequest $request)
    {
        $item = Item::query()->create($request->validated());

        return redirect()
            ->route('items.show', $item)
            ->with('success', 'Tạo mặt hàng thành công.');
    }

    public function show(Item $item)
    {
        $item->load('category');

        return view('item.show', compact('item'));
    }

    public function edit(Item $item)
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get();

        return view('item.edit', compact('item', 'categories'));
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        $item->update($request->validated());

        return redirect()
            ->route('items.show', $item)
            ->with('success', 'Cập nhật mặt hàng thành công.');
    }

    public function destroy(Item $item)
    {
        $item->delete();

        return redirect()
            ->route('items.index')
            ->with('success', 'Xóa mặt hàng thành công.');
    }
}
