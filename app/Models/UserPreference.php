<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="UserPreference",
 *     type="object",
 *     title="UserPreference",
 *     description="User news preferences",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="user_id", type="integer"),
 *     @OA\Property(
 *         property="sources", 
 *         type="array", 
 *         description="List of preferred news sources",
 *         @OA\Items(type="string")
 *     ),
 *     @OA\Property(
 *         property="categories", 
 *         type="array", 
 *         description="List of preferred news categories",
 *         @OA\Items(type="string")
 *     ),
 *     @OA\Property(
 *         property="authors", 
 *         type="array", 
 *         description="List of preferred news authors",
 *         @OA\Items(type="string")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class UserPreference extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'sources',
        'categories',
        'authors',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sources' => 'array',
        'categories' => 'array',
        'authors' => 'array',
    ];
    
    /**
     * Get the user that owns the preferences.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
