ARG PHP_VERSION=8.4.19
FROM php:${PHP_VERSION}-fpm-alpine

RUN echo -e "\n\033[1m php:${PHP_VERSION}-fpm-alpine \033[0m\n"

ARG DOCKER_GID=1000
ARG DOCKER_UID=1000

ENV DOCKER_UID=${DOCKER_UID}
ENV DOCKER_GID=${DOCKER_GID}

WORKDIR /app

RUN addgroup -g ${DOCKER_GID} appuser
RUN adduser -G appuser -D -s /bin/sh -u ${DOCKER_UID} appuser

RUN sed -i "s/user = www-data/user = appuser/g" /usr/local/etc/php-fpm.d/www.conf
RUN sed -i "s/group = www-data/group = appuser/g" /usr/local/etc/php-fpm.d/www.conf
RUN echo "php_admin_flag[log_errors] = on" >> /usr/local/etc/php-fpm.d/www.conf

# Install OS dependencies
RUN apk update
RUN apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS
RUN apk add --update --no-cache autoconf bash pkgconf libssl3 openssl-dev libpq-dev git openssh-client curl wget libtool zlib-dev icu-dev g++ linux-headers libzip-dev

# Install general PHP modules
RUN docker-php-ext-install pgsql \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j2 intl \
    && pecl bundle -d /usr/src/php/ext apcu \
    && docker-php-ext-install -j2 apcu \
    && docker-php-ext-install -j2 bcmath \
    && docker-php-ext-enable opcache \
    && docker-php-ext-install -j2 zip

# Install X-debug
RUN pecl install xdebug

# Cleanup
RUN rm /usr/src/php/ext/*.tgz

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN chmod u+s /usr/bin/composer

RUN echo 'linux' > /etc/docker_host && \
    chmod +r /etc/docker_host

RUN apk add nano

RUN chown -R appuser:appuser /app

USER appuser

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]
