<?php

namespace App\Http\Controllers;

use App\Models\Star;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StarController extends Controller
{
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
