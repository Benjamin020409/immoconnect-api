<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    // ─── Liste des conversations ──────────────────────────────
    public function conversations()
    {
        $userId = auth()->id();

        // Récupérer tous les utilisateurs avec qui on a échangé
        $conversations = Message::with([
                'sender:id,name,avatar,phone',
                'receiver:id,name,avatar,phone',
                'property:id,title',
            ])
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(function ($msg) use ($userId) {
                // Grouper par l'autre utilisateur
                return $msg->sender_id === $userId
                    ? $msg->receiver_id
                    : $msg->sender_id;
            })
            ->map(function ($messages, $otherUserId) use ($userId) {
                $lastMsg   = $messages->first();
                $otherUser = $lastMsg->sender_id === $userId
                    ? $lastMsg->receiver
                    : $lastMsg->sender;

                $unreadCount = $messages->filter(
                    fn($m) => $m->receiver_id === $userId && !$m->is_read
                )->count();

                return [
                    'other_user'    => $otherUser,
                    'last_message'  => $lastMsg,
                    'unread_count'  => $unreadCount,
                    'property'      => $lastMsg->property,
                    'property_id'   => $lastMsg->property_id,
                ];
            })
            ->values();

        return response()->json($conversations);
    }

    // ─── Conversation avec un utilisateur ────────────────────
    public function conversation($userId)
    {
        $authId = auth()->id();

        $messages = Message::with([
                'sender:id,name,avatar',
                'receiver:id,name,avatar',
                'property:id,title',
            ])
            ->where(function ($q) use ($authId, $userId) {
                $q->where('sender_id', $authId)->where('receiver_id', $userId);
            })
            ->orWhere(function ($q) use ($authId, $userId) {
                $q->where('sender_id', $userId)->where('receiver_id', $authId);
            })
            ->oldest()
            ->get();

        // Marquer les messages reçus comme lus
        Message::where('sender_id', $userId)
            ->where('receiver_id', $authId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    // ─── Envoyer un message ───────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'property_id' => 'required|exists:properties,id',
            'content'     => 'required|string|max:1000',
        ]);

        // Empêcher d'envoyer un message à soi-même
        if ($validated['receiver_id'] == auth()->id()) {
            return response()->json([
                'message' => 'Vous ne pouvez pas vous envoyer un message.'
            ], 422);
        }

        $message = Message::create([
            'sender_id'   => auth()->id(),
            'receiver_id' => $validated['receiver_id'],
            'property_id' => $validated['property_id'],
            'content'     => $validated['content'],
            'is_read'     => false,
        ]);

        return response()->json([
            'message' => 'Message envoyé.',
            'data'    => $message->load([
                'sender:id,name,avatar',
                'receiver:id,name,avatar',
                'property:id,title',
            ]),
        ], 201);
    }

    // ─── Marquer comme lu ─────────────────────────────────────
    public function markRead($id)
    {
        Message::where('id', $id)
            ->where('receiver_id', auth()->id())
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Message marqué comme lu.']);
    }

    // ─── Liste simple (pour dashboard) ───────────────────────
    public function index()
    {
        $userId   = auth()->id();
        $messages = Message::with([
                'sender:id,name,avatar',
                'receiver:id,name,avatar',
                'property:id,title',
            ])
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->latest()
            ->take(50)
            ->get();

        return response()->json($messages);
    }
}