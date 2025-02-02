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
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();
        try {
            $id = $this->idrandom();
            $user = User::create([
                'name' => $request->name,
                'note_user_id' => $id,
                'category_user_id' => $id,
                'private_id' => $this->idrandom(),
                'email' => $request->email,
                'password' => bcrypt($request->password)
            ]);

            $user->privateNote()->create([
                'password' => null
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Successfully created an account.',
                'status' => 'success'
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create an account.',
                'status' => 'failed',
                'error' => $th
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
                    'private_id' => $this->idrandom(),
                    'email' => $socialiteUser->email,
                    'email_verified_at' => now(),
                ],
            );

            $user->privateNote()->create([
                'password' => null
            ]);

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
                'message' => "Something went wrong, please try again.",
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
                    'private_id' => $this->idrandom(),
                    'note_user_id' => $id,
                    'category_user_id' => $id,
                    'email_verified_at' => now(),
                    'notes_user_id' => $this->idrandom(),
                ],
            );

            $user->privateNote()->create([
                'password' => null
            ]);

            $token = Auth::login($user);

            return response()->json([
                'message' => 'You have successfully logged in.',
                'user' => new UserResource($user),
                'status' => 'success',
                'token' => $token
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => "failed",
                'message' => "Something went wrong, please try again.",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function refresh(Request $request)
    {
        config([
            'jwt.blacklist_enabled' => false
        ]);
        $rawToken = $request->cookie('jwt_token');

        if ($rawToken) {
            $request->headers->set('Authorization', 'Bearer' . $rawToken);
        }

        try {
            $token = Auth::refresh();
            return response()->json([
                'message' => 'Token successfully updated.',
                'status' => 'success',
                'token' => $token,
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Token update failed'], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function logout(Request $request)
    {
        $rawToken = $request->cookie('jwt_token');

        if ($rawToken) {
            $request->headers->set('Authorization', 'Bearer' . $rawToken);
        }
        try {
            Auth::logout();
            return response()->json([
                'message' => 'Successfully logged out',
                'status' => 'success',
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to logout',
                'status' => 'failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function idrandom()
    {

        $customId = hexdec(uniqid());

        return $customId;
    }
}
