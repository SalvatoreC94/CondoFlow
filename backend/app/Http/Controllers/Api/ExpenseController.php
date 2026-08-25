<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\AuditLog;
use App\Models\Condominium;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseController extends Controller
{
    public function index(Request $request, Condominium $condominium): AnonymousResourceCollection
    {
        $this->authorize('manageFinances', $condominium);

        $expenses = $condominium->expenses()
            ->with(['supplier', 'creator'])
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('expense_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('expense_date', '<=', $request->date('to')))
            ->orderByDesc('expense_date')
            ->paginate($request->integer('per_page', 20));

        return ExpenseResource::collection($expenses);
    }

    public function store(StoreExpenseRequest $request, Condominium $condominium): JsonResponse
    {
        $expense = $condominium->expenses()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        AuditLog::record('expense.created', $expense, [], $request->validated(), $condominium->id);

        return response()->json(['data' => new ExpenseResource($expense->load(['supplier', 'creator']))], 201);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        $old = $expense->only(array_keys($request->validated()));
        $expense->update($request->validated());

        AuditLog::record('expense.updated', $expense, $old, $request->validated(), $expense->condominium_id);

        return response()->json(['data' => new ExpenseResource($expense->fresh(['supplier', 'creator']))]);
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('delete', $expense);

        AuditLog::record('expense.deleted', $expense, [], [], $expense->condominium_id);

        $expense->delete();

        return response()->json(null, 204);
    }
}
