<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use App\Http\Resources\ArticleResource;

/**
 * @OA\Tag(
 *     name="Articles",
 *     description="API Endpoints for Managing News Articles"
 * )
 */
class ArticleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/articles",
     *     tags={"Articles"},
     *     summary="Get list of articles with filters",
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="Search by keyword in title/description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="source",
     *         in="query",
     *         description="Filter by news source",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Filter by published date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of articles",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Article"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Article::query();

        if ($keyword = $request->keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%$keyword%")
                  ->orWhere('description', 'like', "%$keyword%");
            });
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->source) {
            $query->where('source', $request->source);
        }

        if ($request->date) {
            $query->whereDate('published_at', $request->date);
        }

        return ArticleResource::collection(
            $query->orderBy('published_at', 'desc')->paginate(10)
        );
    }

    /**
     * @OA\Get(
     *     path="/api/articles/{id}",
     *     tags={"Articles"},
     *     summary="Get a single article by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Article ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article details",
     *         @OA\JsonContent(ref="#/components/schemas/Article")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Article not found"
     *     )
     * )
     */
    public function show($id)
    {
        $article = Article::findOrFail($id);
        return new ArticleResource($article);
    }
}
