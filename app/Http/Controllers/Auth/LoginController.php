<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

final class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'password' => ['required','string','max:255'],
        ]);

        // Karena kamu 1 user doang, kita login pakai kolom "name" bawaan users.
        $user = User::query()->where('name', $data['name'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return back()
                ->withInput(['name' => $data['name']])
                ->withErrors(['name' => 'Nama atau password salah.']);
        }

        // "Jangka panjang": pakai remember cookie.
        Auth::login($user, true);

        $request->session()->regenerate();

        return redirect()->intended(route('app.shell'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
