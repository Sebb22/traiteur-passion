# 🚀 Guide de démarrage rapide - Système de contact avec BDD

## Installation en 5 étapes

### 1️⃣ Configurer la base de données

```bash
# Copier le fichier de configuration
cp .env.example .env

# Éditer .env et configurer vos identifiants MySQL
nano .env
```

### 2️⃣ Créer la base et les tables

```bash
# Méthode recommandée : script automatique
./setup-database.sh

# OU méthode manuelle
mysql -u root -p
> SOURCE /var/www/html/Traiteur-Passion/storage/database.sql;
> CREATE USER IF NOT EXISTS 'tp'@'localhost' IDENTIFIED BY 'tpAdmin@@';
> GRANT ALL PRIVILEGES ON traiteur.* TO 'tp'@'localhost';
> FLUSH PRIVILEGES;
> exit
```

### 3️⃣ Compiler les assets

```bash
npm install
npm run build
```

### 4️⃣ Tester l'installation

```bash
./test-installation.sh
```

### 5️⃣ Démarrer le serveur

```bash
# Option 1 : Serveur PHP intégré
php -S localhost:8000 -t public

# Option 2 : Si déjà configuré avec Apache/Nginx
# Accédez directement à votre URL
```

## 🎯 URLs importantes

- **Site public** : `http://localhost:8000/`
- **Page contact** : `http://localhost:8000/contact`
- **Interface admin** : `http://localhost:8000/admin/contacts`
- **Export CSV** : `http://localhost:8000/admin/contacts/export`

## ✅ Vérification rapide

Testez que tout fonctionne :

1. ✅ Accédez à `/contact`
2. ✅ Remplissez le formulaire
3. ✅ Sélectionnez des items du menu (optionnel)
4. ✅ Soumettez le formulaire
5. ✅ Vérifiez l'enregistrement dans `/admin/contacts`

## 📊 Requêtes SQL utiles

```sql
-- Voir toutes les demandes
SELECT * FROM contact_requests ORDER BY created_at DESC;

-- Voir les demandes avec items de menu
SELECT cr.*, COUNT(cmi.id) as items_count
FROM contact_requests cr
LEFT JOIN contact_menu_items cmi ON cr.id = cmi.contact_id
GROUP BY cr.id
ORDER BY cr.created_at DESC;

-- Voir le détail d'une demande avec ses items
SELECT cr.*, cmi.menu_item_name, cmi.menu_item_category, cmi.menu_item_price
FROM contact_requests cr
LEFT JOIN contact_menu_items cmi ON cr.id = cmi.contact_id
WHERE cr.id = 1;
```

## 🔧 Résolution de problèmes

### Erreur 500 sur la page contact

- Vérifiez que les tables existent
- Vérifiez la connexion MySQL dans `.env`
- Vérifiez les logs : `storage/logs/`

### Le formulaire ne s'envoie pas

- Ouvrez la console du navigateur (F12)
- Vérifiez que `contactForm.js` est chargé
- Recompilez les assets : `npm run build`

### L'interface admin est vide

- Vérifiez que la base contient des données
- Testez avec : `SELECT * FROM contact_requests;`

## 📝 Structure des données

### Table contact_requests

```sql
id, name, email, phone, people, date, location,
type, message, status, created_at, updated_at
```

### Table contact_menu_items

```sql
id, contact_id, menu_item_name, menu_item_category,
menu_item_price, quantity, notes, created_at
```

## 🎨 Personnalisation

### Ajouter plus d'items de menu

Éditez : `app/Views/pages/contact.php` (lignes 80-150)

### Modifier les styles

Éditez : `resources/scss/pages/contact/_contact.scss`

### Ajouter des champs au formulaire

1. Ajoutez le champ HTML dans `contact.php`
2. Modifiez `ContactController.php` pour traiter le champ
3. Ajoutez la colonne dans la table SQL

## 📚 Documentation complète

Pour plus de détails, consultez : `INSTALLATION_CONTACT.md`

## 🆘 Support

En cas de problème :

1. Exécutez `./test-installation.sh`
2. Consultez les logs PHP
3. Vérifiez la console navigateur (F12)
