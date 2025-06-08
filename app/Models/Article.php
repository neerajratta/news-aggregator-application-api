<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Article",
 *     type="object",
 *     title="Article",
 *     required={"id", "title"},
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="content", type="string"),
 *     @OA\Property(property="author", type="string"),
 *     @OA\Property(property="source", type="string"),
 *     @OA\Property(property="category", type="string"),
 *     @OA\Property(property="url", type="string", format="uri"),
 *     @OA\Property(property="url_to_image", type="string", format="uri"),
 *     @OA\Property(property="published_at", type="string", format="date-time")
 * )
 */
class Article extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title', 'description', 'content', 'author',
        'source', 'category', 'url', 'url_to_image', 'published_at'
    ];
}

