#!/bin/bash

# Script de inicialización con prueba de conectividad a OpenSearch/Elasticsearch

# Función para probar conectividad
test_elasticsearch_connectivity() {
    if [ -z "$ELASTICSEARCH_URLS" ]; then
        echo "[$(date +'%Y-%m-%d %H:%M:%S')] ELASTICSEARCH_URLS no está configurado, omitiendo prueba de conectividad"
        return 0
    fi

    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Probando conectividad a OpenSearch en: $ELASTICSEARCH_URLS"

    # Usar curl con timeout de 5 segundos
    response=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 --max-time 10 "$ELASTICSEARCH_URLS" 2>/dev/null)

    if [ "$response" = "200" ] || [ "$response" = "401" ]; then
        echo "[$(date +'%Y-%m-%d %H:%M:%S')] ✓ OpenSearch está disponible (HTTP $response)"
        return 0
    else
        echo "[$(date +'%Y-%m-%d %H:%M:%S')] ✗ OpenSearch no disponible (HTTP $response o timeout)"
        return 0
    fi
}

# Ejecutar la prueba de conectividad en background (no bloqueante)
test_elasticsearch_connectivity &

# Iniciar el servidor PHP
exec php -S 0.0.0.0:8080 -t /var/www/html
