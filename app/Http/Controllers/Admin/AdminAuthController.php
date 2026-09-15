<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'telephone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // On ajoute is_admin => true directement dans les critères de
        // connexion : Auth::attempt refuse donc silencieusement toute
        // tentative venant d'un compte non-administrateur, même avec le
        // bon mot de passe.
        if (! Auth::attempt(['telephone' => $data['telephone'], 'password' => $data['password'], 'is_admin' => true])) {
            return back()->withErrors(['telephone' => 'Identifiants incorrects ou accès non autorisé.']);
        }

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}