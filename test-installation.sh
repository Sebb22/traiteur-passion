#!/bin/bash

# Script de test de l'installation du système de contact

echo "==================================="
echo "Test de l'installation - Contact DB"
echo "==================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Check if .env file exists
echo "1. Vérification du fichier .env..."
if [ -f ".env" ]; then
    echo -e "${GREEN}✓${NC} Fichier .env trouvé"
else
    echo -e "${RED}✗${NC} Fichier .env manquant"
    echo "   Créez-le avec: cp .env.example .env"
fi

# 2. Check database connection
echo ""
echo "2. Test de connexion à la base de données..."
php -r "
require 'vendor/autoload.php';
\$config = require 'config/database.php';
try {
    \$pdo = new PDO(
        \"mysql:host={\$config['host']};charset={\$config['charset']}\",
        \$config['user'],
        \$config['pass']
    );
    echo \"\\033[0;32m✓\\033[0m Connexion MySQL réussie\\n\";
} catch (PDOException \$e) {
    echo \"\\033[0;31m✗\\033[0m Erreur de connexion: \" . \$e->getMessage() . \"\\n\";
    exit(1);
}
"

# 3. Check if database exists
echo ""
echo "3. Vérification de la base de données..."
php -r "
require 'vendor/autoload.php';
\$config = require 'config/database.php';
try {
    \$pdo = new PDO(
        \"mysql:host={\$config['host']};charset={\$config['charset']}\",
        \$config['user'],
        \$config['pass']
    );
    \$stmt = \$pdo->query(\"SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{\$config['name']}'\");
    if (\$stmt->fetch()) {
        echo \"\\033[0;32m✓\\033[0m Base de données '{\$config['name']}' existe\\n\";
    } else {
        echo \"\\033[0;31m✗\\033[0m Base de données '{\$config['name']}' n'existe pas\\n\";
        echo \"   Créez-la avec: mysql -u root -p < storage/database.sql\\n\";
        exit(1);
    }
} catch (PDOException \$e) {
    echo \"\\033[0;31m✗\\033[0m Erreur: \" . \$e->getMessage() . \"\\n\";
    exit(1);
}
"

# 4. Check if tables exist
echo ""
echo "4. Vérification des tables..."
php -r "
require 'vendor/autoload.php';
\$config = require 'config/database.php';
try {
    \$pdo = new PDO(
        \"mysql:host={\$config['host']};dbname={\$config['name']};charset={\$config['charset']}\",
        \$config['user'],
        \$config['pass']
    );
    
    \$tables = ['contact_requests', 'contact_menu_items'];
    foreach (\$tables as \$table) {
        \$stmt = \$pdo->query(\"SHOW TABLES LIKE '\$table'\");
        if (\$stmt->fetch()) {
            echo \"\\033[0;32m✓\\033[0m Table '\$table' existe\\n\";
        } else {
            echo \"\\033[0;31m✗\\033[0m Table '\$table' n'existe pas\\n\";
        }
    }
} catch (PDOException \$e) {
    echo \"\\033[0;31m✗\\033[0m Erreur: \" . \$e->getMessage() . \"\\n\";
    exit(1);
}
"

# 5. Check if files exist
echo ""
echo "5. Vérification des fichiers créés..."
files=(
    "app/Core/Database.php"
    "app/Models/Contact.php"
    "app/Controllers/ContactController.php"
    "app/Controllers/AdminController.php"
    "resources/js/contact/contactForm.js"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $file"
    else
        echo -e "${RED}✗${NC} $file manquant"
    fi
done

# 6. Check if assets are compiled
echo ""
echo "6. Vérification de la compilation des assets..."
if [ -d "public/build/assets" ]; then
    echo -e "${GREEN}✓${NC} Assets compilés"
else
    echo -e "${YELLOW}⚠${NC} Assets non compilés"
    echo "   Compilez-les avec: npm run build"
fi

echo ""
echo "==================================="
echo "Test terminé !"
echo "==================================="
echo ""
echo "Pour tester le formulaire :"
echo "1. Démarrez le serveur : php -S localhost:8000 -t public"
echo "2. Accédez à : http://localhost:8000/contact"
echo "3. Pour l'admin : http://localhost:8000/admin/contacts"
echo ""
