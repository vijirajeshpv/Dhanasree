FROM php:8.1-apache

# update os and install common dependencies
RUN apt-get update && apt-get install -y \ 
    curl \ 
    wget \ 
    vim  \
    git  \
    zip  \
    unzip  \
    libicu-dev  \
    build-essential  \
    libzip-dev  \
    libssl-dev  \
    zlib1g-dev  \
    libpng-dev  \
    libjpeg-dev   \
    libonig-dev \
    cmake \
    libfreetype6-dev \
    libfontconfig1-dev  \
    xclip 
# COPY sites-available/elioter.conf /etc/apache2/sites-enabled/elioter.conf

# # PHP packages
# RUN docker-php-ext-install intl
# RUN docker-php-ext-configure intl
# RUN docker-php-ext-install mysqli zip mbstring gd pcntl exif bcmath
RUN docker-php-ext-install mysqli 
# # RUN docker-php-ext-install mysqli pdo pdo_mysql zip gd pcntl exif
# RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
#     && docker-php-ext-install gd

# RUN docker-php-ext-configure zip 

# # Enable apache modules
# RUN a2enmod rewrite headers expires

# # XDEBUG
# RUN pecl install xdebug
# RUN docker-php-ext-enable xdebug
# # This needs in order to run xdebug from VSCode
# ENV PHP_IDE_CONFIG 'serverName=DockerApp'

COPY . /var/www/html

# # Permissions
RUN chown -R www-data:www-data /var/www/html
# RUN chmod u+rwx,g+rx,o+rx /var/www/html
# RUN find /var/www/html -type d -exec chmod u+rwx,g+rx,o+rx {} +
# RUN find /var/www/html -type f -exec chmod u+rw,g+rw,o+r {} +

EXPOSE 80
EXPOSE 443 