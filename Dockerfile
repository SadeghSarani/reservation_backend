FROM php:8.4-apache

# نصب اکستنشن‌های مورد نیاز PHP و ابزارهای لازم
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    libmemcached-dev \
    zlib1g-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip mysqli pdo pdo_mysql \
    && docker-php-ext-enable pdo_mysql

# تنظیمات آپلود فایل و سایر محدودیت‌ها
RUN echo "upload_max_filesize=100M\npost_max_size=100M\nmemory_limit=256M\nmax_execution_time=300\nmax_input_vars=5000\nfile_uploads=On" > /usr/local/etc/php/conf.d/uploads.ini

# کپی سورس پروژه
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/

# فعال‌سازی mod_rewrite
RUN a2enmod rewrite

EXPOSE 80

# نصب Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV COMPOSER_ALLOW_SUPERUSER=1
