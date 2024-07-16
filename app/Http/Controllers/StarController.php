<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotesResource;
use App\Models\Note;
use App\Models\Star;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class StarController extends Controller
{
    public function stars()
    {
        try {
            $user = Auth::user();
            $notes = Note::where('user_id', $user->note_user_id)
                ->where('private', 0)
                ->with('category')
                ->with('stars')
                ->whereHas('stars', function ($q) {
                    $q->where('star', true);
                })
                ->orderBy('updated_at', 'desc')
                ->paginate(16);

            return response()->json([
                'status' => 'success',
                "notes" => NotesResource::collection($notes)->response()->getData(),
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'failed',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
    public function update($id)
    {
        try {
            $star = Star::where("star_id", $id)->firstOrFail();
            $star->update([
                "star" => !$star->star
            ]);
            return response()->json([
                'star' => $star->star,
                "status" => "success",
                "message" => "Star updated successfully"
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "failed",
                "message" => "Star updated failed"
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
