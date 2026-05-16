#!/bin/bash
cd "$(dirname "$0")/backend"

if [ ! -d "vendor" ]; then
    echo "Installing dependencies..."
    /Applications/MAMP/bin/php/php8.3.14/bin/php composer.phar install
fi

echo "Starting backend at http://localhost:8080"
/Applications/MAMP/bin/php/php8.3.14/bin/php -S localhost:8080 -t public