@extends('layouts.admin')

@section('title', 'Enquiry from '.$enquiry->name)
@section('heading', 'Enquiry from '.$enquiry->name)

@section('content')
<div class="grid gap-6 xl:grid-cols-3">
    <section class="card space-y-4 p-6 text-sm xl:col-span-2">
        <p><span class="label">From</span> {{ $enquiry->name }} &middot; <a class="text-gold hover:underline" href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a> @if($enquiry->phone)&middot; {{ $enquiry->phone }}@endif</p>
        <p><span class="label">Subject</span> {{ $enquiry->subject ?: '—' }}</p>
        <p class="whitespace-pre-line leading-relaxed">{{ $enquiry->message }}</p>
        <p class="text-xs text-muted">Received {{ $enquiry->created_at->format('d M Y H:i') }} from {{ $enquiry->ip_address }}</p>
    </section>
    <form method="post" action="{{ route('admin.enquiries.update', $enquiry) }}" class="card space-y-4 p-6">
        @csrf @method('PATCH')
        <div><label class="label" for="st">Status</label>
            <select id="st" name="status" class="input">@foreach($statuses as $k => $l)<option value="{{ $k }}" @selected($enquiry->status === $k)>{{ $l }}</option>@endforeach</select></div>
        <div><label class="label" for="an">Internal notes</label><textarea id="an" name="admin_notes" rows="6" class="input">{{ $enquiry->admin_notes }}</textarea></div>
        <button class="btn-gold w-full">Save</button>
    </form>
</div>
@endsection
