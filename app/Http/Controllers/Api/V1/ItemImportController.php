<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Items\ImportItemsRequest;
use App\Services\ItemImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[OA\Tag(name: 'Items')]
final class ItemImportController extends Controller
{
    public function __construct(private readonly ItemImportService $service) {}

    #[OA\Post(path: '/items/imports/preview', summary: 'Preview an item import file', security: [['sanctum' => []]], tags: ['Items'],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(required: ['file'], properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])
        )),
        responses: [new OA\Response(response: 200, description: 'Preview data')]
    )]
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

    #[OA\Post(path: '/items/imports', summary: 'Apply an item import', security: [['sanctum' => []]], tags: ['Items'],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(required: ['file'], properties: [
                new OA\Property(property: 'file', type: 'string', format: 'binary'),
                new OA\Property(property: 'category_id', type: 'integer'),
            ])
        )),
        responses: [new OA\Response(response: 201, description: 'Import result')]
    )]
    public function import(ImportItemsRequest $request): JsonResponse
    {
        $path = $request->file('file')->store('imports/items', 'local');
        $fullPath = storage_path("app/{$path}");
        try {
            $preview = $this->service->getPreviewData($fullPath);
            $result = $this->service->applyJsonImport($preview['allItems'], $request->integer('category_id') ?: null);
            return response()->json(['data' => $result], 201);
        } finally {
            @unlink($fullPath);
        }
    }

    #[OA\Get(path: '/items/import-template', summary: 'Download item import Excel template', security: [['sanctum' => []]], tags: ['Items'],
        responses: [
            new OA\Response(response: 200, description: 'Excel file download'),
            new OA\Response(response: 404, description: 'Template not found'),
        ]
    )]
    public function downloadTemplate(): BinaryFileResponse
    {
        $this->authorize('create', \App\Models\Item::class);
        $path = storage_path('app/templates/item_import_template.xlsx');
        if (! file_exists($path)) { abort(404, 'Import template not found.'); }
        return response()->download($path, 'item_import_template.xlsx');
    }
}
