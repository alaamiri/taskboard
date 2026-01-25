<?php

namespace App\Http\Controllers\Api;

use App\Data\Column\ColumnData;
use App\Http\Controllers\Controller;
use App\Http\Resources\ColumnResource;
use App\Models\Board;
use App\Models\Column;
use App\Services\Model\ColumnService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group Columns
 *
 * APIs for managing columns within boards
 */
class ColumnController extends Controller
{
    public function __construct(
        private readonly ColumnService $columnService
    ) {}

    /**
     * Get a column
     *
     * Returns a single column with its cards.
     *
     * @authenticated
     *
     * @urlParam column integer required The column ID. Example: 1
     *
     * @response 200 scenario="success" {
     *   "data": {
     *     "id": 1,
     *     "name": "To Do",
     *     "position": 0,
     *     "board_id": 1,
     *     "created_at": "2026-01-25T10:00:00.000000Z",
     *     "updated_at": "2026-01-25T10:00:00.000000Z",
     *     "cards": [
     *       {
     *         "id": 1,
     *         "title": "Task 1",
     *         "description": "First task",
     *         "position": 0
     *       }
     *     ]
     *   }
     * }
     * @response 403 scenario="forbidden" {"message": "This action is unauthorized."}
     * @response 404 scenario="not found" {"message": "Column not found."}
     */
    public function show(Column $column): ColumnResource
    {
        $this->authorize('view', $column);

        $column->load('cards');

        return new ColumnResource($column);
    }

    /**
     * Create a column
     *
     * Creates a new column in the specified board. Position is auto-assigned.
     *
     * @authenticated
     *
     * @urlParam board integer required The board ID. Example: 1
     * @bodyParam name string required The name of the column. Example: In Progress
     *
     * @response 201 scenario="created" {
     *   "data": {
     *     "id": 2,
     *     "name": "In Progress",
     *     "position": 3,
     *     "board_id": 1,
     *     "created_at": "2026-01-25T10:00:00.000000Z",
     *     "updated_at": "2026-01-25T10:00:00.000000Z"
     *   }
     * }
     * @response 403 scenario="forbidden" {"message": "This action is unauthorized."}
     * @response 422 scenario="validation error" {"message": "The name field is required.", "errors": {"name": ["The name field is required."]}}
     */
    public function store(Request $request, Board $board): ColumnResource
    {
        $this->authorize('create', [Column::class, $board]);

        $data = ColumnData::from($request);

        $column = $this->columnService->create($board, $data);

        return new ColumnResource($column);
    }

    /**
     * Update a column
     *
     * Updates the column name and/or position.
     *
     * @authenticated
     *
     * @urlParam column integer required The column ID. Example: 1
     * @bodyParam name string The new name for the column. Example: Done
     * @bodyParam position integer The new position (0-based index). Example: 2
     *
     * @response 200 scenario="success" {
     *   "data": {
     *     "id": 1,
     *     "name": "Done",
     *     "position": 2,
     *     "board_id": 1,
     *     "created_at": "2026-01-25T10:00:00.000000Z",
     *     "updated_at": "2026-01-25T11:00:00.000000Z"
     *   }
     * }
     * @response 403 scenario="forbidden" {"message": "This action is unauthorized."}
     */
    public function update(Request $request, Column $column): ColumnResource
    {
        $this->authorize('update', $column);

        $data = ColumnData::from($request);

        $column = $this->columnService->update($column, $data);

        return new ColumnResource($column);
    }

    /**
     * Delete a column
     *
     * Deletes the column and all its cards. Admin only.
     *
     * @authenticated
     *
     * @urlParam column integer required The column ID. Example: 1
     *
     * @response 204 scenario="deleted"
     * @response 403 scenario="forbidden" {"message": "This action is unauthorized."}
     */
    public function destroy(Column $column): Response
    {
        $this->authorize('delete', $column);

        $this->columnService->delete($column);

        return response()->noContent();
    }
}
