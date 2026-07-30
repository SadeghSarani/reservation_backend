<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'category' => 'nullable|in:general,payment,reservation,venue,account',
            'priority' => 'nullable|in:low,normal,high',
        ]);

        $ticket = DB::transaction(function () use ($request, $data) {
            $ticket = SupportTicket::create([
                'number' => 'TKT-'.strtoupper(Str::random(10)),
                'user_id' => $request->user()->id,
                'subject' => $data['subject'],
                'category' => $data['category'] ?? 'general',
                'priority' => $data['priority'] ?? 'normal',
                'status' => 'open',
            ]);
            $ticket->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $data['message'],
                'is_staff' => false,
            ]);

            return $ticket;
        });

        return response()->json($ticket->load('messages.user:id,name'), 201);
    }

    public function index(Request $request)
    {
        return SupportTicket::where('user_id', $request->user()->id)
            ->withCount('messages')->latest()->paginate(20);
    }

    public function adminIndex(Request $request)
    {
        $query = SupportTicket::with('user:id,name,email')->withCount('messages')->latest();
        if ($request->filled('status')) {
            $request->validate(['status' => 'in:open,answered,waiting_for_user,closed']);
            $query->where('status', $request->string('status'));
        }

        return $query->paginate(20);
    }

    public function show(Request $request, SupportTicket $ticket)
    {
        $this->authorizeAccess($request, $ticket);

        return $ticket->load(['user:id,name,email', 'messages.user:id,name']);
    }

    public function message(Request $request, SupportTicket $ticket)
    {
        $this->authorizeAccess($request, $ticket);
        $data = $request->validate(['message' => 'required|string|max:5000']);

        if ($ticket->status === 'closed') {
            return response()->json(['message' => 'This ticket is closed.'], 409);
        }

        $isStaff = $request->user()->isSuperAdmin();
        $message = DB::transaction(function () use ($ticket, $request, $data, $isStaff) {
            $message = $ticket->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $data['message'],
                'is_staff' => $isStaff,
            ]);
            $ticket->update(['status' => $isStaff ? 'answered' : 'waiting_for_user']);

            return $message;
        });

        return response()->json($message->load('user:id,name'), 201);
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate(['status' => 'required|in:open,answered,waiting_for_user,closed']);
        $ticket->update([
            'status' => $data['status'],
            'closed_at' => $data['status'] === 'closed' ? now() : null,
        ]);

        return response()->json($ticket);
    }

    private function authorizeAccess(Request $request, SupportTicket $ticket): void
    {
        abort_unless($request->user()->isSuperAdmin() || $ticket->user_id === $request->user()->id, 403);
    }
}
