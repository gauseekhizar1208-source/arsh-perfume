<?php

namespace App\Http\Controllers\Admin;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View
    {
        return view('admin.banners.index', ['banners' => Banner::query()->orderBy('position')->orderBy('sort_order')->get()]);
    }

    public function create(): View
    {
        return view('admin.banners.form', ['banner' => new Banner(['position' => 'hero', 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $banner = Banner::query()->create($this->validated($request));
        ActivityLogger::log('banner.created', $banner, "Created banner {$banner->title}");

        return redirect()->route('admin.banners.index')->with('status', 'Banner created.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.form', ['banner' => $banner]);
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $banner->update($this->validated($request));
        ActivityLogger::log('banner.updated', $banner, "Updated banner {$banner->title}");

        return redirect()->route('admin.banners.index')->with('status', 'Banner saved.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        if ($banner->image_path) {
            Storage::disk(config('arsh.media_disk'))->delete($banner->image_path);
        }
        ActivityLogger::log('banner.deleted', null, "Deleted banner {$banner->title}");
        $banner->delete();

        return back()->with('status', 'Banner deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:250'],
            'button_text' => ['nullable', 'string', 'max:60'],
            'link_url' => ['nullable', 'string', 'max:255', 'regex:#^(/|https://)#'], // internal path or https only
            'position' => ['required', Rule::in(array_keys(Banner::POSITIONS))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:min_width=800'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->storeAs('banners', Str::random(32).'.'.$request->file('image')->guessExtension(), config('arsh.media_disk'));
        }
        unset($data['image']);

        return $data;
    }
}
