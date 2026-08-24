<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketCategoryResource;
use App\Models\TicketCategory;
use Illuminate\Http\JsonResponse;

class TicketCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = TicketCategory::where('is_active', true)->orderBy('sort_order')->get();

        return response()->json(['data' => TicketCategoryResource::collection($categories)]);
    }
}
