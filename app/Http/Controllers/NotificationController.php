<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Daftar notifikasi user yang login (dengan jumlah belum dibaca).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $perPage = $request->get('per_page', 10);
        $notifications = $user->notifications()->orderByDesc('created_at')->paginate($perPage);

        $items = $notifications->map(function (DatabaseNotification $n) {
            $data = is_array($n->data) ? ($n->data ?? []) : (json_decode((string) $n->data, true) ?? []);

            return [
                'id' => (string) $n->id,
                'icon' => $data['icon'] ?? 'bell',
                'title' => $data['title'] ?? '',
                'message' => $data['message'] ?? '',
                'store_id' => $data['store_id'] ?? null,
                'store_name' => $data['store_name'] ?? null,
                'role' => $data['role'] ?? null,
                'token' => $data['token'] ?? null,
                'action' => $data['action'] ?? null,
                'read_at' => $n->read_at?->toISOString(),
                'created_at' => $n->created_at?->toISOString(),
            ];
        });

        return response()->json([
            'data' => $items,
            'unread_count' => $user->unreadNotifications()->count(),
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
            'total' => $notifications->total(),
            'per_page' => $notifications->perPage(),
        ]);
    }

    /**
     * Jumlah notifikasi belum dibaca (untuk badge lonceng di navbar).
     */
    public function unreadCount()
    {
        return response()->json([
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai dibaca.
     */
    public function markRead(DatabaseNotification $notification)
    {
        $user = Auth::user();

        if ((string) $notification->notifiable_id !== (string) $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json([
            'message' => 'OK',
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Tandai semua notifikasi sebagai dibaca.
     */
    public function readAll()
    {
        $user = Auth::user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'message' => 'OK',
            'unread_count' => 0,
        ]);
    }
}