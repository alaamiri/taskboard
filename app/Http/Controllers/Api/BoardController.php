<?php

namespace App\Http\Controllers\Api;

use App\Data\Board\BoardData;
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

    public function index(Request $request): AnonymousResourceCollection
    {
        $boards = $this->boardService->getAllForUser($request->user());

        return BoardResource::collection($boards);
    }

    public function store(Request $request): BoardResource
    {
        $data = BoardData::from($request);

        $board = $this->boardService->create($request->user(), $data);

        return new BoardResource($board);
    }

    public function show(Board $board): BoardResource
    {
        $this->authorize('view', $board);

        $board = $this->boardService->getWithRelations($board);

        return new BoardResource($board);
    }

    public function update(Request $request, Board $board): BoardResource
    {
        $this->authorize('update', $board);

        $data = BoardData::from($request);

        $board = $this->boardService->update($board, $data);

        return new BoardResource($board);
    }

    public function destroy(Board $board): Response
    {
        $this->authorize('delete', $board);

        $this->boardService->delete($board);

        return response()->noContent();
    }
}
