<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Builder;

class UserPreferenceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/user/preferences",
     *     summary="Get user preferences",
     *     tags={"User Preferences"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="User preferences retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="sources", 
     *                 type="array", 
     *                 description="List of preferred news sources",
     *                 example={"BBC News", "The Guardian", "NewsAPI"},
     *                 @OA\Items(type="string")
     *             ),
     *             @OA\Property(
     *                 property="categories", 
     *                 type="array", 
     *                 description="List of preferred news categories",
     *                 example={"Politics", "Technology", "Health"},
     *                 @OA\Items(type="string")
     *             ),
     *             @OA\Property(
     *                 property="authors", 
     *                 type="array", 
     *                 description="List of preferred authors to follow",
     *                 example={"John Smith", "Jane Doe"},
     *                 @OA\Items(type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Preferences not found"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $preferences = $user->preferences;
        
        if (!$preferences) {
            // Create default preferences if not found
            $preferences = new UserPreference([
                'sources' => [],
                'categories' => [],
                'authors' => []
            ]);
            $user->preferences()->save($preferences);
        }
        
        return response()->json([
            'sources' => $preferences->sources ?? [],
            'categories' => $preferences->categories ?? [],
            'authors' => $preferences->authors ?? []
        ]);
    }
    
    /**
     * @OA\Put(
     *     path="/api/v1/user/preferences/update",
     *     summary="Update user preferences",
     *     tags={"User Preferences"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\RequestBody(
     *         required=true,
     *         description="User preference data",
     *         @OA\JsonContent(
     *             required={"sources", "categories", "authors"},
     *             @OA\Property(
     *                 property="sources", 
     *                 type="array", 
     *                 description="List of preferred news sources",
     *                 example={"CNN", "BBC", "The New York Times"},
     *                 @OA\Items(type="string")
     *             ),
     *             @OA\Property(
     *                 property="categories", 
     *                 type="array", 
     *                 description="List of preferred news categories",
     *                 example={"Politics", "Technology", "Health"},
     *                 @OA\Items(type="string")
     *             ),
     *             @OA\Property(
     *                 property="authors", 
     *                 type="array", 
     *                 description="List of preferred authors to follow",
     *                 example={"John Smith", "Jane Doe"},
     *                 @OA\Items(type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User preferences updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Preferences updated successfully"),
     *             @OA\Property(property="preferences", type="object",
     *                 @OA\Property(
     *                     property="sources", 
     *                     type="array", 
     *                     description="List of preferred news sources",
     *                     example={"CNN", "BBC", "The New York Times"},
     *                     @OA\Items(type="string")
     *                 ),
     *                 @OA\Property(
     *                     property="categories", 
     *                     type="array", 
     *                     description="List of preferred news categories",
     *                     example={"Politics", "Technology", "Health"},
     *                     @OA\Items(type="string")
     *                 ),
     *                 @OA\Property(
     *                     property="authors", 
     *                     type="array", 
     *                     description="List of preferred authors to follow",
     *                     example={"John Smith", "Jane Doe"},
     *                     @OA\Items(type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'sources' => 'nullable|array',
            'sources.*' => 'string',
            'categories' => 'nullable|array',
            'categories.*' => 'string',
            'authors' => 'nullable|array',
            'authors.*' => 'string',
        ]);
        
        $user = $request->user();
        $preferences = $user->preferences;
        
        if (!$preferences) {
            $preferences = new UserPreference();
            $user->preferences()->save($preferences);
        }
        
        // Update only the fields that are provided in the request
        if ($request->has('sources')) {
            $preferences->sources = $validated['sources'];
        }
        
        if ($request->has('categories')) {
            $preferences->categories = $validated['categories'];
        }
        
        if ($request->has('authors')) {
            $preferences->authors = $validated['authors'];
        }
        
        $preferences->save();
        
        return response()->json([
            'message' => 'Preferences updated successfully',
            'preferences' => [
                'sources' => $preferences->sources,
                'categories' => $preferences->categories,
                'authors' => $preferences->authors,
            ]
        ]);
    }
    
    /**
     * @OA\Post(
     *     path="/api/v1/user/preferences/reset",
     *     summary="Reset user preferences to default",
     *     tags={"User Preferences"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="User preferences reset successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Preferences reset successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function reset(Request $request)
    {
        $user = $request->user();
        $preferences = $user->preferences;
        
        if ($preferences) {
            // Reset to empty arrays
            $preferences->update([
                'sources' => [],
                'categories' => [],
                'authors' => []
            ]);
        } else {
            // Create new preferences with empty arrays if not exists
            $preferences = new UserPreference([
                'sources' => [],
                'categories' => [],
                'authors' => []
            ]);
            $user->preferences()->save($preferences);
        }
        
        return response()->json([
            'message' => 'Preferences reset successfully'
        ]);
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/user/feed",
     *     summary="Get personalized news feed based on user preferences",
     *     tags={"User Feed"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Personalized articles retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="articles", 
     *                 type="array", 
     *                 description="List of personalized articles based on preferences",
     *                 @OA\Items(ref="#/components/schemas/Article")
     *             ),
     *             @OA\Property(
     *                 property="pagination", 
     *                 type="object",
     *                 description="Pagination information",
     *                 @OA\Property(property="total", type="integer", example=45),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=3),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="to", type="integer", example=15)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function feed(Request $request)
    {
        $user = $request->user();
        $preferences = $user->preferences;
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 15);
        
        // Start with a base query
        $query = Article::query();
        
        // Apply filters based on user preferences if they exist
        if ($preferences) {
            // Filter by sources if defined
            if (!empty($preferences->sources)) {
                $query->where(function (Builder $query) use ($preferences) {
                    foreach ($preferences->sources as $source) {
                        $query->orWhere('source', 'like', "%{$source}%");
                    }
                });
            }
            
            // Filter by categories if defined
            if (!empty($preferences->categories)) {
                $query->where(function (Builder $query) use ($preferences) {
                    foreach ($preferences->categories as $category) {
                        $query->orWhere('category', 'like', "%{$category}%");
                    }
                });
            }
            
            // Filter by authors if defined
            if (!empty($preferences->authors)) {
                $query->where(function (Builder $query) use ($preferences) {
                    foreach ($preferences->authors as $author) {
                        $query->orWhere('author', 'like', "%{$author}%");
                    }
                });
            }
        }
        
        // Order by most recent articles first
        $query->orderBy('published_at', 'desc');
        
        // Paginate the results
        $articles = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'articles' => $articles->items(),
            'pagination' => [
                'total' => $articles->total(),
                'per_page' => $articles->perPage(),
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'from' => $articles->firstItem(),
                'to' => $articles->lastItem(),
            ]
        ]);
    }
}
