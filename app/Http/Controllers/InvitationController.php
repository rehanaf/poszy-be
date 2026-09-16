<?php

namespace App\Http\Controllers;

use App\Models\StoreInvitation;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    /**
     * Terima undangan bergabung ke toko.
     * Hanya user yang emailnya sesuai undangan yang bisa menerima.
     */
    public function accept(Request $request, string $token)
    {
        $invitation = StoreInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->with('store')
            ->first();

        if (! $invitation) {
            return response()->json(['message' => 'Undangan tidak ditemukan atau sudah digunakan.'], 404);
        }

        $user = $request->user();

        if (strtolower($invitation->email) !== strtolower($user->email)) {
            return response()->json(['message' => 'Undangan ini ditujukan untuk email lain.'], 403);
        }

        // Tandai notifikasi terkait sebagai sudah dibaca (bila ada).
        $user->notifications()
            ->where('data->token', $token)
            ->update(['read_at' => now()]);

        $alreadyMember = $user->stores()
            ->where('store_user.store_id', $invitation->store_id)
            ->exists();

        if ($alreadyMember) {
            $invitation->update(['accepted_at' => now()]);
            return response()->json([
                'message' => 'Anda sudah menjadi anggota toko ' . $invitation->store->name . '.',
                'store_id' => $invitation->store_id,
            ]);
        }

        $user->stores()->attach($invitation->store_id, ['role' => $invitation->role]);
        $invitation->update(['accepted_at' => now()]);

        return response()->json([
            'message' => 'Selamat! Anda bergabung dengan toko "' . $invitation->store->name . '" sebagai ' . ucfirst($invitation->role) . '.',
            'store_id' => $invitation->store_id,
        ]);
    }
}