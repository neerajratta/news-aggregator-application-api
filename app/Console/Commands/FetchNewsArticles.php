<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Article;
use Carbon\Carbon;

class FetchNewsArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch news articles from external APIs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->fetchFromNewsApi();
        $this->fetchFromGuardian();
        $this->fetchFromBBC();

        $this->info('News articles fetched successfully.');
    }

    protected function fetchFromNewsApi()
    {
        $articles = $this->fetchNewsApiArticles(); // general top-headlines
        $this->saveArticles($articles ?? [], 'NewsAPI');
    }

    protected function fetchFromGuardian(): void
    {
        $response = Http::get('https://content.guardianapis.com/search', [
            'api-key' => config('services.guardian.key'),
            'show-fields' => 'all',
            'page-size' => 50,
        ]);

        if (!$response->successful()) return;

        $articles = [];

        foreach ($response->json('response.results') as $item) {
            $articles[] = [
                'title' => $item['webTitle'],
                'author' => $item['fields']['byline'] ?? null,
                'description' => $item['fields']['trailText'] ?? null,
                'content' => $item['fields']['bodyText'] ?? null,
                'published_at' => Carbon::parse($item['webPublicationDate'] ?? now()),
                'source' => 'The Guardian',
                'category' => $item['sectionName'] ?? null,
                'url' => $item['webUrl'],
                'url_to_image' => $item['fields']['thumbnail'] ?? null,
            ];
        }

        // Now pass full list
        $this->saveArticles($articles, 'The Guardian');
    }


    protected function fetchFromBBC()
    {
        $articles = $this->fetchNewsApiArticles('bbc-news');
        $this->saveArticles($articles ?? [], 'BBC News');
    }

    protected function fetchNewsApiArticles($source = null)
    {
        $query = [
            'apiKey' => config('services.newsapi.key'),
            'language' => 'en',
            'pageSize' => 50,
        ];

        if ($source) {
            $query['sources'] = $source;
        }

        $response = Http::get('https://newsapi.org/v2/top-headlines', $query);

        return $response->successful() ? $response->json('articles') : [];
    }

    protected function formatArticleData(array $article, string $sourceName): array
    {
        return [
            'title' => $article['title'],
            'author' => $article['author'] ?? null,
            'description' => $article['description'] ?? null,
            'content' => $article['content'] ?? null,
            'published_at' => isset($article['publishedAt'])
                ? Carbon::parse($article['publishedAt'])->format('Y-m-d H:i:s')
                : now(),
            'source' => $sourceName,
            'category' => null,
            'url' => $article['url'],
            'url_to_image' => $article['urlToImage'] ?? null,
        ];
    }

    protected function saveArticles(array $articles, string $sourceName)
    {
        if (empty($articles)) {
            \Log::info("No articles to save for source: {$sourceName}");
            return;
        }
        foreach ($articles as $index => $article) {

            // Ensure each article is an array
            if (!is_array($article)) {
                \Log::warning("Skipping invalid article at index {$index}. Expected array, got " . gettype($article));
                continue;
            }

            // Ensure required field 'url' exists
            if (!isset($article['url'])) {
                \Log::warning("Skipping article at index {$index}. Missing 'url' key.");
                continue;
            }

            Article::updateOrCreate(
                ['url' => $article['url']],
                $this->formatArticleData($article, $sourceName)
            );
        }
    }


}
