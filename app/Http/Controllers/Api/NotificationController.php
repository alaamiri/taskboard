<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @group Notifications
 *
 * APIs for managing user notifications
 */
class NotificationController extends Controller
{
    /**
     * List all notifications
     *
     * Returns paginated notifications for the authenticated user.
     *
     * @authenticated
     *
     * @queryParam page integer The page number. Example: 1
     *
     * @response 200 scenario="success" {
     *   "data": [
     *     {
     *       "id": "550e8400-e29b-41d4-a716-446655440000",
     *       "type": "App\\Notifications\\CardMovedNotification",
     *       "data": {
     *         "card_id": 1,
     *         "card_title": "Task 1",
     *         "moved_by": "John Doe",
     *         "from_column": "To Do",
     *         "to_column": "Done"
     *       },
     *       "read_at": null,
     *       "created_at": "2026-01-25T10:00:00.000000Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/notifications?page=1",
     *     "last": "http://localhost/api/notifications?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "per_page": 20,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     * @response 401 scenario="unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return NotificationResource::collection($notifications);
    }

    /**
     * List unread notifications
     *
     * Returns all unread notifications for the authenticated user.
     *
     * @authenticated
     *
     * @response 200 scenario="success" {
     *   "data": [
     *     {
     *       "id": "550e8400-e29b-41d4-a716-446655440000",
     *       "type": "App\\Notifications\\CardMovedNotification",
     *       "data": {
     *         "card_id": 1,
     *         "card_title": "Task 1",
     *         "moved_by": "John Doe",
     *         "from_column": "To Do",
     *         "to_column": "Done"
     *       },
     *       "read_at": null,
     *       "created_at": "2026-01-25T10:00:00.000000Z"
     *     }
     *   ]
     * }
     * @response 401 scenario="unauthenticated" {"message": "Unauthenticated."}
     */
    public function unread(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->get();

        return NotificationResource::collection($notifications);
    }

    /**
     * Mark notification as read
     *
     * Marks a specific notification as read.
     *
     * @authenticated
     *
     * @urlParam id string required The notification UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 204 scenario="success"
     * @response 401 scenario="unauthenticated" {"message": "Unauthenticated."}
     * @response 404 scenario="not found" {"message": "Notification not found."}
     */
    public function markAsRead(Request $request, string $id): Response
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->noContent();
    }

    /**
     * Mark all notifications as read
     *
     * Marks all unread notifications as read for the authenticated user.
     *
     * @authenticated
     *
     * @response 204 scenario="success"
     * @response 401 scenario="unauthenticated" {"message": "Unauthenticated."}
     */
    public function markAllAsRead(Request $request): Response
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->noContent();
    }

    /**
     * Delete a notification
     *
     * Permanently deletes a notification.
     *
     * @authenticated
     *
     * @urlParam id string required The notification UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 204 scenario="deleted"
     * @response 401 scenario="unauthenticated" {"message": "Unauthenticated."}
     * @response 404 scenario="not found" {"message": "Notification not found."}
     */
    public function destroy(Request $request, string $id): Response
    {
        $request->user()
            ->notifications()
            ->findOrFail($id)
            ->delete();

        return response()->noContent();
    }
}
