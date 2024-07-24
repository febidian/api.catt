<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Note;
use App\Models\Star;
use App\Models\Share;
use App\Models\Catagories;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\NoteRequest;
use Illuminate\Support\Facades\DB;
use App\Jobs\DeleteAllNotesExpired;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use App\Http\Resources\NotesResource;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\NoteShowResource;
use Intervention\Image\Drivers\Gd\Driver;
use App\Http\Resources\CatagoriesResource;
use App\Http\Resources\ShareNoteShowResource;
use App\Http\Resources\DeleteNoteShowResource;

class NoteController extends Controller
{
    public function notes($category = null)
    {
        try {
            $user = Auth::user();
            if ($category === null) {
                $notes = Note::where('user_id', $user->note_user_id)
                    ->where('private', 0)
                    ->with('category')
                    ->with('stars')
                    ->whereHas('stars', function ($q) {
                        $q->where('star', false);
                    })
                    ->orderBy('updated_at', 'desc')
                    ->paginate(16);
            } else {
                $notes = Note::where('user_id', $user->note_user_id)
                    ->whereHas('category', function ($q) use ($category) {
                        $q->where('category_name', $category);
                    })
                    ->where('private', 0)
                    ->with('category')
                    ->with('stars')
                    ->whereHas('stars', function ($q) {
                        $q->where('star', false);
                    })
                    ->orderBy('updated_at', 'desc')
                    ->paginate(16);
            }
            return response()->json([
                'status' => 'success',
                "notes" => NotesResource::collection($notes)->response()->getData(),
            ], Response::HTTP_OK);
        } catch (QueryException $th) {
            return response()->json([
                'status' => 'failed',
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function create(NoteRequest $request)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();

            $category = Catagories::where('user_id', $user->note_user_id)->where('category_name', $request->category)->first();

            if (!$category) {
                $category = Catagories::create([
                    'user_id' => $user->category_user_id,
                    'category_name' => $request->category,
                    'category_id' => $this->idrandom(),
                ]);

                $note = $category->note()->create([
                    'user_id' => $user->note_user_id,
                    'note_id' => $this->idrandom(),
                    'category_id' => $category->category_id,
                    'title' => $request->title,
                    'note_content' => $request->note,
                    'duplicate_id' => $this->idrandom(),
                    'star_note_id' => $this->idrandom(),
                ]);

                $note->stars()->create([
                    "star" => false
                ]);
            } else {
                $note = Note::create([
                    'user_id' => $user->note_user_id,
                    'note_id' => $this->idrandom(),
                    'category_id' => $category->category_id,
                    'title' => $request->title,
                    'duplicate_id' => $this->idrandom(),
                    'note_content' => $request->note,
                    'star_note_id' => $this->idrandom(),
                ]);
                $note->stars()->create([
                    "star" => false
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => "Note successfully saved",
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'failed',
                'message' => "Note failed to be saved",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $note = Note::where('user_id', $user->note_user_id)->where('note_id', $id)
                ->where('private', 0)
                ->with('category')
                ->with('stars')->first();

            return response()->json([
                'status' => 'success',
                "note" => new NoteShowResource($note),
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(NoteRequest $request, $note_id)

    {
        try {
            $new = $request->new;
            $user = Auth::user();
            if ($new == "true") {
                $category = Catagories::create([
                    'user_id' => $user->category_user_id,
                    'category_name' => $request->category,
                    'category_id' => $this->idrandom(),
                ]);
                Note::where('user_id', $user->note_user_id)
                    ->where('note_id', $note_id)->update([
                        'title' => $request->title,
                        'category_id' => $category->category_id,
                        'note_content' => $request->note,
                    ]);
            } else {
                $category = Catagories::where('user_id', $user->note_user_id)->where('category_name', $request->category)->first();
                Note::where('user_id', $user->note_user_id)
                    ->where('note_id', $note_id)->update([
                        'title' => $request->title,
                        'category_id' => $category->category_id,
                        'note_content' => $request->note,
                    ]);
            }

            return response()->json([
                'message' => 'Note successfully updated.',
                'status' => 'success',
            ], Response::HTTP_OK);
        } catch (QueryException $q) {
            return response()->json([
                'message' => 'Note update failed.',
                'status' => 'failed',
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function uploadImage(Request $request)
    {
        try {
            $auth = Auth::check();
            if ($auth) {
                if ($request->hasFile('file')) {
                    $path = $request->file('file')->store('uploads');
                    $manager = new ImageManager(Driver::class);
                    $resize = $manager->read($request->file('file'));
                    $resize->scale(height: 384);
                    $resize->save(public_path("storage/{$path}"));
                    $url = Storage::url($path);

                    return response()->json(['success' => 1, 'file' => ['url' => $url]], Response::HTTP_OK);
                }
            } else {
                return response()->json(['success' => 0, 'message' => 'No image uploaded'], Response::HTTP_BAD_REQUEST);
            }
        } catch (QueryException $th) {
            return response()->json(['success' => 0, 'message' => 'No image uploaded'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function category()
    {
        $user = Auth::user();
        $category = Catagories::where('user_id', $user->note_user_id)->orderBy('updated_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            "category" => CatagoriesResource::collection($category),
        ], Response::HTTP_OK);
    }

    public function idrandom()
    {
        $customId = hexdec(uniqid());

        return $customId;
    }

    public function showdelete()
    {

        try {
            $deleteNote = Note::where('user_id', Auth::user()->note_user_id)
                ->where('deleted_at', '<', Carbon::now()->subDays(7))
                ->onlyTrashed()
                ->get();

            DeleteAllNotesExpired::dispatch($deleteNote);

            $notes = Note::where('user_id', Auth::user()->note_user_id)
                ->with('category')
                ->with('stars')
                ->whereBetween('deleted_at', [Carbon::now()->subDays(7), Carbon::now()])
                ->orderBy('deleted_at', 'desc')
                ->onlyTrashed()
                ->paginate(16);

            return response()->json([
                'notes' => DeleteNoteShowResource::collection($notes)->response()->getData(),
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (QueryException $q) {
            return response()->json([
                'status' => 'failed'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function softdelete($note_id)
    {
        $user = Auth::user();
        try {
            Note::where('user_id', $user->note_user_id)
                ->where('note_id', $note_id)
                ->whereHas('stars', function ($q) {
                    $q->where('star', false);
                })
                ->first()->delete();

            return response()->json([
                'message' => 'The note was temporarily deleted',
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (\Throwable $q) {
            return response()->json([
                'message' => 'The note was not temporarily deleted',
                'status' => 'failed'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function forcedestroy($note_id)
    {
        try {
            Note::where('user_id', Auth::user()->note_user_id)->withTrashed('note_id', $note_id)->first()->forceDelete();
            return response()->json([
                'message' => 'The note was permanently deleted',
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (QueryException $q) {
            return response()->json([
                'message' => 'The note was not permanently deleted',
                'status' => 'failed'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function forcedestroyall()
    {
        try {
            Note::where('user_id', Auth::user()->note_user_id)->onlyTrashed()->forceDelete();
            return response()->json([
                'message' => '"All notes were permanently deleted',
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (QueryException $q) {
            return response()->json([
                'message' => 'All notes were not permanently deleted',
                'status' => 'failed'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore($note_id)
    {
        try {
            Note::withTrashed()
                ->where('user_id', Auth::user()->note_user_id)
                ->where('note_id', $note_id)->restore();

            return response()->json([
                'message' => 'Notes have been restored',
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (QueryException $q) {
            return response()->json([
                'message' => 'Notes failed to restored',
                'status' => 'failed'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function restoreall()
    {
        try {
            Note::where('user_id', Auth::user()->note_user_id)->onlyTrashed()->restore();

            return response()->json([
                'message' => 'All notes have been restored',
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (QueryException $q) {
            return response()->json([
                'message' => 'All notes failed to restore',
                'status' => 'failed'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function share($note_id)
    {
        try {
            $selectShare = Share::where("user_id", Auth::user()->note_user_id)
                ->where('note_id', $note_id)->first();

            if ($selectShare) {
                if (Carbon::now()->gt($selectShare->expired_at)) {
                    $selectShare->delete();
                    $share =  Share::create([
                        'user_id' => Auth::user()->note_user_id,
                        'note_id' => $note_id,
                        'url_generate' => Str::uuid(),
                        'expired_at' => now()->addMinutes(30),
                    ]);
                } else {
                    $share = Share::where('note_id', $note_id)->first();
                }
            } else {
                $share = Share::create([
                    'user_id' => Auth::user()->note_user_id,
                    'note_id' => $note_id,
                    'url_generate' => Str::uuid(),
                    'expired_at' => now()->addMinutes(30),
                ]);
            }
            return response()->json([
                'url' => $share->url_generate,
                'status' => 'success'
            ], Response::HTTP_CREATED);
        } catch (QueryException $th) {
            return response()->json([
                'status' => 'failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showshare($share_id)
    {
        try {
            $share = Share::where('url_generate', $share_id)->first();
            $auth = Auth::check();

            if ($share) {
                if (Carbon::now()->gt($share->expired_at)) {
                    $share->delete();
                    return response()->json([
                        'message' => 'The link has expired',
                    ], 404);
                } else {
                    $note = Note::where('note_id', $share->note_id)->first();
                }
            } else {
                return response()->json([
                    'message' => 'The link has expired',
                ], 404);
            }

            return response()->json([
                'auth' => $auth,
                'note' => new ShareNoteShowResource($note),
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (QueryException $th) {
            return response()->json([
                'status' => 'failed',
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function duplicate($share_id)
    {
        $share = Share::where('url_generate', $share_id)->first();

        try {
            if ($share) {
                if (Carbon::now()->gt($share->expired_at)) {
                    $share->delete();
                    return response()->json([
                        'message' => 'Link has expired',
                        'status' => 'failed'
                    ], Response::HTTP_NOT_FOUND);
                } else {
                    $user = Auth::user();
                    if ($share->note_id !== $user->note_user_id) {
                        $cloneNote = Note::where('note_id', $share->note_id)->with('category')->first();
                        if ($cloneNote) {
                            $checkDuplicate = Note::where('user_id', $user->note_user_id)
                                ->where('duplicate_id', $cloneNote->duplicate_id)->first();
                            if ($checkDuplicate) {
                                return response()->json([
                                    'message' => 'You have already duplicated this note',
                                    'status' => 'failed'
                                ], Response::HTTP_BAD_REQUEST);
                            } else {
                                $category = Catagories::where('user_id', $user->note_user_id)->where('category_name', $cloneNote->category->category_name)->first();
                                if (!$category) {
                                    $category = Catagories::create([
                                        'user_id' => $user->category_user_id,
                                        'category_name' => $cloneNote->category->category_name,
                                        'category_id' => $this->idrandom(),
                                    ]);

                                    $note = $category->note()->create([
                                        'user_id' => $user->note_user_id,
                                        'note_id' => $this->idrandom(),
                                        'category_id' => $category->category_id,
                                        'duplicate_id' => $cloneNote->duplicate_id,
                                        'title' => $cloneNote->title,
                                        'note_content' => $cloneNote->note_content,
                                        'star_note_id' => $this->idrandom(),
                                    ]);

                                    $note->stars()->create([
                                        "star" => false
                                    ]);
                                } else {
                                    $note = Note::create([
                                        'user_id' => $user->note_user_id,
                                        'note_id' => $this->idrandom(),
                                        'category_id' => $category->category_id,
                                        'duplicate_id' => $cloneNote->duplicate_id,
                                        'title' => $cloneNote->title,
                                        'note_content' => $cloneNote->note_content,
                                        'star_note_id' => $this->idrandom(),
                                    ]);
                                    $note->stars()->create([
                                        "star" => false
                                    ]);
                                }
                                return response()->json([
                                    'message' => 'Note successfully duplicated.',
                                    'status' => 'success'
                                ], Response::HTTP_OK);
                            }
                        } else {
                            return response()->json([
                                'message' => 'Link has expired',
                                'status' => 'failed'
                            ], Response::HTTP_NOT_FOUND);
                        }
                    } else {
                        return response()->json([
                            'message' => 'Do not duplicate this note',
                            'status' => 'failed',
                        ], Response::HTTP_BAD_REQUEST);
                    }
                }
            } else {
                return response()->json([
                    'message' => 'Link has expired',
                    'status' => 'failed'
                ], Response::HTTP_NOT_FOUND);
            }
        } catch (QueryException $th) {
            return response()->json([
                'status' => 'failed'
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
