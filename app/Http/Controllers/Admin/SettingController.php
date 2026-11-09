<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Business settings. Only keys declared in config/arsh.php are writable; secrets are never editable here
 * (they live in .env).
 */
class SettingController extends Controller
{
    public function edit(): View
{
    $groups = collect(config('arsh.settings'))
        ->groupBy(fn ($def) => $def[0], preserveKeys: true)
        ->map(fn ($items, $group) => $items->keys()->mapWithKeys(fn ($key) => [$key => Setting::get($key)]));

    return view('admin.settings.edit', [
        'groups' => $groups,
        'definitions' => config('arsh.settings'),
        'razorpayMode' => app(\App\Services\RazorpayService::class)->mode(),
        'states' => config('arsh.states'),
    ]);
}
    public function update(Request $request): RedirectResponse
    {
        $definitions = config('arsh.settings');
        $rules = [];
        foreach ($definitions as $key => [$group, $type]) {
            $rules[$key] = match ($type) {
                'bool' => ['sometimes', 'boolean'],
                'int' => ['nullable', 'integer', 'min:0', 'max:100000'],
                'decimal' => ['nullable', 'numeric', 'min:0', 'max:100000'],
                'text' => ['nullable', 'string', 'max:3000'],
                default => ['nullable', 'string', 'max:255'],
            };
        }
        $rules['contact_email'] = ['nullable', 'email', 'max:255'];
        $rules['admin_notification_email'] = ['nullable', 'email', 'max:255'];
        $rules['tax_rate'] = ['nullable', 'numeric', 'min:0', 'max:100'];
        $rules['social_instagram'] = ['nullable', 'url:https', 'max:255'];
        $rules['social_facebook'] = ['nullable', 'url:https', 'max:255'];
        $rules['social_youtube'] = ['nullable', 'url:https', 'max:255'];
        $rules['upi_id'] = ['nullable', Rule::requiredIf($request->boolean('upi_enabled')), 'string', 'max:193', 'regex:/^[A-Za-z0-9._-]{2,128}@[A-Za-z0-9.-]{2,64}$/'];
        $rules['upi_payee_name'] = ['nullable', Rule::requiredIf($request->boolean('upi_enabled')), 'string', 'max:120'];

        $data = $request->validate($rules);

        foreach ($definitions as $key => [$group, $type]) {
            if ($type === 'bool') {
                Setting::set($key, $request->boolean($key));
            } elseif (array_key_exists($key, $data)) {
                Setting::set($key, $data[$key] ?? '');
            }
        }

        ActivityLogger::log('settings.updated', null, 'Business settings updated');

        return back()->with('status', 'Settings saved.');
    }
}
