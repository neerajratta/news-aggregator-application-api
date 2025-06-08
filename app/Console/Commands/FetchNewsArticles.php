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
        try {
            $this->info('Starting news articles fetch...');
            
            $newsApiCount = $this->fetchFromNewsApi();
            $guardianCount = $this->fetchFromGuardian();
            $bbcCount = $this->fetchFromBBC();
            
            $totalCount = $newsApiCount + $guardianCount + $bbcCount;
            
            $this->info("News articles fetched successfully. Total: {$totalCount} articles (NewsAPI: {$newsApiCount}, Guardian: {$guardianCount}, BBC: {$bbcCount})");
            return 0;
        } catch (\Exception $e) {
            $this->error('Error fetching news articles: ' . $e->getMessage());
            \Log::error('News fetch failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    protected function fetchFromNewsApi()
    {
        $this->info('Fetching from NewsAPI...');
        try {
            $articles = $this->fetchNewsApiArticles(); // general top-headlines
            $count = count($articles);
            $this->saveArticles($articles ?? [], 'NewsAPI');
            $this->info("NewsAPI fetch complete: {$count} articles");
            return $count;
        } catch (\Exception $e) {
            $this->warn('Error fetching from NewsAPI: ' . $e->getMessage());
            \Log::warning('NewsAPI fetch failed: ' . $e->getMessage());
            return 0;
        }
    }

    protected function fetchFromGuardian(): int
    {
        $this->info('Fetching from The Guardian...');
        try {
            $guardianKey = config('services.guardian.key');
            
            if (empty($guardianKey)) {
                $this->warn('Missing Guardian API key in configuration');
                \Log::warning('Guardian API fetch skipped: Missing API key');
                return 0;
            }
            
            $response = Http::get('https://content.guardianapis.com/search', [
                'api-key' => $guardianKey,
                'show-fields' => 'all',
                'page-size' => 50,
            ]);

            if (!$response->successful()) {
                $this->warn("Guardian API returned error status: {$response->status()}");
                \Log::warning("Guardian API error", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return 0;
            }

            $articles = [];
            $results = $response->json('response.results') ?? [];
            
            foreach ($results as $item) {
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

            $count = count($articles);
            $this->saveArticles($articles, 'The Guardian');
            $this->info("Guardian fetch complete: {$count} articles");
            return $count;
        } catch (\Exception $e) {
            $this->warn('Error fetching from The Guardian: ' . $e->getMessage());
            \Log::warning('Guardian fetch failed: ' . $e->getMessage());
            return 0;
        }
    }


    protected function fetchFromBBC()
    {
        $this->info('Fetching from BBC News...');
        try {
            $articles = $this->fetchNewsApiArticles('bbc-news');
            $count = count($articles);
            $this->saveArticles($articles ?? [], 'BBC News');
            $this->info("BBC News fetch complete: {$count} articles");
            return $count;
        } catch (\Exception $e) {
            $this->warn('Error fetching from BBC News: ' . $e->getMessage());
            \Log::warning('BBC News fetch failed: ' . $e->getMessage());
            return 0;
        }
    }

    protected function fetchNewsApiArticles($source = null)
    {
        $apiKey = config('services.newsapi.key');
        
        if (empty($apiKey)) {
            $this->warn('Missing NewsAPI key in configuration');
            \Log::warning('NewsAPI fetch skipped: Missing API key');
            return [];
        }
        
        $query = [
            'apiKey' => $apiKey,
            'language' => 'en',
            'pageSize' => 50,
        ];

        if ($source) {
            $query['sources'] = $source;
        }

        $response = Http::get('https://newsapi.org/v2/top-headlines', $query);

        if (!$response->successful()) {
            $this->warn("NewsAPI returned error status: {$response->status()}");
            \Log::warning("NewsAPI error", [
                'status' => $response->status(),
                'body' => $response->body(),
                'source' => $source
            ]);
            return [];
        }
        
        return $response->json('articles') ?? [];
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
