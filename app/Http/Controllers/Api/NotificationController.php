<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return NotificationResource::collection($notifications);
    }

    public function unread(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->get();

        return NotificationResource::collection($notifications);
    }

    public function markAsRead(Request $request, string $id): Response
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->noContent();
    }

    public function markAllAsRead(Request $request): Response
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->noContent();
    }

    public function destroy(Request $request, string $id): Response
    {
        $request->user()
            ->notifications()
            ->findOrFail($id)
            ->delete();

        return response()->noContent();
    }
}
