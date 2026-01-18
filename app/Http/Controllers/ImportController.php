<?php

namespace App\Http\Controllers;

use App\Jobs\ImportTasksFromCsv;
use App\Models\Board;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function store(Request $request, Board $board)
    {
        if ($board->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        // Stocke le fichier temporairement
        $path = $request->file('file')->store('imports');

        // Dispatch le job (exécuté en arrière-plan par Horizon)
        ImportTasksFromCsv::dispatch($board, $path, auth()->id());

        return redirect()->back()->with('success', 'Import en cours... Les tâches apparaîtront bientôt.');
    }
}
