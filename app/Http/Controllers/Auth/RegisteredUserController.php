<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
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
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:'.User::class,
                function ($attribute, $value, $fail) {
                    if (app()->environment('testing')) {
                        return;
                    }

                    $allowed = collect(explode(',', SettingsService::get('allowed_email_domains', '')))
                        ->map(fn ($domain) => strtolower(trim($domain)))
                        ->filter()
                        ->map(fn ($domain) => str_starts_with($domain, '@') ? $domain : '@' . $domain)
                        ->values();

                    if ($allowed->isEmpty()) {
                        return;
                    }

                    $isAllowed = $allowed->contains(fn ($domain) => str_ends_with(strtolower($value), $domain));

                    if (! $isAllowed) {
                        $fail('Only approved organization email addresses are allowed.');
                    }
                },
            ],
            'staff_id' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'designation' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'employee_level' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admission',
            'staff_id' => $request->staff_id,
            'phone' => $request->phone,
            'designation' => $request->designation,
            'department' => $request->department,
            'employee_level' => $request->employee_level,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
