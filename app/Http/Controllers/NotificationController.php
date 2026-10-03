<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notification',
                'body' => $notification->data['body'] ?? '',
                'url' => $notification->data['url'] ?? null,
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            ]);

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
        ]);
    }

    public function read(Request $request, string $id): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        if (is_string($url) && $url !== '') {
            return redirect()->to($url);
        }

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()?->unreadNotifications->markAsRead();

        return back();
    }
}
