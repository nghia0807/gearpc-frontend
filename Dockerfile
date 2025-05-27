# Use an official PHP image with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Change Debian mirror - trying a different one to potentially bypass network issues
# You can try other mirrors if this one also causes issues.
# A list of Debian mirrors can be found at: https://www.debian.org/mirror/list
# This example uses a mirror from MIT (US).
RUN echo "deb http://debian.csail.mit.edu/debian/ bookworm main" > /etc/apt/sources.list && \
    echo "deb http://debian.csail.mit.edu/debian/ bookworm-updates main" >> /etc/apt/sources.list && \
    echo "deb http://security.debian.org/debian-security bookworm-security main" >> /etc/apt/sources.list

# Install system dependencies
RUN apt-get update -qq && apt-get install -y --no-install-recommends \
    libxml2-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    libzip-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install curl mbstring fileinfo xml simplexml

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Ensure correct permissions for web server
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 3000

# Apache is started by the base image's CMD