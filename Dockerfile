FROM php:8.2-apache

# Cài đặt các extension PHP cần thiết cho MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Bật module rewrite của Apache
RUN a2enmod rewrite

# Thiết lập thư mục làm việc
WORKDIR /var/www/html

# Mở cổng 80 cho web server
EXPOSE 80
