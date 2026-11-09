@extends('layouts.admin')

@section('title', 'Banners')
@section('heading', 'Homepage banners')

@section('content')
<div class="flex justify-end"><a href="{{ route('admin.banners.create') }}" class="btn-gold btn-sm">New banner</a></div>
<div class="card mt-6 overflow-x-auto">
    <table class="table-x">
        <thead><tr><th>Title</th><th>Position</th><th>Order</th><th>Schedule</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($banners as $b)
            <tr>
                <td><p class="font-medium">{{ $b->title }}</p><p class="text-xs text-muted">{{ $b->subtitle }}</p></td>
                <td>{{ \App\Models\Banner::POSITIONS[$b->position] ?? $b->position }}</td>
                <td>{{ $b->sort_order }}</td>
                <td class="text-xs">{{ $b->starts_at?->format('d M Y') ?? 'Always' }} → {{ $b->ends_at?->format('d M Y') ?? 'No end' }}</td>
                <td>{!! $b->is_active ? '<span class="badge-ok">Active</span>' : '<span class="badge-muted">Hidden</span>' !!}</td>
                <td class="whitespace-nowrap text-right">
                    <a class="text-gold hover:underline" href="{{ route('admin.banners.edit', $b) }}">Edit</a>
                    <form method="post" action="{{ route('admin.banners.destroy', $b) }}" class="inline" onsubmit="return confirm('Delete this banner?')">@csrf @method('DELETE')<button class="ml-3 text-muted hover:text-danger">Delete</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted">No banners yet. The homepage shows its default hero text.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
