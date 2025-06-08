<?php

namespace Tests\Unit\Models;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_expected_fields()
    {
        $article = Article::create([
            'title' => 'Test Title',
            'author' => 'Test Author',
            'description' => 'Test Description',
            'content' => 'Test Content',
            'published_at' => now(),
            'source' => 'Test Source',
            'category' => 'Test Category',
            'url' => 'https://example.com/test',
            'url_to_image' => 'https://example.com/test.jpg',
        ]);

        $this->assertEquals('Test Title', $article->title);
        $this->assertEquals('Test Author', $article->author);
        $this->assertEquals('Test Description', $article->description);
        $this->assertEquals('Test Content', $article->content);
        $this->assertEquals('Test Source', $article->source);
        $this->assertEquals('Test Category', $article->category);
        $this->assertEquals('https://example.com/test', $article->url);
        $this->assertEquals('https://example.com/test.jpg', $article->url_to_image);
    }

    /** @test */
    public function it_can_handle_null_fields()
    {
        $article = Article::create([
            'title' => 'Test Title',
            'author' => null,
            'description' => null,
            'content' => 'Test Content',
            'published_at' => now(),
            'source' => 'Test Source',
            'category' => null,
            'url' => 'https://example.com/test',
            'url_to_image' => null,
        ]);

        $this->assertEquals('Test Title', $article->title);
        $this->assertNull($article->author);
        $this->assertNull($article->description);
        $this->assertEquals('Test Content', $article->content);
        $this->assertEquals('Test Source', $article->source);
        $this->assertNull($article->category);
        $this->assertEquals('https://example.com/test', $article->url);
        $this->assertNull($article->url_to_image);
    }

    /** @test */
    public function it_allows_duplicate_urls()
    {
        // First article with a specific URL
        $article1 = Article::create([
            'title' => 'First Article',
            'author' => 'Author',
            'content' => 'Content',
            'published_at' => now(),
            'source' => 'Source',
            'url' => 'https://example.com/unique-test',
        ]);

        // Create another with same URL - should not throw an exception
        $article2 = Article::create([
            'title' => 'Second Article',
            'author' => 'Author',
            'content' => 'Content',
            'published_at' => now(),
            'source' => 'Source',
            'url' => 'https://example.com/unique-test',
        ]);
        
        // Assert both articles were created successfully
        $this->assertEquals('First Article', $article1->title);
        $this->assertEquals('Second Article', $article2->title);
        $this->assertEquals('https://example.com/unique-test', $article1->url);
        $this->assertEquals('https://example.com/unique-test', $article2->url);
    }
}
