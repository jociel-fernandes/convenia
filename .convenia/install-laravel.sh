#!/bin/bash

echo "🚀 Instalando Laravel 12 no container..."

# Aguardar containers ficarem prontos
echo "⏳ Aguardando containers ficarem prontos..."
sleep 10

# Instalar Laravel 12 dentro do container na pasta src
echo "📦 Criando projeto Laravel 12..."
docker-compose exec app composer create-project laravel/laravel . "^12.0"

# Copiar arquivo .env personalizado da raiz para dentro da pasta src
echo "📋 Configurando arquivo .env..."
if [ -f .env ]; then
    echo "Copiando .env da raiz para src..."
    cp .env src/.env
else
    echo "⚠️  Arquivo .env não encontrado na raiz. Copiando .env.example..."
    cp .env.example src/.env
fi

# Gerar chave da aplicação
echo "🔑 Gerando chave da aplicação..."
docker-compose exec app php artisan key:generate

# Configurar permissões
echo "🔧 Configurando permissões..."
docker-compose exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker-compose exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache

echo "✅ Laravel 12 instalado com sucesso!"
echo ""
echo "Próximos passos:"
echo "1. Execute: docker-compose up -d"
echo "2. Acesse: http://localhost:8000"
echo "3. Para comandos: docker-compose exec app php artisan <command>"