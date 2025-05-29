<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;

class GoogleAuthController extends Controller
{
    public function __construct(
        protected GoogleAuthService $googleAuthService
    ) {}

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $user = $this->googleAuthService->findOrCreateUser($googleUser);

            Auth::login($user, true);

            // Usa la ruta correcta basada en tu configuración del panel
            return redirect()->intended('/dashboard');
        } catch (\Exception $e) {
            // Redirige a la ruta de login correcta
            return redirect('/dashboard/login')->withErrors([
                'email' => 'Error al autenticar con Google. Por favor intenta nuevamente.',
            ]);
        }
    }
}
