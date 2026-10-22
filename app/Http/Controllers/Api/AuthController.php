<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Roles que cualquiera puede elegir al registrarse (§5). */
    private const PUBLIC_ROLES = [User::ROLE_COMPRADOR, User::ROLE_ARTISTA];

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)],
            'role' => ['sometimes', Rule::in(self::PUBLIC_ROLES)],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => $validated['role'] ?? User::ROLE_COMPRADOR,
            ]);

            // El artista completa su perfil en su panel; queda pendiente de revisión (§6).
            if ($user->role === User::ROLE_ARTISTA) {
                $user->artist()->create([
                    'name' => $user->name,
                    'slug' => Artist::uniqueSlug($user->name),
                    'status' => Artist::STATUS_PENDIENTE,
                ]);
            }

            return $user;
        });

        return response()->json($this->session($user), 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['El correo o la contraseña no son correctos.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Esta cuenta está desactivada.'],
            ]);
        }

        return response()->json($this->session($user));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->profile($request->user()));
    }

    public function updateMe(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => 'required_with:password|current_password:sanctum',
            'password' => ['sometimes', 'required', 'string', 'confirmed', PasswordRule::min(8)],
        ]);

        $user->update(collect($validated)->only(['name', 'email', 'password'])->all());

        return response()->json($this->profile($user->fresh()));
    }

    /** Siempre responde lo mismo, exista o no el correo, para no revelar qué cuentas existen. */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|string|email']);

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|string|email',
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                $user->tokens()->delete();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['El enlace no es válido o ya venció. Pide uno nuevo.'],
            ]);
        }

        return response()->json(['message' => 'Tu contraseña fue actualizada. Ya puedes iniciar sesión.']);
    }

    private function session(User $user): array
    {
        return [
            'user' => $this->profile($user),
            'token' => $user->createToken('auth-token')->plainTextToken,
        ];
    }

    /** Datos del usuario para el frontend, con el estado de su perfil si es artista. */
    private function profile(User $user): array
    {
        $data = $user->only(['id', 'name', 'email', 'role', 'is_active']);

        if ($user->role === User::ROLE_ARTISTA) {
            $data['artist_status'] = $user->artist?->status;
            $data['artist_panel_url'] = route('login'); // el login único lo lleva a su panel
        }

        return $data;
    }
}
