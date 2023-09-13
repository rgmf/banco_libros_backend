<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\LogoutRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        // GDC login post
        $response = Http::post('https://gdc.ieslaencanta.com/srv/api/login', [
            'username' => $request->name,
            'password' => $request->password
        ]);

        if ($response->status() != "200") {
            return response()->json(
                ['error' => 'Credenciales no válidas'],
                401
            );
        }

        $email = $response->json()['user']['email'];
        $token = $response->json()['token'];

        $user = User::where('name', $request->name)->first();
        if (!$user) {
            $user = new User();
            $user->name = $request->name;
            $user->email = $email;
            $user->password = bcrypt($request->password);
        }
        $user->gdc_token = $token;
        $user->gdc_token_expiration = Carbon::now()->addDays(1);
        $user->save();

        return response()->json(['user' => $user, 'token' => $token]);
    }

    public function logout(LogoutRequest $request)
    {
        if (User::where('name', $request->name)->delete() > 0) {
            return response()->json(
                ['ok' => 'Sesión cerrada correctamente'],
                200
            );
        }

        return response()->json(
            ['error' => 'No existe el usuario sobre el que cerrar la sesión'],
            400
        );
    }
}
