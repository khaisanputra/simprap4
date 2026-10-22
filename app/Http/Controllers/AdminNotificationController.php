<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Support\AdminNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    protected function ensureAdmin(Request $request)
    {
        $user = $request->user() ?? Auth::user();
        if (!$user || ($user->role ?? 'siswa') !== 'admin') {
            abort(403, 'Hanya admin yang dapat mengakses notifikasi.');
        }
        return $user;
    }

    /**
     * Jumlah belum dibaca — untuk badge
     */
    public function unreadCount(Request $request)
    {
        $this->ensureAdmin($request);
        try {
            AdminNotificationService::syncAlphaRecent(7);
        } catch (\Throwable $e) {
        }
        $count = AdminNotification::unread()->count();
        return response()->json(['count' => $count]);
    }

    /**
     * Data dropdown terbaru (5 terbaru)
     */
    public function dropdown(Request $request)
    {
        $this->ensureAdmin($request);
        try {
            AdminNotificationService::syncAlphaRecent(7);
        } catch (\Throwable $e) {
        }

        $items = AdminNotification::latestFirst()->limit(6)->get();
        $unread = AdminNotification::unread()->count();

        // Format untuk JS: id, type, title, message, student_name, tanggal, created_at diff, is_read, icon, color, bg
        $data = $items->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => $n->type,
                'type_label' => $n->type_label,
                'title' => $n->title,
                'message' => $n->message,
                'student_name' => $n->student_name,
                'tanggal' => $n->tanggal ? $n->tanggal->format('Y-m-d') : null,
                'tanggal_fmt' => $n->tanggal ? \Carbon\Carbon::parse($n->tanggal)->translatedFormat('d F Y') : '-',
                'created_at' => $n->created_at->toIso8601String(),
                'created_human' => $n->created_at->diffForHumans(),
                'created_time' => $n->created_at->format('H:i'),
                'is_read' => $n->is_read,
                'icon' => $n->icon,
                'color' => $n->color,
                'bg' => $n->bg,
                // Link relevan: arahkan ke data-absen dengan filter tanggal atau ke dashboard
                'link' => route('data-absen-masuk.index', ['tanggal' => $n->tanggal ? $n->tanggal->format('Y-m-d') : now()->toDateString()]),
            ];
        });

        return response()->json([
            'unread' => $unread,
            'items' => $data,
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai dibaca
     */
    public function markRead(Request $request, $id)
    {
        $this->ensureAdmin($request);
        $notif = AdminNotification::findOrFail($id);
        $notif->markAsRead();
        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('notif_success', 'Notifikasi ditandai sudah dibaca.');
    }

    /**
     * Tandai semua sebagai dibaca
     */
    public function markAllRead(Request $request)
    {
        $this->ensureAdmin($request);
        AdminNotification::unread()->update(['read_at' => now()]);
        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('notif_success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
//hallo