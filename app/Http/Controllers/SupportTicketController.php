<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('Support/Index', [
            'tickets' => $team
                ? $team->supportTickets()->with('replies.user:id,name')->where('user_id', $request->user()->id)->latest()->get()
                : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = $request->user()->currentTeam->supportTickets()->create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'status' => 'open',
        ]);

        $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'is_admin_reply' => false,
            'message' => $validated['message'],
        ]);

        return back()->with('success', 'Support ticket submitted.');
    }

    public function reply(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        if ($supportTicket->team_id !== $request->user()->current_team_id) {
            abort(404);
        }

        $validated = $request->validate(['message' => ['required', 'string', 'max:5000']]);

        $supportTicket->replies()->create([
            'user_id' => $request->user()->id,
            'is_admin_reply' => false,
            'message' => $validated['message'],
        ]);

        $supportTicket->update(['status' => 'open']);

        return back()->with('success', 'Reply sent.');
    }
}
