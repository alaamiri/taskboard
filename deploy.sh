#!/bin/bash
set -e

echo "🚀 Déploiement en cours..."

# Mode maintenance
php artisan down --render="errors::503"

# Mise à jour du code
git pull origin main

# Dépendances
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Migrations
php artisan migrate --force

# Redémarrage des services
php artisan queue:restart
php artisan reverb:restart

# Fin maintenance
php artisan up

echo "✅ Déploiement terminé!"
