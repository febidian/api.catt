<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
            $id = $this->idrandom();
            User::create([
                'name' => $request->name,
                'note_user_id' => $id,
                'category_user_id' => $id,
                'email' => $request->email,
                'password' => bcrypt($request->password)
            ]);

            return response()->json([
                'message' => 'Successfully created an account.',
                'status' => 'success'
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to create an account.',
                'status' => 'failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->only('email', 'password');
            $user = User::where('email', '=', $credentials['email'])->firstOrFail();

            if ($user->password === null) {
                return response()->json([
                    'errors' => [
                        'email' => "It appears the account uses a different login method.",
                    ],
                    'status' => 'failed'
                ], Response::HTTP_UNAUTHORIZED);
            } else if (!$token = auth()->attempt($credentials)) {
                return response()->json([
                    'status' => 'failed',
                    'errors' => [
                        'email' => ['Email or password is incorrect, please try again.'],
                    ],
                ], Response::HTTP_UNAUTHORIZED);
            }
            return response()->json([
                'message' => 'You have successfully logged in.',
                'user' => new UserResource($user),
                'status' => 'success',
                'token' => $token,
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'errors' => [
                    'email' => "Email or password is incorrect, please try again.",
                ],
                'status' => 'failed'
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function redirectToGoogle()
    {
        return response()->json([
            'url' => Socialite::driver('google')->stateless()
                ->redirect()
                ->getTargetUrl()
        ]);
    }

    public function handleGoogleCallback()
    {
        $socialiteUser = Socialite::driver('google')->stateless()->user();

        try {
            $id = $this->idrandom();
            $user = User::firstOrCreate(
                ['google_id' => $socialiteUser->id],
                [
                    'name' => $socialiteUser->name,
                    'note_user_id' => $id,
                    'category_user_id' => $id,
                    'email' => $socialiteUser->email,
                    'email_verified_at' => now(),
                ],
            );

            $token = Auth::login($user);

            return response()->json([
                'message' => 'You have successfully logged in.',
                'user' => new UserResource($user),
                'status' => 'success',
                'token' => $token
            ], Response::HTTP_OK);
        } catch (QueryException $th) {
            return response()->json([
                'status' => "failed",
                'th' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function redirectToFacebook()
    {
        return response()->json([
            'url' => Socialite::driver('facebook')->stateless()
                ->redirect()
                ->getTargetUrl()
        ]);
    }

    public function handleFacebookCallback()
    {
        $socialiteUser = Socialite::driver('facebook')->stateless()->user();

        try {
            $id = $this->idrandom();
            $user = User::firstOrCreate(
                ['facebook_id' => $socialiteUser->id],
                [
                    'name' => $socialiteUser->name,
                    'email' => $socialiteUser->email,
                    'note_user_id' => $id,
                    'category_user_id' => $id,
                    'email_verified_at' => now(),
                    'notes_user_id' => $this->idrandom(),
                ],
            );

            if ($user->status === 0) {
                return response()->json([
                    'message' => 'Akun Anda dinonaktifkan.',
                    'status' => 'failed',
                ], Response::HTTP_UNAUTHORIZED);
            } else {
                $minutes = 480;
                $customClaims = ['sub' => $user->id];
                $payload = JWTFactory::customClaims($customClaims)->make();
                $token = JWTAuth::encode($payload);
                $cookie = cookie('jwt_token', $token, $minutes, null, null, true, true);

                return response()->json([
                    'message' => 'Anda Berhasil Login.',
                    'user' => new UserResource($user),
                    'status' => 'success',
                ], Response::HTTP_OK)->withCookie($cookie);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => "failed",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function idrandom()
    {
        $tanggal = now()->format('dmY');
        $jam = now()->format('H');
        $bulan = now()->format('m');
        $tahun = now()->format('Y');
        $randomAngka = mt_rand(1, 999);
        $customId = $tanggal . $bulan . $tahun . $jam . $randomAngka;

        return $customId;
    }
}
