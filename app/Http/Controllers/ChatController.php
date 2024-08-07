<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Message;

class ChatController extends Controller
{
    public function index(Request $request)
    {
    
        $userId = auth('api')->user()->id;
        $account = auth('api')->user()->account_type;
        $chats = null ;
        if ($account === 'client') {
            $chats = Chat::where('client_id', $userId)
                ->with('freelancer')
                ->get();
        } else {
            $chats = Chat::where('freelancer_id', $userId)
                ->with('client')
                ->get();
        }

        return response()->json(["data"=>$chats]);
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

    public function storeMessage(Request $request)
    {
        $chat = Chat::find($request->chat_id);
        if(auth('api')->user()->account_type == 'freelancer'){
            $message = Message::create([
                'sender' => auth('api')->user()->id,
                'reciever' => $chat->client_id,
                'message' => $request->message,
                'chat_id' => $chat->id
            ]);
        }else{
            $message = Message::create([
                'sender' => auth('api')->user()->id,
                'reciever' => $chat->freelancer_id,
                'message' => $request->message,
                'chat_id' => $chat->id
            ]);
        }
        if($message) {
            return response()->json(["data" => $message]);
        }else{
            return response()->json(["error" => "Something went wrong"]);
        }
    }

    public function getAllMessages(Request $request)
    {
        $messages = Message::where('chat_id', $request->chat_id)->get();

        if($messages) {
            return response()->json(["data" => $messages]);
        }else{
            return response()->json(["error" => "no messages"]);
        }
    }
}
