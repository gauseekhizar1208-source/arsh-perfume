<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data) {
            if ($data['is_default']) {
                $request->user()->addresses()->update(['is_default' => false]);
            }
            $request->user()->addresses()->create($data);
        });

        return back()->with('status', 'Address saved.');
    }

    public function update(Request $request, UserAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $address, $data) {
            if ($data['is_default']) {
                $request->user()->addresses()->update(['is_default' => false]);
            }
            $address->update($data);
        });

        return back()->with('status', 'Address updated.');
    }

    public function destroy(Request $request, UserAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $address->delete();

        return back()->with('status', 'Address removed.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', Rule::in(config('arsh.states'))],
            'postal_code' => ['required', 'digits:6'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
        $data['is_default'] = (bool) ($data['is_default'] ?? false);

        return $data;
    }
}
