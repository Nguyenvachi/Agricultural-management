<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryTransaction;

class InventoryController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = Inventory::query()->with(['agency', 'item']);

        if ($user->isAgency()) {
            // AGENCY chỉ xem tồn kho của agency mình
            $query->where('agency_id', $user->agency_id);
        }
        // ADMIN: xem tất cả (không filter)

        $inventories = $query
            ->orderBy('agency_id')
            ->orderBy('item_id')
            ->paginate(20);

        return view('inventory.index', compact('inventories'));
    }

    public function transactions()
    {
        $user  = auth()->user();
        $query = InventoryTransaction::query()->with(['agency', 'item', 'transactionType']);

        if ($user->isAgency()) {
            $query->where('agency_id', $user->agency_id);
        }

        $transactions = $query->orderByDesc('id')->paginate(20);

        return view('inventory.transactions', compact('transactions'));
    }
}
