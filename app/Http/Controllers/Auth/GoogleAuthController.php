<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class GoogleAuthController extends Controller
{
    public function __construct(
        protected GoogleAuthService $googleAuthService
    ) {
    }

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $user = $this->googleAuthService->findOrCreateUser($googleUser);

            Auth::login($user, true);

            // Registrar la sesión - AÑADE ESTA LÍNEA
            $this->logSession($user, $request);

            return redirect()->intended('/dashboard');
        } catch (\Exception $e) {
            // Añade el registro de error
            \Log::error('Error en autenticación Google: ' . $e->getMessage());
            return redirect('/dashboard/login')->withErrors([
                'email' => 'Error al autenticar con Google. Por favor intenta nuevamente.',
            ]);
        }
    }
    protected function logSession($user, $request)
    {
        try {
            $agent = new Agent();
            $ip = $request->ip();

            // Obtener país (solo si no es localhost)
            $country = ($ip == '127.0.0.1') ? 'Localhost' : $this->getCountryFromIp($ip);

            // Datos a registrar
            $logData = [
                'fecha_hora' => now()->toDateTimeString(),
                'nombre' => $user->name,
                'email' => $user->email,
                'rol' => $user->getRoleNames()->first() ?? 'Sin rol',
                'pais' => $country,
                'dispositivo' => $agent->device() ?: 'Desconocido',
                'plataforma' => $agent->platform() ?: 'Desconocido',
                'navegador' => $agent->browser() ?: 'Desconocido',
                'ip' => $ip
            ];

            // Formato del registro
            $logMessage = implode(' | ', array_map(
                fn($key, $value) => "$key: $value",
                array_keys($logData),
                $logData
            )) . PHP_EOL;

            // Ruta del archivo (asegúrate que storage/logs tenga permisos de escritura)
            $filePath = storage_path('logs/sesiones.txt');

            // Escribir en el archivo
            file_put_contents($filePath, $logMessage, FILE_APPEND);

        } catch (\Exception $e) {
            Log::error('Error al registrar sesión: ' . $e->getMessage());
        }
    }
    //Obtiene pais porip de conexiono
    protected function getCountryFromIp($ip)
    {
        try {
            $response = @file_get_contents("http://ip-api.com/json/{$ip}");
            if ($response === false) {
                return 'Desconocido';
            }

            $data = json_decode($response, true);
            return $data['country'] ?? 'Desconocido';
        } catch (\Exception $e) {
            return 'Desconocido';
        }
    }
}