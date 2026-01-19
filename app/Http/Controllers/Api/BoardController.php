<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BoardResource;
use App\Models\Board;
use App\Services\BoardService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BoardController extends Controller
{
    public function __construct(
        private readonly BoardService $boardService
    ) {}

    /**
     * GET /api/boards
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $boards = $this->boardService->getAllForUser($request->user());

        return BoardResource::collection($boards);
    }

    /**
     * POST /api/boards
     */
    public function store(Request $request): BoardResource
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $board = $this->boardService->create($request->user(), $validated);

        return new BoardResource($board);
    }

    /**
     * GET /api/boards/{board}
     */
    public function show(Board $board): BoardResource
    {
        $this->authorize('view', $board);

        $board = $this->boardService->getWithRelations($board);

        return new BoardResource($board);
    }

    /**
     * PUT /api/boards/{board}
     */
    public function update(Request $request, Board $board): BoardResource
    {
        $this->authorize('update', $board);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $board = $this->boardService->update($board, $validated);

        return new BoardResource($board);
    }

    /**
     * DELETE /api/boards/{board}
     */
    public function destroy(Board $board): Response
    {
        $this->authorize('delete', $board);

        $this->boardService->delete($board);

        return response()->noContent();
    }
}
