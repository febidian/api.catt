<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Catagories;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\NoteRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use App\Http\Resources\NotesResource;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\NoteShowResource;
use Intervention\Image\Drivers\Gd\Driver;
use App\Http\Resources\CatagoriesResource;

class NoteController extends Controller
{
    public function notes($category = null)
    {
        try {
            $user = Auth::user();
            if ($category === null) {
                $notes = Note::where('user_id', $user->note_user_id)
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
                'message' => "Note successfully saved.",
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'failed',
                'message' => "Note failed to be saved.",
                'th' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $note = Note::where('user_id', $user->note_user_id)->where('note_id', $id)
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

    public function uploadImage(Request $request)
    {
        try {
            if ($request->hasFile('file')) {
                $path = $request->file('file')->store('uploads');
                $manager = new ImageManager(Driver::class);
                $resize = $manager->read($request->file('file'));
                $resize->scale(height: 384);
                $resize->save(public_path("storage/{$path}"));
                $url = Storage::url($path);

                return response()->json(['success' => 1, 'file' => ['url' => $url]], Response::HTTP_OK);
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
}
