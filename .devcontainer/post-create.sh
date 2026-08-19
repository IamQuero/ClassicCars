#!/bin/bash
set -e


sudo PHP_INI_DIR="$PHP_INI_DIR" docker-php-ext-install pdo_pgsql

# Composer ya viene en la imagen de PHP, esto es solo un seguro por si falta
if ! command -v composer &> /dev/null; then
  curl -sS https://getcomposer.org/installer | php
  mv composer.phar /usr/local/bin/composer
fi

# Crea el backend Laravel solo la primera vez (si ya existe, no lo toca)
if [ ! -f backend/artisan ]; then
  composer create-project laravel/laravel backend
fi

echo ""
echo "Entorno listo."
echo "Entra en backend/ y ejecuta: php artisan serve --host=0.0.0.0 --port=8000"
