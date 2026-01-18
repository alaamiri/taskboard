<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BoardController extends Controller
{
    public function index()
    {
        $boards = Board::where('user_id', auth()->id())
            ->with('columns.cards')
            ->get();

        return Inertia::render('Boards/Index', [
            'boards' => $boards
        ]);
    }

    public function create()
    {
        return Inertia::render('Boards/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $board = auth()->user()->boards()->create($validated);

        return redirect()->route('boards.show', $board)
            ->with('success', 'Board créé !');
    }

    public function show(Board $board)
    {
        $this->authorize('view', $board);

        $board->load('columns.cards');

        return Inertia::render('Boards/Show', [
            'board' => $board
        ]);
    }

    public function edit(Board $board)
    {
        $this->authorize('update', $board);

        return Inertia::render('Boards/Edit', [
            'board' => $board
        ]);
    }

    public function update(Request $request, Board $board)
    {
        $this->authorize('update', $board);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $board->update($validated);

        return redirect()->route('boards.show', $board)
            ->with('success', 'Board mis à jour !');
    }

    public function destroy(Board $board)
    {
        $this->authorize('delete', $board);

        $board->delete();

        return redirect()->route('boards.index')
            ->with('success', 'Board supprimé !');
    }
}
