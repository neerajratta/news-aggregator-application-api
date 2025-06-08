# News Aggregator Application API

A Laravel-based RESTful API that aggregates news from multiple external sources (NewsAPI.org, The Guardian, BBC), provides comprehensive filtering capabilities, supports user preferences, and delivers personalized news feeds.

## Features

- **Authentication**: Secure API authentication using Laravel Sanctum
- **Article Management**: Fetch and store articles from multiple news sources
- **Advanced Filtering**: Filter articles by keyword, category, source, and date
- **User Preferences**: Store and manage user preferences for sources, categories, and authors
- **Personalized Feeds**: Generate customized news feeds based on user preferences
- **API Documentation**: Comprehensive Swagger/OpenAPI documentation
- **Artisan Commands**: Custom command to fetch news articles from external APIs
- **Dockerized Environment**: Easy setup and deployment with Docker
- **Caching**: Response caching for improved performance
- **Rate Limiting**: Throttling to prevent abuse

## Tech Stack

- **Framework**: Laravel 9.x
- **Database**: MySQL
- **Caching**: Redis
- **Authentication**: Laravel Sanctum
- **Documentation**: Swagger/OpenAPI
- **Containerization**: Docker & Docker Compose
- **External APIs**: NewsAPI.org, The Guardian, BBC

## Setup Instructions

### Prerequisites

- Docker and Docker Compose installed
- API keys for news sources (NewsAPI.org, The Guardian)

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/news-aggregator-application-api.git
   cd news-aggregator-application-api
   ```

2. Copy the environment file and configure it with your API keys:
   ```bash
   cp .env.example .env
   ```
   
3. Edit the `.env` file and add your API keys:
   ```
   NEWS_API_KEY=your_newsapi_key
   GUARDIAN_API_KEY=your_guardian_key
   ```

4. Start the Docker containers:
   ```bash
   docker-compose up -d
   ```

5. Install dependencies and set up the application:
   ```bash
   docker-compose exec app composer install
   docker-compose exec app php artisan key:generate
   docker-compose exec app php artisan migrate
   ```
   Note: `composer install` and Swagger generation are already handled in the Dockerfile,
   but if you've modified dependencies after building the container, you may need to run:
   ```bash
   docker-compose exec app composer install
   ```

## Usage

### API Endpoints

- **Authentication**
  - `POST /api/register` - Register a new user
  - `POST /api/login` - Login and get access token
  - `POST /api/logout` - Logout and invalidate token

- **Articles**
  - `GET /api/v1/articles` - Get all articles (with pagination and filters)
  - `GET /api/v1/articles/{id}` - Get a specific article

- **User Preferences**
  - `GET /api/v1/user/preferences` - Get user preferences
  - `POST /api/v1/user/preferences` - Update user preferences
  - `POST /api/v1/user/preferences/reset` - Reset user preferences
  - `DELETE /api/v1/user/preferences` - Alternative reset preferences

- **Personalized Feed**
  - `GET /api/v1/user/feed` - Get personalized feed based on preferences

### Artisan Commands

- Fetch articles from all configured news sources:
  ```bash
  docker-compose exec app php artisan news:fetch
  ```

### API Documentation

Access the API documentation at `/api/documentation` when the application is running.

## Testing

Run the test suite:
```bash
docker-compose exec app php artisan test
```

## Development

### Code Structure

- `app/Console/Commands` - Artisan commands for fetching news articles
- `app/Http/Controllers/API` - API controllers
- `app/Http/Resources` - API resources and transformers
- `app/Models` - Database models
- `app/Services` - Service classes for business logic
- `tests` - Unit and feature tests

## Deployment

The application is containerized with Docker, making deployment consistent across different environments.

## License

MIT
