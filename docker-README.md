# News Aggregator API - Docker Setup

This document explains how to run the News Aggregator API using Docker.

## Prerequisites

- [Docker](https://www.docker.com/products/docker-desktop/)
- [Docker Compose](https://docs.docker.com/compose/install/)

## Setup Instructions

### 1. Clone the Repository

```bash
git clone <repository-url>
cd news-aggregator-application-api
```

### 2. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

Update the `.env` file with the following database settings to match the Docker environment:

```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=news_aggregator
DB_USERNAME=news_user
DB_PASSWORD=secret
```

### 3. Build and Start Docker Containers

```bash
docker-compose up -d
```

This command will:
- Build the PHP application container
- Start the Nginx web server
- Start the MySQL database server

### 4. Run the Complete Setup (Simplified Method)

We've added custom composer scripts to simplify the setup process. You can run all required commands with a single command:

```bash
docker-compose exec app composer run-script docker-setup
```

This will automatically:
- Install composer dependencies
- Generate application key
- Run database migrations
- Seed the database with test data
- Generate Swagger documentation

### Alternatively, Run Each Step Manually (if preferred)

#### 4a. Install Laravel Dependencies

```bash
docker-compose exec app composer install
```

#### 4b. Generate Application Key

```bash
docker-compose exec app php artisan key:generate
```

#### 4c. Run Migrations and Seeders

```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

### 7. Configure Permissions (if needed)

```bash
docker-compose exec app chmod -R 777 storage bootstrap/cache
```

## Accessing the Application

- **API Endpoint**: http://localhost:8000/api/v1
- **API Documentation**: http://localhost:8000/api/documentation

## Common Development Commands

### Running Artisan Commands

```bash
docker-compose exec app php artisan <command>
```

Examples:
```bash
# Create a new migration
docker-compose exec app php artisan make:migration create_new_table

# Clear cache
docker-compose exec app php artisan cache:clear

# Generate Swagger documentation
docker-compose exec app php artisan l5-swagger:generate
```

### Running Tests

```bash
docker-compose exec app php artisan test
```

### Refreshing the Database

If you need to reset the database with fresh migrations and seed data:

```bash
docker-compose exec app composer run-script docker-refresh
```

This will drop all tables, run migrations from scratch, and re-seed the database.

### Viewing Logs

```bash
docker-compose logs -f app
```

## Stopping the Application

```bash
docker-compose down
```

To remove all data volumes (including the database):

```bash
docker-compose down -v
```

## Rebuilding Containers

If you make changes to the Dockerfile or need to rebuild the containers:

```bash
docker-compose build --no-cache
docker-compose up -d
```

## Container Structure

- **app**: PHP 8.1 container with Laravel application
- **webserver**: Nginx web server
- **db**: MySQL 8.0 database server

## Troubleshooting

### Database Connection Issues

If you encounter database connection issues, ensure:
1. The database container is running: `docker-compose ps`
2. Your .env file has the correct settings (see Setup Instructions #2)
3. Try restarting the containers: `docker-compose restart`

### Permission Issues

If you encounter permission issues:

```bash
docker-compose exec app chown -R www:www /var/www
```

### Ports Already in Use

If port 8000 or 3306 is already in use on your host machine, modify the `docker-compose.yml` file to use different ports.
