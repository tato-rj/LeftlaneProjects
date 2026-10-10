<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Projects\Quickreads\Api\V2\{Responses, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Password, Validator};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public static function profile(User $user): array
    {
        return ['id' => (string) $user->id, 'legacy_account_id' => $user->facebook_id,
            'email' => $user->email, 'first_name' => $user->first_name,
            'last_name' => $user->last_name, 'locale' => $user->locale];
    }

    private function passwordRules(): array
    {
        return ['required', 'string', 'min:8', 'max:72', 'confirmed',
            function ($attribute, $value, $fail) {
                if (is_string($value) && strlen($value) > 72) {
                    $fail('The password must not exceed 72 UTF-8 bytes.');
                }
            }];
    }

    private function session(Request $request, User $user, int $status = 200)
    {
        $expires = now()->addDays(config('quickreads_v2.token_lifetime_days'));
        $token = $user->createToken('quickreads-ios-v2', ['quickreads:v2'], $expires);
        return Responses::json($request, ['data' => ['user' => self::profile($user),
            'access_token' => $token->plainTextToken, 'token_type' => 'Bearer',
            'expires_at' => $expires->utc()->toIso8601String()]], false)->setStatusCode($status);
    }

    public function login(Request $request)
    {
        $input = Validator::make($request->json()->all(), [
            'email' => 'required|string|email|max:255', 'password' => 'required|string|max:4096',
        ])->validate();
        return DB::connection('quickreads')->transaction(function () use ($input, $request) {
            $user = User::where('email', $input['email'])->lockForUpdate()->first();
            $valid = false;
            // Some social-only legacy accounts have no usable password hash.
            if ($user && $user->password) {
                try {
                    $valid = Hash::check($input['password'], $user->password);
                } catch (\RuntimeException $e) {
                    $valid = false;
                }
            } else {
                // Fixed valid bcrypt hash reduces obvious missing-account timing differences.
                Hash::check($input['password'], '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
            }
            abort_unless($valid, 401);
            return $this->session($request, $user);
        });
    }

    public function register(Request $request)
    {
        $input = Validator::make($request->json()->all(), [
            'email' => 'required|string|email|max:255|unique:quickreads.users,email',
            'password' => $this->passwordRules(),
            'first_name' => 'required|string|max:255', 'last_name' => 'required|string|max:255',
            'locale' => 'nullable|string|max:35',
        ])->validate();
        return DB::connection('quickreads')->transaction(function () use ($input, $request) {
            try {
                $user = User::create([
                    'slug' => Str::slug($input['first_name'].' '.$input['last_name']),
                    'email' => $input['email'], 'first_name' => $input['first_name'],
                    'last_name' => $input['last_name'], 'locale' => $input['locale'] ?? null,
                    'facebook_id' => Hash::make(Str::uuid()->toString()),
                    'password' => Hash::make($input['password']),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // The unique database constraint also protects concurrent registrations.
                if (in_array($e->getCode(), ['23000', '23505'])) {
                    throw ValidationException::withMessages(['email' => ['This email is already registered.']]);
                }
                throw $e;
            }
            return $this->session($request, $user, 201);
        });
    }

    public function me(Request $request)
    {
        return Responses::json($request, ['data' => self::profile($request->user())], false);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return Responses::json($request, ['data' => ['revoked' => true]], false);
    }

    public function forgotPassword(Request $request)
    {
        $input = Validator::make($request->json()->all(), ['email' => 'required|string|email|max:255'])->validate();
        Password::broker('quickreads-v2')->sendResetLink($input);
        // Same acknowledgement for unknown emails and broker-throttled requests.
        return Responses::json($request, ['data' => ['accepted' => true,
            'message' => 'If this account exists, password reset instructions will be sent.']], false)->setStatusCode(202);
    }

    public function resetPassword(Request $request)
    {
        $input = Validator::make($request->json()->all(), [
            'email' => 'required|string|email|max:255', 'token' => 'required|string|max:255',
            'password' => $this->passwordRules(),
        ])->validate();
        $status = DB::connection('quickreads')->transaction(function () use ($input) {
            // Serialize token validation as well as the password change: a
            // concurrent reset must not consume the same reset token twice.
            User::where('email', $input['email'])->lockForUpdate()->first();
            return Password::broker('quickreads-v2')->reset($input, function ($user, $password) {
                $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                $user->tokens()->delete();
            });
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['token' => ['The password reset token is invalid or expired.']]);
        }
        return Responses::json($request, ['data' => ['reset' => true]], false);
    }
}
