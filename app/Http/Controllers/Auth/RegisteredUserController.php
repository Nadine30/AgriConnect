<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:acheteur,agriculteur,cooperative'],
        ]);

        $role = $request->role;

        // Coopérative = validation nécessaire
        $status = $role === User::ROLE_COOPERATIVE
        ? User::STATUS_PENDING
        : User::STATUS_APPROVED;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'status' => $status,
        ]);

        event(new Registered($user));

        Auth::login($user);
        
        // Redirection spéciale si coopérative en attente
        if ($user->role === User::ROLE_COOPERATIVE && $user->status === User::STATUS_PENDING) {
            return redirect()->route('cooperative.pending');
        }

        return redirect(route('dashboard', absolute: false));
    }
}
