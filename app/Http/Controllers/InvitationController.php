<?php

namespace App\Http\Controllers;

use App\Mail\UserInvitation;
use App\Models\Invitation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class InvitationController extends Controller
{
    public function index()
    {
        $invitations = Invitation::with('inviter')
            ->latest()
            ->get();

        return Inertia::render('Admin/Invitations', [
            'invitations' => $invitations,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email|unique:invitations,email',
        ]);

        $invitation = Invitation::create([
            'email' => $request->email,
            'token' => Invitation::generateToken(),
            'invited_by' => auth()->id(),
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        Mail::to($invitation->email)->send(new UserInvitation($invitation));

        return redirect()->back()->with('success', 'Invitation sent successfully!');
    }

    public function accept($token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired()) {
            return Inertia::render('Auth/InvitationExpired');
        }

        if ($invitation->isAccepted()) {
            return redirect()->route('login')->with('info', 'This invitation has already been accepted.');
        }

        return Inertia::render('Auth/AcceptInvitation', [
            'invitation' => $invitation,
        ]);
    }

    public function complete(Request $request, $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired() || $invitation->isAccepted()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $invitation->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $invitation->update(['accepted_at' => now()]);

        auth()->login($user);

        return redirect()->route('dashboard')->with('success', 'Welcome! Your account has been created.');
    }
}
