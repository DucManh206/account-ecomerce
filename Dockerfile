FROM php:8.2-apache

# Cài đặt các extension PHP cần thiết cho MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Bật rewrite và cho phép .htaccess khóa file cấu hình nhạy cảm.
RUN a2enmod rewrite \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Thiết lập thư mục làm việc
WORKDIR /var/www/html

# Mở cổng 80 cho web server
EXPOSE 80
