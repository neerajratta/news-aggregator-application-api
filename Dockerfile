FROM php:8.1-fpm

# Set working directory
WORKDIR /var/www

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    libonig-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Copy existing application directory contents
COPY . /var/www

#Create Vendor Directory
RUN mkdir -p /var/www/vendor && chown -R www:www /var/www

# Copy existing application directory permissions
COPY --chown=www:www . /var/www

# Install dependencies and optimize the application
WORKDIR /var/www
RUN composer install --no-interaction --optimize-autoloader --no-dev

#set proper permissions
RUN chown -R www:www /var/www

# Change user to www
USER www

# Generate Laravel cache files for better performance
RUN php artisan config:cache
RUN php artisan cache:clear
RUN php artisan route:cache
RUN php artisan view:cache

# Generate Swagger documentation - use || true to prevent build failures
RUN php artisan l5-swagger:generate || true

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
