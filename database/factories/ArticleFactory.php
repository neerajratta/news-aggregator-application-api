<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence(),
            'author' => $this->faker->optional(0.9)->name(),
            'description' => $this->faker->paragraph(),
            'content' => $this->faker->paragraphs(3, true),
            'published_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'source' => $this->faker->randomElement(['BBC News', 'The Guardian', 'CNN', 'Reuters', 'Associated Press']),
            'category' => $this->faker->optional(0.9)->randomElement(['Politics', 'Business', 'Technology', 'Science', 'Health', 'Sports']),
            'url' => $this->faker->unique()->url(),
            'url_to_image' => $this->faker->optional(0.8)->imageUrl(),
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (Article $article) {
            // Additional customization if needed
        });
    }
}
