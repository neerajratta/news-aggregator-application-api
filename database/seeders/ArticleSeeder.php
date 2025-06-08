<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define some example sources
        $sources = ['BBC News', 'The Guardian', 'NewsAPI'];
        
        // Define some example categories
        $categories = ['Politics', 'Technology', 'Health', 'Business', 'Entertainment', 'Science'];
        
        // Create 25 sample articles
        for ($i = 0; $i < 25; $i++) {
            $sourceIndex = array_rand($sources);
            $categoryIndex = array_rand($categories);
            
            // 20% chance of having null values for testing
            $author = rand(1, 5) > 1 ? "Sample Author " . ($i % 5 + 1) : null;
            $category = rand(1, 5) > 1 ? $categories[$categoryIndex] : null;
            
            Article::create([
                'title' => 'Sample Article ' . ($i + 1),
                'author' => $author,
                'description' => 'This is a sample article description for seeding the database with test data.',
                'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam euismod lectus at magna vestibulum, at tempor nisi rhoncus. Quisque bibendum erat vel ante facilisis, in tincidunt urna aliquet.',
                'published_at' => Carbon::now()->subHours(rand(1, 72))->format('Y-m-d H:i:s'),
                'source' => $sources[$sourceIndex],
                'category' => $category,
                'url' => 'https://example.com/article-' . ($i + 1),
                'url_to_image' => 'https://via.placeholder.com/640x360.png?text=Article+' . ($i + 1),
            ]);
        }
    }
}
