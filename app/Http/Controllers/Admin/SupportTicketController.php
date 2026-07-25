<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $tickets = SupportTicket::query()
            ->with(['team:id,name', 'user:id,name,email'])
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/SupportTickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only('status'),
        ]);
    }

    public function show(SupportTicket $supportTicket): Response
    {
        $supportTicket->load(['team:id,name', 'user:id,name,email', 'replies.user:id,name']);

        return Inertia::render('Admin/SupportTickets/Show', [
            'ticket' => $supportTicket,
        ]);
    }

    public function reply(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $validated = $request->validate(['message' => ['required', 'string', 'max:5000']]);

        $supportTicket->replies()->create([
            'user_id' => $request->user()->id,
            'is_admin_reply' => true,
            'message' => $validated['message'],
        ]);

        $supportTicket->update(['status' => 'pending']);

        return back()->with('success', 'Reply sent.');
    }

    public function updateStatus(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', 'in:open,pending,closed']]);

        $supportTicket->update($validated);

        return back()->with('success', 'Ticket status updated.');
    }
}
