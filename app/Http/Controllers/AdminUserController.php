<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->latest();
        $query->when($request->filled('role'), fn ($q) => $q->where('role', $request->input('role')))
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->string('search').'%')
                    ->orWhere('email', 'like', '%'.$request->string('search').'%');
            }));

        return $query->paginate(30);
    }

    public function show(User $user)
    {
        return $user->loadCount(['reservations', 'venues', 'supportTickets', 'roleUpgradeRequests']);
    }

    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate(['role' => 'required|in:user,venue_admin,instructor,super_admin']);
        abort_if($request->user()->is($user) && $data['role'] !== 'super_admin', 409, 'You cannot remove your own super admin role.');
        $user->update($data);

        return response()->json($user);
    }
}
