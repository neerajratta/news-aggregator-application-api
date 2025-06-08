<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserPreference;
use App\Http\Resources\ArticleResource;
use App\Models\Article;


/**
 * @OA\Tag(name="User Preferences", description="Manage user’s news preferences")
 */
class UserPreferenceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/user/preferences",
     *     tags={"User Preferences"},
     *     summary="Get the logged-in user's preferences",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User preferences returned",
     *         @OA\JsonContent(
     *             @OA\Property(property="sources", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="categories", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="authors", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function show()
    {
        $prefs = Auth::user()->preferences;
        return response()->json($prefs);
    }

    /**
     * @OA\Post(
     *     path="/api/user/preferences",
     *     tags={"User Preferences"},
     *     summary="Set or update user preferences",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="sources", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="categories", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="authors", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preferences saved",
     *         @OA\JsonContent(@OA\Property(property="message", type="string"))
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'sources' => 'nullable|array',
            'categories' => 'nullable|array',
            'authors' => 'nullable|array',
        ]);

        $user = Auth::user();

        $prefs = $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'sources' => $request->sources,
                'categories' => $request->categories,
                'authors' => $request->authors,
            ]
        );

        return response()->json([
            'message' => 'Preferences saved successfully',
            'data' => $prefs,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/user/feed",
     *     tags={"User Preferences"},
     *     summary="Get personalized news feed based on user preferences",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of personalized articles",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Article"))
     *         )
     *     )
     * )
     */
    public function feed(Request $request)
    {
        $user = Auth::user();
        $prefs = $user->preferences;

        if (!$prefs) {
            return response()->json(['message' => 'No preferences set'], 200);
        }

        $query = Article::query();

        if (!empty($prefs->sources)) {
            $query->whereIn('source', $prefs->sources);
        }

        if (!empty($prefs->categories)) {
            $query->whereIn('category', $prefs->categories);
        }

        if (!empty($prefs->authors)) {
            $query->whereIn('author', $prefs->authors);
        }

        $articles = $query->orderBy('published_at', 'desc')->paginate(10);

        return ArticleResource::collection($articles);
    }

}
