<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Items\ImportItemsRequest;
use App\Services\ItemImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ItemImportController extends Controller
{
    public function __construct(private readonly ItemImportService $service) {}

    public function preview(ImportItemsRequest $request): JsonResponse
    {
        $path = $request->file('file')->store('imports/items', 'local');
        $fullPath = storage_path("app/{$path}");

        try {
            $preview = $this->service->getPreviewData($fullPath);
            return response()->json(['data' => $preview]);
        } finally {
            @unlink($fullPath);
        }
    }

    public function import(ImportItemsRequest $request): JsonResponse
    {
        $path = $request->file('file')->store('imports/items', 'local');
        $fullPath = storage_path("app/{$path}");

        try {
            $preview = $this->service->getPreviewData($fullPath);
            $result = $this->service->applyJsonImport(
                $preview['allItems'],
                $request->integer('category_id') ?: null
            );

            return response()->json(['data' => $result], 201);
        } finally {
            @unlink($fullPath);
        }
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $this->authorize('create', \App\Models\Item::class);
        $path = storage_path('app/templates/item_import_template.xlsx');

        if (! file_exists($path)) {
            abort(404, 'Import template not found.');
        }

        return response()->download($path, 'item_import_template.xlsx');
    }
}
