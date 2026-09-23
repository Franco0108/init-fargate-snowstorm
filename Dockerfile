# Dockerfile
FROM php:8.2-cli

WORKDIR /var/www/html

# Instalamos curl y las librerías necesarias para la extensión curl de PHP
RUN apt-get update && apt-get install -y curl libcurl4-openssl-dev && rm -rf /var/lib/apt/lists/* && \
    docker-php-ext-install curl

# Copiamos la app PHP
COPY src/ /var/www/html/

# Copiamos el script de inicialización
COPY startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

# Exponemos el puerto 8080
EXPOSE 8080

# Ejecutar el script de inicialización
CMD ["/usr/local/bin/startup.sh"]
