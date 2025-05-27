# Use an official PHP image with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Change Debian mirror
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

# Create custom Apache configuration for port 3000
RUN echo "Listen 3000" > /etc/apache2/ports.conf && \
    echo "<VirtualHost *:3000>" > /etc/apache2/sites-available/000-default.conf && \
    echo "    DocumentRoot /var/www/html" >> /etc/apache2/sites-available/000-default.conf && \
    echo "    <Directory /var/www/html>" >> /etc/apache2/sites-available/000-default.conf && \
    echo "        AllowOverride All" >> /etc/apache2/sites-available/000-default.conf && \
    echo "        Require all granted" >> /etc/apache2/sites-available/000-default.conf && \
    echo "    </Directory>" >> /etc/apache2/sites-available/000-default.conf && \
    echo "    ErrorLog \${APACHE_LOG_DIR}/error.log" >> /etc/apache2/sites-available/000-default.conf && \
    echo "    CustomLog \${APACHE_LOG_DIR}/access.log combined" >> /etc/apache2/sites-available/000-default.conf && \
    echo "</VirtualHost>" >> /etc/apache2/sites-available/000-default.conf

# Copy application files
COPY . /var/www/html/

# Ensure correct permissions for web server
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port 3000
EXPOSE 3000

# Start Apache
CMD ["apache2-foreground"]