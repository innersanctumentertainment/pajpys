<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleSwitchRequest;
use Illuminate\Http\RedirectResponse;

class RoleSwitchController extends Controller
{
    public function store(RoleSwitchRequest $request): RedirectResponse
    {
        $role = $request->string('role')->toString();
        $user = $request->user();

        $user->forceFill(['active_role' => $role])->save();
        $request->session()->put('active_role', $role);

        return back()->with('status', 'Switched to '.ucfirst($role).' mode.');
    }
}
