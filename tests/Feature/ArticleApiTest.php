<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getting articles list.
     *
     * @return void
     */
    public function test_get_articles_list()
    {
        // Create some test articles
        for ($i = 0; $i < 5; $i++) {
            Article::create([
                'title' => "Test Article $i",
                'author' => "Author $i",
                'description' => "Description $i",
                'content' => "Content $i",
                'published_at' => now()->subHours($i),
                'source' => "Source $i",
                'category' => "Category $i",
                'url' => "https://example.com/article-$i"
            ]);
        }

        // Make API call to get articles
        $response = $this->getJson('/api/v1/articles');

        // Assert response is successful with correct structure
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'author',
                            'description',
                            'url',
                            'url_to_image',
                            'published_at',
                            'source',
                            'category',
                        ]
                    ],
                    'links',
                    'meta'
                ]);
    }

    /**
     * Test filtering articles by keyword.
     *
     * @return void
     */
    public function test_filter_articles_by_keyword()
    {
        // Create an article with a specific title
        Article::create([
            'title' => 'Special Test Article',
            'author' => 'Test Author',
            'description' => 'Test description',
            'content' => 'Test content',
            'published_at' => now(),
            'source' => 'Test Source',
            'category' => 'Test Category',
            'url' => 'https://example.com/test',
            'url_to_image' => 'https://example.com/test.jpg',
        ]);

        // Create some other articles
        for ($i = 0; $i < 3; $i++) {
            Article::create([
                'title' => "Regular Article $i",
                'author' => "Author $i",
                'description' => "Description $i",
                'content' => "Content $i",
                'published_at' => now()->subHours($i),
                'source' => "Source $i",
                'category' => "Category $i",
                'url' => "https://example.com/regular-article-$i"
            ]);
        }

        // Filter using the keyword
        $response = $this->getJson('/api/v1/articles?keyword=Special');

        // Assert that only the matching article is returned
        $response->assertStatus(200)
                ->assertJsonCount(1, 'data')
                ->assertJsonFragment(['title' => 'Special Test Article']);
    }

    /**
     * Test getting personalized feed requires authentication.
     *
     * @return void
     */
    public function test_user_feed_requires_auth()
    {
        // Try accessing feed without authentication
        $response = $this->getJson('/api/v1/user/feed');

        // Assert it fails with 401 Unauthorized
        $response->assertStatus(401);

        // Now create a user and authenticate
        $user = User::factory()->create();

        // Access the feed endpoint as authenticated user
        $response = $this->actingAs($user)->getJson('/api/v1/user/feed');

        // Should now succeed
        $response->assertStatus(200);
    }
}
