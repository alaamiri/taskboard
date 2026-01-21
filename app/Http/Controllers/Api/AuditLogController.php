<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
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
