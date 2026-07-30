<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\EarningsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WithdrawalRequestController extends Controller
{
    public function balance(Request $request, EarningsService $earnings)
    {
        return response()->json($earnings->balance($request->user()));
    }

    public function index(Request $request)
    {
        return WithdrawalRequest::where('user_id', $request->user()->id)->latest()->paginate(20);
    }

    public function store(Request $request, EarningsService $earnings)
    {
        $request->merge(['iban' => strtoupper(str_replace(' ', '', (string) $request->input('iban')))]);
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'iban' => ['required', 'regex:/^IR[0-9]{24}$/'],
            'account_holder' => 'required|string|max:255',
        ]);

        $withdrawal = DB::transaction(function () use ($request, $data, $earnings) {
            $user = User::lockForUpdate()->findOrFail($request->user()->id);
            $balance = $earnings->balance($user);
            abort_if((float) $data['amount'] > $balance['available_to_withdraw'], 422, 'Requested amount exceeds available balance.');

            return WithdrawalRequest::create([
                ...$data,
                'number' => 'WDR-'.strtoupper(Str::random(12)),
                'user_id' => $user->id,
                'status' => 'pending',
            ]);
        });

        return response()->json($withdrawal, 201);
    }

    public function adminIndex(Request $request)
    {
        $request->validate(['status' => 'nullable|in:pending,approved,rejected,paid']);
        $query = WithdrawalRequest::with('user:id,name,email,role')->latest();
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')));

        return $query->paginate(30);
    }

    public function show(WithdrawalRequest $withdrawalRequest, EarningsService $earnings)
    {
        return response()->json([
            'withdrawal' => $withdrawalRequest->load(['user:id,name,email,role', 'processor:id,name']),
            'current_balance' => $earnings->balance($withdrawalRequest->user),
        ]);
    }

    public function updateStatus(Request $request, WithdrawalRequest $withdrawalRequest)
    {
        $data = $request->validate([
            'status' => 'required|in:approved,rejected,paid',
            'admin_note' => 'nullable|string|max:2000',
        ]);

        $withdrawalRequest = DB::transaction(function () use ($request, $withdrawalRequest, $data) {
            $withdrawal = WithdrawalRequest::lockForUpdate()->findOrFail($withdrawalRequest->id);
            $allowed = match ($withdrawal->status) {
                'pending' => ['approved', 'rejected'],
                'approved' => ['paid', 'rejected'],
                default => [],
            };
            abort_unless(in_array($data['status'], $allowed, true), 409, 'This status transition is not allowed.');

            $withdrawal->update([
                'status' => $data['status'],
                'admin_note' => $data['admin_note'] ?? $withdrawal->admin_note,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
                'paid_at' => $data['status'] === 'paid' ? now() : null,
            ]);

            return $withdrawal;
        });

        return response()->json($withdrawalRequest->load('processor:id,name'));
    }
}
