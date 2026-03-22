<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRecipeRequest;
use App\Services\ImageGenerationService;
use App\Services\RecipeService;
use App\Services\VideoGenerationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RecipeController extends Controller
{
    public function __construct(
        private readonly RecipeService          $recipeService,
        private readonly ImageGenerationService $imageService,
        private readonly VideoGenerationService $videoService,
    ) {}

    /**
     * @throws ConnectionException|RequestException
     */
    public function generate(GenerateRecipeRequest $request): JsonResponse
    {
        try {
            // Step 1: Generate recipe
            $recipe = $this->recipeService->generate(
                $request->validated('ingredients'),
                $request->validated('country')
            );

            // Step 2: Generate image per step
            $recipe['steps'] = $this->imageService->generateForSteps($recipe['steps']);

            // Step 3: Generate final cinematic video of finished dish
            $finalImageUrl       = collect($recipe['steps'])->last()['image_url'];
            $recipe['video_url'] = $this->videoService->generateFromImage(
                $finalImageUrl,
                "Beautifully plated {$recipe['dish_name']}, slow cinematic zoom in, steam rising, professional food photography"
            );

            return response()->json([
                'success' => true,
                'data'    => $recipe,
            ]);

        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
