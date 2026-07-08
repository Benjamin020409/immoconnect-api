#!/bin/sh
set -e

# Render fournit le port d'écoute via la variable d'environnement PORT.
# On l'injecte dans la config Nginx (par défaut 10000 en local si absent).
PORT_TO_USE=${PORT:-10000}
sed -i "s/__PORT__/${PORT_TO_USE}/g" /etc/nginx/nginx.conf

# Génère la clé d'application si elle n'existe pas déjà (à éviter en prod
# si APP_KEY est déjà définie dans les variables d'environnement Render).
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Cache la config, les routes et les vues pour de meilleures perfs
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Lance les migrations en production (à retirer si vous préférez
# les lancer manuellement via le shell Render)
php artisan migrate --force

# Démarre nginx + php-fpm via supervisord
exec supervisord -c /etc/supervisord.conf