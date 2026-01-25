<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * @group Audit Logs
 *
 * APIs for viewing audit logs (Admin only)
 */
class AuditLogController extends Controller
{
    /**
     * List audit logs
     *
     * Returns paginated audit logs. Requires admin role.
     * This endpoint is rate-limited to 10 requests per minute.
     *
     * @authenticated
     *
     * @queryParam user_id integer Filter by user who performed the action. Example: 1
     * @queryParam model_type string Filter by model type (Board, Column, Card). Example: Board
     * @queryParam action string Filter by action type (created, updated, deleted). Example: created
     * @queryParam page integer The page number. Example: 1
     *
     * @response 200 scenario="success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "description": "Board created",
     *       "subject_type": "App\\Models\\Board",
     *       "subject_id": 1,
     *       "causer_type": "App\\Models\\User",
     *       "causer_id": 1,
     *       "properties": {
     *         "attributes": {
     *           "name": "New Board",
     *           "description": "Board description"
     *         }
     *       },
     *       "created_at": "2026-01-25T10:00:00.000000Z",
     *       "causer": {
     *         "id": 1,
     *         "name": "Admin User"
     *       }
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/audit-logs?page=1",
     *     "last": "http://localhost/api/audit-logs?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "per_page": 50,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     * @response 401 scenario="unauthenticated" {"message": "Unauthenticated."}
     * @response 403 scenario="forbidden" {"message": "Accès réservé aux administrateurs."}
     */
    public function index(Request $request)
    {
        // Seulement les admins
        if (!$request->user()->hasRole('admin')) {
            abort(403, 'Accès réservé aux administrateurs.');
        }

        $query = Activity::with('causer', 'subject')
            ->latest();

        // Filtres optionnels
        if ($request->has('user_id')) {
            $query->where('causer_id', $request->user_id);
        }

        if ($request->has('model_type')) {
            $query->where('subject_type', 'like', "%{$request->model_type}%");
        }

        if ($request->has('action')) {
            $query->where('description', 'like', "%{$request->action}%");
        }

        $activities = $query->paginate(50);

        return AuditLogResource::collection($activities);
    }
}
