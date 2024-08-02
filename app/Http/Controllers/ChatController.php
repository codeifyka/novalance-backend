<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $chats = Chat::where('client_id', auth('api')->user()->id)
        ->with('freelancer')
        ->get();

    return response()->json(['data' => $chats]);
    }

    public function store(Request $request)
    {
        $chat = Chat::create([
            'freelancer_id' => $request->freelancer_id,
            'client_id' => $request->client_id,
            'job_post_id' => $request->job_post_id,
            'started_at' => $request->started_at,
        ]);

        return response()->json($chat);
    }

}
