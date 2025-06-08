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
     *     path="/api/v1/articles",
     *     tags={"Articles"},
     *     summary="Get list of articles with filters",
     *     description="Retrieve a paginated list of news articles with optional filtering capabilities",
     *     operationId="getArticles",
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="Search by keyword in title/description",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         example="climate"
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category (use 'null' to find articles with no category)",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         example="politics"
     *     ),
     *     @OA\Parameter(
     *         name="source",
     *         in="query",
     *         description="Filter by news source (use 'null' to find articles with no source)",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         example="bbc-news"
     *     ),
     *     @OA\Parameter(
     *         name="author",
     *         in="query",
     *         description="Filter by author (use 'null' to find articles with no author)",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         example="John Doe"
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Filter by published date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date"),
     *         example="2025-06-08"
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer"),
     *         example=1
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of articles",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Article")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
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

        if ($request->has('category')) {
            if ($request->category === 'null' || $request->category === 'undefined') {
                $query->whereNull('category');
            } else if ($request->category === '') {
                $query->where(function($q) {
                    $q->whereNull('category')->orWhere('category', '');
                });
            } else {
                $query->where('category', $request->category);
            }
        }

        if ($request->has('source')) {
            if ($request->source === 'null' || $request->source === 'undefined') {
                $query->whereNull('source');
            } else {
                $query->where('source', $request->source);
            }
        }

        if ($request->has('author')) {
            if ($request->author === 'null' || $request->author === 'undefined') {
                $query->whereNull('author');
            } else {
                $query->where('author', $request->author);
            }
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
     *     path="/api/v1/articles/{id}",
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
