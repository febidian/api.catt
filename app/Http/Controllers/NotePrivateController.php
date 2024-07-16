<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotePriateRequest;
use App\Http\Resources\NoteShowResource;
use App\Http\Resources\NotesResource;
use App\Models\Note;
use App\Models\PrivateNote;
use App\Models\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class NotePrivateController extends Controller
{
    public function private(Request $request)
    {
        $user = Auth::user();
        $checkPrivate = User::where('id', $user->id)
            ->whereHas('privateNote', function ($q) {
                $q->where('password', '!=', null);
            })->first();

        if ($checkPrivate != null) {
            if (Hash::check($request->password, $checkPrivate->privateNote->password)) {

                $notes = Note::where('user_id', $user->note_user_id)
                    ->where('private', 1)
                    ->with('category')
                    ->with('stars')
                    ->whereHas('stars', function ($q) {
                        $q->where('star', false);
                    })
                    ->orderBy('updated_at', 'desc')
                    ->paginate(10);

                return response()->json([
                    'notes' => NotesResource::collection($notes)->response()->getData(),
                    'status' => 'success',
                ], Response::HTTP_OK);
            } else {
                return response()->json([
                    'message' => "The password you entered is incorrect",
                    'password' => true,
                    'status' => 'failed',
                ], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return response()->json([
                'message' => "Please create a password first",
                'password' => false,
                'status' => 'failed',
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function createPassword(NotePriateRequest $request)
    {
        try {
            $user = Auth::user();
            PrivateNote::where('user_private_id', $user->private_id)->update([
                'password' => bcrypt($request->password)
            ]);

            return response()->json([
                'message' => "Password created successfully",
                'status' => 'success',
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => "Failed to create password",
                'status' => 'failed',
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function show($note_id, $password = null)
    {
        try {
            $user = Auth::user();
            if ($password !== null) {
                if (Hash::check($password, $user->privateNote->password)) {
                    $note = Note::where('user_id', $user->note_user_id)
                        ->where('note_id', $note_id)
                        ->where('private', 1)
                        ->with('category')
                        ->with('stars')->first();

                    return response()->json([
                        'status' => 'success',
                        "note" => new NoteShowResource($note),
                    ], Response::HTTP_OK);
                } else {
                    return response()->json([
                        'message' => "The password you entered is incorrect",
                        'status' => 'failed',
                    ], Response::HTTP_BAD_REQUEST);
                }
            } else {
                return response()->json([
                    'message' => "The password you entered is incorrect",
                    'status' => 'failed',
                ], Response::HTTP_BAD_REQUEST);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'failed',
                'message' => "The password you entered is incorrect",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($note_id)
    {
        $user = Auth::user();
        try {
            $checkPrivate = User::where('id', $user->id)
                ->whereHas('privateNote', function ($q) {
                    $q->where('password', '!=', null);
                })->first();

            if ($checkPrivate != null) {
                $checkStar = Note::where('note_id', $note_id)
                    ->whereHas('stars', function ($q) {
                        $q->where('star', '!=', true);
                    })->first();

                if ($checkStar) {
                    Note::where('note_id', $note_id)->update([
                        'private' => $checkStar->private == 1 ? 0 : 1
                    ]);
                    return response()->json([
                        'message' => "The note has been successfully locked",
                        'private' => $checkStar->private == 1 ? 0 : 1,
                        'status' => 'success',
                    ], Response::HTTP_OK);
                } else {
                    return response()->json([
                        'message' => "Starred notes cannot be locked",
                        'status' => 'failed',
                    ], Response::HTTP_BAD_REQUEST);
                }
            } else {
                return response()->json([
                    'message' => "You need to set a password in the private menu",
                    'status' => 'failed',
                ], Response::HTTP_BAD_REQUEST);
            }
        } catch (QueryException $th) {
            return response()->json([
                'message' => "Failed to lock the note",
                'status' => 'failed',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
