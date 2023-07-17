FROM php:8.1-fpm

RUN docker-php-ext-install pdo pdo_mysql

RUN mkdir -p "/etc/supervisor/logs"

RUN apt-get update && apt-get install -y supervisor

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY ./docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf

CMD ["/usr/bin/supervisord", "-n", "-c",  "/etc/supervisor/supervisord.conf"]
