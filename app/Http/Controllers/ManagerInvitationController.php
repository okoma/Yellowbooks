<?php
// ============================================
// app/Http/Controllers/ManagerInvitationController.php
// Handle public invitation acceptance
// ============================================

namespace App\Http\Controllers;

use App\Models\ManagerInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ManagerInvitationController extends Controller
{
    /**
     * Show the invitation acceptance page
     */
    public function show($token)
    {
        $invitation = ManagerInvitation::where('invitation_token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if (!$invitation) {
            return view('manager.invitation.invalid');
        }

        // Check if user exists
        $userExists = User::where('email', $invitation->email)->exists();

        return view('manager.invitation.accept', [
            'invitation' => $invitation,
            'userExists' => $userExists,
        ]);
    }

    /**
     * Accept the invitation
     */
    public function accept(Request $request, $token)
    {
        $invitation = ManagerInvitation::where('invitation_token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $user = User::where('email', $invitation->email)->first();

        // If user doesn't exist, create account
        if (!$user) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', Password::default()],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'password' => Hash::make($validated['password']),
                'role' => 'branch_manager',
            ]);
        } else {
            // If user exists, verify password
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);
        }

        // Accept the invitation
        $manager = $invitation->accept($user->id);

        // Log the user in
        Auth::login($user);

        // Redirect to dashboard
        return redirect()
            ->route('filament.business.pages.dashboard')
            ->with('success', 'Welcome! You are now a manager for ' . $invitation->branch->branch_title);
    }

    /**
     * Decline the invitation
     */
    public function decline(Request $request, $token)
    {
        $invitation = ManagerInvitation::where('invitation_token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $invitation->decline();

        return view('manager.invitation.declined', [
            'invitation' => $invitation,
        ]);
    }
}