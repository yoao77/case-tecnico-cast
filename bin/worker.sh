#!/bin/sh

# Simula um worker: a cada 5 segundos, retoma os processamentos que ficaram para trás na fila.
echo "Worker rodando. Ctrl+C para parar."

while true; do
    php "$(dirname "$0")/processar-fila.php"
    sleep 5
done
