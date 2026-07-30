<?php

namespace App\Http\Controllers;

use App\Models\RoleUpgradeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleUpgradeRequestController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'requested_role' => 'required|in:venue_admin,instructor',
            'business_name' => 'nullable|required_if:requested_role,venue_admin|string|max:255',
            'phone' => 'required|string|max:30',
            'reason' => 'nullable|string|max:2000',
        ]);

        if ($request->user()->role !== 'user') {
            return response()->json(['message' => 'Your account already has an elevated role.'], 409);
        }

        if (RoleUpgradeRequest::where('user_id', $request->user()->id)->where('status', 'pending')->exists()) {
            return response()->json(['message' => 'You already have a pending upgrade request.'], 409);
        }

        $upgradeRequest = RoleUpgradeRequest::create([
            ...$data,
            'user_id' => $request->user()->id,
            'status' => 'pending',
            'pending_marker' => true,
        ]);

        return response()->json($upgradeRequest, 201);
    }

    public function current(Request $request)
    {
        return RoleUpgradeRequest::where('user_id', $request->user()->id)->latest()->paginate(15);
    }

    public function index(Request $request)
    {
        $query = RoleUpgradeRequest::with(['user:id,name,email', 'reviewer:id,name'])->latest();
        if ($request->filled('status')) {
            $request->validate(['status' => 'in:pending,approved,rejected']);
            $query->where('status', $request->string('status'));
        }

        return $query->paginate(20);
    }

    public function review(Request $request, RoleUpgradeRequest $upgradeRequest)
    {
        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_note' => 'nullable|string|max:2000',
        ]);

        $upgradeRequest = DB::transaction(function () use ($upgradeRequest, $data, $request) {
            $upgradeRequest = RoleUpgradeRequest::lockForUpdate()->findOrFail($upgradeRequest->id);
            if ($upgradeRequest->status !== 'pending') {
                abort(409, 'This request has already been reviewed.');
            }

            $upgradeRequest->update([
                ...$data,
                'pending_marker' => null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            if ($data['status'] === 'approved') {
                $upgradeRequest->user()->update(['role' => $upgradeRequest->requested_role]);
            }

            return $upgradeRequest;
        });

        return response()->json($upgradeRequest->load(['user:id,name,email,role', 'reviewer:id,name']));
    }
}
