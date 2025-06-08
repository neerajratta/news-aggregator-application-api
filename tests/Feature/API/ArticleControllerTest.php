<?php

namespace Tests\Feature\API;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_articles_with_pagination()
    {
        // Create 15 articles
        Article::create([
            'title' => 'Test Article 1',
            'author' => 'Author 1',
            'description' => 'Description 1',
            'content' => 'Content 1',
            'published_at' => now()->subHours(1),
            'source' => 'Source 1',
            'category' => 'Category 1',
            'url' => 'https://example.com/1',
        ]);

        // Create more articles
        for ($i = 2; $i <= 15; $i++) {
            Article::create([
                'title' => "Test Article $i",
                'author' => "Author $i",
                'description' => "Description $i",
                'content' => "Content $i",
                'published_at' => now()->subHours($i),
                'source' => "Source " . ($i % 3 + 1),
                'category' => "Category " . ($i % 4 + 1),
                'url' => "https://example.com/$i",
            ]);
        }

        $response = $this->getJson('/api/v1/articles');

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
            ])
            ->assertJsonCount(10, 'data'); // Default pagination is 10 items

        // Verify second page
        $response = $this->getJson('/api/v1/articles?page=2');
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function it_shows_a_specific_article()
    {
        $article = Article::create([
            'title' => 'Test Article',
            'author' => 'Test Author',
            'description' => 'Test Description',
            'content' => 'Test Content',
            'published_at' => now(),
            'source' => 'Test Source',
            'category' => 'Test Category',
            'url' => 'https://example.com/test',
        ]);

        $response = $this->getJson("/api/v1/articles/{$article->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $article->id,
                    'title' => 'Test Article',
                    'author' => 'Test Author',
                    'description' => 'Test Description',
                    'source' => 'Test Source',
                    'category' => 'Test Category',
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_article()
    {
        $response = $this->getJson('/api/v1/articles/999');
        
        $response->assertStatus(404);
    }

    /** @test */
    public function it_filters_articles_by_keyword()
    {
        // Create articles with specific keywords in title and description
        Article::create([
            'title' => 'Apple launches new iPhone',
            'description' => 'New model released today',
            'author' => 'Tech Writer',
            'content' => 'Content',
            'published_at' => now(),
            'source' => 'Tech News',
            'category' => 'Technology',
            'url' => 'https://example.com/apple1',
        ]);

        Article::create([
            'title' => 'Samsung announces Galaxy phone',
            'description' => 'Competitor to Apple iPhone',
            'author' => 'Tech Writer',
            'content' => 'Content',
            'published_at' => now(),
            'source' => 'Tech News',
            'category' => 'Technology',
            'url' => 'https://example.com/samsung1',
        ]);

        Article::create([
            'title' => 'New Android update',
            'description' => 'Google releases Android 14',
            'author' => 'Tech Writer',
            'content' => 'Content',
            'published_at' => now(),
            'source' => 'Tech News',
            'category' => 'Technology',
            'url' => 'https://example.com/android1',
        ]);

        // Test keyword in title
        $response = $this->getJson('/api/v1/articles?keyword=Apple');
        $response->assertStatus(200)
            // Check that we have at least one article with Apple in the title
            ->assertJsonPath('data.0.title', 'Apple launches new iPhone');

        // Test keyword in description
        $response = $this->getJson('/api/v1/articles?keyword=Competitor');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Samsung announces Galaxy phone');

        // Test no results
        $response = $this->getJson('/api/v1/articles?keyword=NotFound');
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function it_filters_articles_by_category()
    {
        // Create articles with different categories
        Article::create([
            'title' => 'Politics News 1',
            'author' => 'Author',
            'content' => 'Content',
            'published_at' => now(),
            'source' => 'News Source',
            'category' => 'Politics',
            'url' => 'https://example.com/politics1',
        ]);

        Article::create([
            'title' => 'Politics News 2',
            'author' => 'Author',
            'content' => 'Content',
            'published_at' => now(),
            'source' => 'News Source',
            'category' => 'Politics',
            'url' => 'https://example.com/politics2',
        ]);

        Article::create([
            'title' => 'Sports News 1',
            'author' => 'Author',
            'content' => 'Content',
            'published_at' => now(),
            'source' => 'News Source',
            'category' => 'Sports',
            'url' => 'https://example.com/sports1',
        ]);

        Article::create([
            'title' => 'No Category News',
            'author' => 'Author',
            'content' => 'Content',
            'published_at' => now(),
            'source' => 'News Source',
            'category' => null,
            'url' => 'https://example.com/nocategory',
        ]);

        // Test filter by category
        $response = $this->getJson('/api/v1/articles?category=Politics');
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.category', 'Politics')
            ->assertJsonPath('data.1.category', 'Politics');

        // Test filter by null category
        $response = $this->getJson('/api/v1/articles?category=null');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'No Category News')
            ->assertJsonPath('data.0.category', null);
    }

    /** @test */
    public function it_filters_articles_by_source()
    {
        // Create articles with different sources
        Article::create([
            'title' => 'BBC News 1',
            'author' => 'Author',
            'content' => 'Content',
            'published_at' => now(),
            'source' => 'BBC News',
            'category' => 'General',
            'url' => 'https://example.com/bbc1',
        ]);

        Article::create([
            'title' => 'BBC News 2',
            'author' => 'Author',
            'content' => 'Content',
            'published_at' => now(),
            'source' => 'BBC News',
            'category' => 'General',
            'url' => 'https://example.com/bbc2',
        ]);

        Article::create([
            'title' => 'Guardian News 1',
            'author' => 'Author',
            'content' => 'Content',
            'published_at' => now(),
            'source' => 'The Guardian',
            'category' => 'General',
            'url' => 'https://example.com/guardian1',
        ]);

        // Test filter by source
        $response = $this->getJson('/api/v1/articles?source=BBC+News');
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.source', 'BBC News')
            ->assertJsonPath('data.1.source', 'BBC News');

        // Test filter by source - single result
        $response = $this->getJson('/api/v1/articles?source=The+Guardian');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.source', 'The Guardian');
    }

    /** @test */
    public function it_filters_articles_by_date()
    {
        // Create articles with specific dates
        Article::create([
            'title' => 'Yesterday News',
            'author' => 'Author',
            'content' => 'Content',
            'published_at' => Carbon::yesterday(),
            'source' => 'News Source',
            'category' => 'General',
            'url' => 'https://example.com/yesterday',
        ]);

        Article::create([
            'title' => 'Today News 1',
            'author' => 'Author',
            'content' => 'Content',
            'published_at' => Carbon::today(),
            'source' => 'News Source',
            'category' => 'General',
            'url' => 'https://example.com/today1',
        ]);

        Article::create([
            'title' => 'Today News 2',
            'author' => 'Author',
            'content' => 'Content',
            'published_at' => Carbon::today()->addHours(2),
            'source' => 'News Source',
            'category' => 'General',
            'url' => 'https://example.com/today2',
        ]);

        // Test filter by today's date
        $today = Carbon::today()->format('Y-m-d');
        $response = $this->getJson("/api/v1/articles?date=$today");
        
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'Today News 2')
            ->assertJsonPath('data.1.title', 'Today News 1');

        // Test filter by yesterday's date
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $response = $this->getJson("/api/v1/articles?date=$yesterday");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Yesterday News');
    }

    /** @test */
    public function it_combines_multiple_filters()
    {
        // Create diverse test articles
        Article::create([
            'title' => 'Tech News from BBC',
            'author' => 'Tech Writer',
            'content' => 'Content about tech',
            'published_at' => Carbon::today(),
            'source' => 'BBC News',
            'category' => 'Technology',
            'url' => 'https://example.com/tech-bbc',
        ]);

        Article::create([
            'title' => 'Politics News from BBC',
            'author' => 'Politics Writer',
            'content' => 'Content about politics',
            'published_at' => Carbon::today(),
            'source' => 'BBC News',
            'category' => 'Politics',
            'url' => 'https://example.com/politics-bbc',
        ]);

        Article::create([
            'title' => 'Tech News from Guardian',
            'author' => 'Tech Writer',
            'content' => 'Content about tech',
            'published_at' => Carbon::yesterday(),
            'source' => 'The Guardian',
            'category' => 'Technology',
            'url' => 'https://example.com/tech-guardian',
        ]);

        // Test combining source and category filters
        $response = $this->getJson('/api/v1/articles?source=BBC+News&category=Technology');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Tech News from BBC')
            ->assertJsonPath('data.0.source', 'BBC News')
            ->assertJsonPath('data.0.category', 'Technology');

        // Test combining date and category filters
        $today = Carbon::today()->format('Y-m-d');
        $response = $this->getJson("/api/v1/articles?date=$today&category=Politics");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Politics News from BBC')
            ->assertJsonPath('data.0.category', 'Politics');

        // Test combining keyword and source filters
        $response = $this->getJson('/api/v1/articles?keyword=Tech&source=The+Guardian');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Tech News from Guardian')
            ->assertJsonPath('data.0.source', 'The Guardian');
    }
}
