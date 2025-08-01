FROM php:8.2-cli

# System-Abhängigkeiten installieren
RUN apt-get update && \
    apt-get install -y git unzip libcurl4-openssl-dev && \
    rm -rf /var/lib/apt/lists/*

# PHP-Erweiterungen installieren
RUN docker-php-ext-install curl json

WORKDIR /app
COPY . /app

CMD ["php", "-v"]
