#!/bin/bash
# Script pour créer la base de données

echo "======================================"
echo "Configuration de la base de données"
echo "======================================"
echo ""

# Demander le mot de passe root MySQL
read -sp "Entrez le mot de passe root MySQL: " MYSQL_ROOT_PASS
echo ""

# Créer la base de données et les tables
echo "Création de la base de données et des tables..."
mysql -u root -p"$MYSQL_ROOT_PASS" < storage/database.sql

if [ $? -eq 0 ]; then
    echo "✅ Base de données créée avec succès!"
    echo ""
    
    # Demander si on veut créer l'utilisateur tp
    read -p "Voulez-vous créer l'utilisateur 'tp' pour l'application? (o/n) " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Oo]$ ]]; then
        echo "Création de l'utilisateur 'tp'..."
        mysql -u root -p"$MYSQL_ROOT_PASS" <<EOF
CREATE USER IF NOT EXISTS 'tp'@'localhost' IDENTIFIED BY 'tpAdmin@@';
GRANT ALL PRIVILEGES ON traiteur.* TO 'tp'@'localhost';
FLUSH PRIVILEGES;
EOF
        
        if [ $? -eq 0 ]; then
            echo "✅ Utilisateur 'tp' créé avec succès!"
            echo ""
            echo "Configuration dans .env:"
            echo "  DB_USER=tp"
            echo "  DB_PASS=tpAdmin@@"
        else
            echo "❌ Erreur lors de la création de l'utilisateur"
        fi
    fi
    
    # Demander si on veut insérer les données de test
    echo ""
    read -p "Voulez-vous insérer des données de test? (o/n) " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Oo]$ ]]; then
        echo "Insertion des données de test..."
        mysql -u root -p"$MYSQL_ROOT_PASS" < storage/sample-data.sql
        
        if [ $? -eq 0 ]; then
            echo "✅ Données de test insérées avec succès!"
        else
            echo "❌ Erreur lors de l'insertion des données de test"
        fi
    fi
    
    echo ""
    echo "======================================"
    echo "Configuration terminée!"
    echo "======================================"
    echo ""
    echo "Vous pouvez maintenant:"
    echo "1. Compiler les assets: npm run build"
    echo "2. Démarrer le serveur: php -S localhost:8000 -t public"
    echo "3. Accéder au site: http://localhost:8000/contact"
    echo ""
else
    echo "❌ Erreur lors de la création de la base de données"
    exit 1
fi
