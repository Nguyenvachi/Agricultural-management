<?php

namespace App\Http\Controllers;

use App\Http\Requests\Agency\StoreAgencyRequest;
use App\Http\Requests\Agency\UpdateAgencyRequest;
use App\Models\Agency;

class AgencyController extends Controller
{
    public function index()
    {
        $agencies = Agency::query()
            ->orderByDesc('id')
            ->paginate(15);

        return view('agency.index', compact('agencies'));
    }

    public function create()
    {
        return view('agency.create');
    }

    public function store(StoreAgencyRequest $request)
    {
        $agency = Agency::query()->create($request->validated());

        return redirect()
            ->route('agencies.show', $agency)
            ->with('success', 'Tạo đại lý thành công.');
    }

    public function show(Agency $agency)
    {
        return view('agency.show', compact('agency'));
    }

    public function edit(Agency $agency)
    {
        return view('agency.edit', compact('agency'));
    }

    public function update(UpdateAgencyRequest $request, Agency $agency)
    {
        $agency->update($request->validated());

        return redirect()
            ->route('agencies.show', $agency)
            ->with('success', 'Cập nhật đại lý thành công.');
    }

    public function destroy(Agency $agency)
    {
        $agency->delete();

        return redirect()
            ->route('agencies.index')
            ->with('success', 'Xóa đại lý thành công.');
    }
}
