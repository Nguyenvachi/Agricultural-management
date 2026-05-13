<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryTransaction;

class InventoryController extends Controller
{
    public function index()
    {
        $inventories = Inventory::query()
            ->with(['agency', 'item'])
            ->orderBy('agency_id')
            ->orderBy('item_id')
            ->paginate(20);

        return view('inventory.index', compact('inventories'));
    }

    public function transactions()
    {
        $transactions = InventoryTransaction::query()
            ->with(['agency', 'item', 'transactionType'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('inventory.transactions', compact('transactions'));
    }
}
