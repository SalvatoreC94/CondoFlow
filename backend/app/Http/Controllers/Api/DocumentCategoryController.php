<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentCategoryResource;
use App\Models\DocumentCategory;
use Illuminate\Http\JsonResponse;

class DocumentCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = DocumentCategory::orderBy('sort_order')->get();

        return response()->json(['data' => DocumentCategoryResource::collection($categories)]);
    }
}
