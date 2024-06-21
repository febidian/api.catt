<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoteRequest;
use App\Models\Catagories;
use App\Models\Note;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NoteController extends Controller
{
    public function create(NoteRequest $request)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();

            $category = Catagories::where('user_id', $user->note_user_id)->where('category_name', $request->category)->first();

            if (!$category) {
                $note = Note::create([
                    'user_id' => $user->note_user_id,
                    'note_id' => $this->idrandom(),
                    'category_id' => $this->idrandom(),
                    'title' => $request->title,
                    'note_content' => $request->note,
                    'star_note_id' => $this->idrandom(),
                ]);

                $note->category()->create([
                    'user_id' => $user->category_user_id,
                    'category_name' => $request->category,
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

    public function uploadImage(Request $request)
    {
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('uploads');
            $url = Storage::url($path);

            return response()->json(['success' => 1, 'file' => ['url' => $url]], Response::HTTP_OK);
        }
        return response()->json(['success' => 0, 'message' => 'No image uploaded'], Response::HTTP_BAD_REQUEST);
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
