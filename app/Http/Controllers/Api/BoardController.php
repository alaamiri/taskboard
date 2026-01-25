<?php

namespace App\Http\Controllers\Api;

use App\Data\Board\BoardData;
use App\Http\Controllers\Controller;
use App\Http\Resources\BoardResource;
use App\Models\Board;
use App\Services\Model\BoardService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @group Boards
 *
 * APIs for managing Kanban boards
 */
class BoardController extends Controller
{
    public function __construct(
        private readonly BoardService $boardService
    ) {}

    /**
     * List all boards
     *
     * Returns all boards accessible to the authenticated user.
     * Admin users see all boards, viewers see only their own.
     *
     * @authenticated
     *
     * @response 200 scenario="success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Project Alpha",
     *       "description": "Main project board",
     *       "user_id": 1,
     *       "created_at": "2026-01-25T10:00:00.000000Z",
     *       "updated_at": "2026-01-25T10:00:00.000000Z",
     *       "columns_count": 3
     *     }
     *   ]
     * }
     * @response 401 scenario="unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $boards = $this->boardService->getAllForUser($request->user());

        return BoardResource::collection($boards);
    }

    /**
     * Create a board
     *
     * Creates a new board with 3 default columns: "À faire", "En cours", "Terminé".
     *
     * @authenticated
     *
     * @bodyParam name string required The name of the board. Example: Project Alpha
     * @bodyParam description string optional The board description. Example: Main project board
     *
     * @response 201 scenario="created" {
     *   "data": {
     *     "id": 1,
     *     "name": "Project Alpha",
     *     "description": "Main project board",
     *     "user_id": 1,
     *     "created_at": "2026-01-25T10:00:00.000000Z",
     *     "updated_at": "2026-01-25T10:00:00.000000Z"
     *   }
     * }
     * @response 422 scenario="validation error" {"message": "The name field is required.", "errors": {"name": ["The name field is required."]}}
     */
    public function store(Request $request): BoardResource
    {
        $data = BoardData::from($request);

        $board = $this->boardService->create($request->user(), $data);

        return new BoardResource($board);
    }

    /**
     * Get a board
     *
     * Returns a single board with its columns and cards.
     *
     * @authenticated
     *
     * @urlParam board integer required The board ID. Example: 1
     *
     * @response 200 scenario="success" {
     *   "data": {
     *     "id": 1,
     *     "name": "Project Alpha",
     *     "description": "Main project board",
     *     "user_id": 1,
     *     "created_at": "2026-01-25T10:00:00.000000Z",
     *     "updated_at": "2026-01-25T10:00:00.000000Z",
     *     "columns": [
     *       {
     *         "id": 1,
     *         "name": "To Do",
     *         "position": 0,
     *         "cards": []
     *       }
     *     ]
     *   }
     * }
     * @response 403 scenario="forbidden" {"message": "This action is unauthorized."}
     * @response 404 scenario="not found" {"message": "Board not found."}
     */
    public function show(Board $board): BoardResource
    {
        $this->authorize('view', $board);

        $board = $this->boardService->getWithRelations($board);

        return new BoardResource($board);
    }

    /**
     * Update a board
     *
     * Updates the board name and/or description.
     *
     * @authenticated
     *
     * @urlParam board integer required The board ID. Example: 1
     * @bodyParam name string The new name for the board. Example: Updated Project Name
     * @bodyParam description string The new description. Example: Updated description
     *
     * @response 200 scenario="success" {
     *   "data": {
     *     "id": 1,
     *     "name": "Updated Project Name",
     *     "description": "Updated description",
     *     "user_id": 1,
     *     "created_at": "2026-01-25T10:00:00.000000Z",
     *     "updated_at": "2026-01-25T11:00:00.000000Z"
     *   }
     * }
     * @response 403 scenario="forbidden" {"message": "This action is unauthorized."}
     */
    public function update(Request $request, Board $board): BoardResource
    {
        $this->authorize('update', $board);

        $data = BoardData::from($request);

        $board = $this->boardService->update($board, $data);

        return new BoardResource($board);
    }

    /**
     * Delete a board
     *
     * Deletes the board and all its columns and cards. Admin only.
     *
     * @authenticated
     *
     * @urlParam board integer required The board ID. Example: 1
     *
     * @response 204 scenario="deleted"
     * @response 403 scenario="forbidden" {"message": "This action is unauthorized."}
     */
    public function destroy(Board $board): Response
    {
        $this->authorize('delete', $board);

        $this->boardService->delete($board);

        return response()->noContent();
    }
}
