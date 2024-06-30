<?php

namespace App\Http\Controllers;

use App\Http\Resources\SearchResource;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke($title)
    {
        $user = Auth::user();
        $notes = Note::where('user_id', $user->note_user_id)
            ->where('title', 'like', '%' . $title . '%')
            ->with('category')
            ->with('stars')
            ->limit(3)
            ->get();

        return response()->json([
            'notes' => SearchResource::collection($notes),
            'status' => 'success',
        ], Response::HTTP_OK);
    }
}
