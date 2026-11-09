<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactEnquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', 'in:new,in_progress,resolved']])['status'] ?? null;

        return view('admin.enquiries.index', [
            'enquiries' => ContactEnquiry::query()->when($status, fn ($q, $s) => $q->where('status', $s))->latest('id')->paginate(20)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(ContactEnquiry $enquiry): View
    {
        return view('admin.enquiries.show', ['enquiry' => $enquiry, 'statuses' => ContactEnquiry::STATUSES]);
    }

    public function update(Request $request, ContactEnquiry $enquiry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(ContactEnquiry::STATUSES))],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $enquiry->update([...$data, 'handled_by' => $request->user()->id]);

        return back()->with('status', 'Enquiry updated.');
    }
}
