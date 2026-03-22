<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateVideoRequest;
use App\Services\VideoPipelineService;
use Illuminate\Http\JsonResponse;

class VideoController extends Controller
{
    /**
     * @param VideoPipelineService $videoPipelineService
     */
    public function __construct(private readonly VideoPipelineService $videoPipelineService)
    {
    }

    /**
     * @param GenerateVideoRequest $request
     * @return JsonResponse
     */
    public function generate(GenerateVideoRequest $request): JsonResponse
    {
        $result = $this->videoPipelineService->generate($request->validated('product_description'));

        return response()->json($result);
    }
}
