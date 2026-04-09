# Installation du système de contact avec base de données

## 📋 Fonctionnalités ajoutées

- ✅ Enregistrement des demandes de contact en base de données
- ✅ Sélection d'items du menu dans le formulaire
- ✅ Gestion AJAX pour une expérience utilisateur fluide
- ✅ Messages de succès/erreur
- ✅ Architecture MVC propre

## 🗄️ Configuration de la base de données

### 1. Créer le fichier .env

Si vous n'avez pas encore de fichier `.env`, copiez `.env.example` :

```bash
cp .env.example .env
```

### 2. Configurer les identifiants de connexion

Éditez le fichier `.env` et configurez vos paramètres MySQL :

```env
DB_HOST=localhost
DB_NAME=traiteur
DB_USER=root
DB_PASS=votre_mot_de_passe
DB_CHARSET=utf8mb4
```

### 3. Créer la base de données et les tables

Connectez-vous à MySQL et exécutez le script SQL :

```bash
# Méthode 1 : Via ligne de commande
mysql -u root -p < storage/database.sql

# Méthode 2 : Via MySQL
mysql -u root -p
```

Puis dans MySQL :

```sql
SOURCE /var/www/html/Traiteur-Passion/storage/database.sql;
```

Ou créez manuellement la base :

```sql
CREATE DATABASE IF NOT EXISTS traiteur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE traiteur;

-- Puis copiez/collez le contenu de storage/database.sql
```

## 🏗️ Structure de la base de données

### Table `contact_requests`

Stocke les demandes de contact avec :

- Informations du client (nom, email, téléphone)
- Détails de l'événement (date, lieu, type, nombre de personnes)
- Message
- Statut de la demande (new, in_progress, quoted, completed, cancelled)
- Timestamps

### Table `contact_menu_items`

Stocke les items de menu sélectionnés pour chaque demande :

- Lien avec la demande de contact
- Nom de l'item
- Catégorie (paniers, carte, aperitif, etc.)
- Prix
- Quantité
- Notes optionnelles

## 🔧 Recompiler les assets

Après l'installation, recompilez les assets pour inclure le nouveau JavaScript :

```bash
npm run dev
# ou pour la production
npm run build
```

## 📬 Configurer l'envoi d'emails

Le projet peut maintenant envoyer :

- une notification à l'administrateur pour chaque demande de contact ou devis
- un accusé de réception automatique au client

Ajoutez et renseignez ces variables dans `.env` :

```env
MAIL_ENABLED=1
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_SMTP_AUTH=1
MAIL_USERNAME=utilisateur-smtp
MAIL_PASSWORD=mot-de-passe-smtp
MAIL_ENCRYPTION=tls
MAIL_TIMEOUT=15
MAIL_FROM_ADDRESS=no-reply@traiteur-passion.fr
MAIL_FROM_NAME="Traiteur Passion"
MAIL_ADMIN_TO=contact@traiteur-passion.fr
MAIL_NOTIFY_ADMIN=1
MAIL_ACK_CLIENT=1
```

Notes :

- `MAIL_ADMIN_TO` accepte plusieurs destinataires séparés par des virgules
- laissez `MAIL_ENABLED=0` tant que le SMTP n'est pas prêt
- si la base enregistre bien la demande mais que l'email échoue, l'erreur est journalisée côté PHP sans bloquer l'envoi du formulaire

## 🧪 Tester le formulaire

1. Accédez à la page contact : `http://localhost/contact`
2. Remplissez le formulaire
3. (Optionnel) Sélectionnez des items du menu
4. Soumettez le formulaire
5. Vérifiez que les données sont bien enregistrées :

```sql
SELECT * FROM contact_requests ORDER BY created_at DESC LIMIT 5;
SELECT * FROM contact_menu_items;
```

## 📁 Fichiers créés/modifiés

### Nouveaux fichiers :

- `app/Core/Database.php` - Classe singleton pour la connexion PDO
- `app/Models/Contact.php` - Modèle pour les opérations CRUD
- `app/Controllers/ContactController.php` - Contrôleur pour gérer les soumissions
- `app/Services/Mailer.php` - Service SMTP basé sur PHPMailer
- `app/Services/ContactNotificationService.php` - Orchestration des notifications admin + client
- `app/Controllers/AdminController.php` - Contrôleur pour l'interface d'administration
- `app/Views/admin/contacts.php` - Liste des demandes de contact
- `app/Views/admin/contact-detail.php` - Détail d'une demande avec items du menu
- `config/mail.php` - Configuration centralisée des emails
- `storage/database.sql` - Script de création des tables
- `resources/js/contact/contactForm.js` - Gestion AJAX du formulaire

### Fichiers modifiés :

- `app/Core/Router.php` - Ajout du support des routes dynamiques avec paramètres
- `app/Views/pages/contact.php` - Ajout de la sélection d'items + messages d'alerte
- `resources/scss/pages/contact/_contact.scss` - Styles pour les nouveaux éléments
- `resources/js/main.js` - Import du module contactForm
- `app/Routes/web.php` - Ajout des routes POST /contact et routes admin
- `app/Controllers/HomeController.php` - Suppression de la méthode contact()
- `.env.example` - Variables SMTP d'exemple
- `composer.json` - Dépendance PHPMailer

## 🎯 Utilisation

### Côté utilisateur

Le client peut maintenant :

1. Remplir le formulaire de contact habituel
2. Ouvrir l'accordéon "Choisir parmi nos menus"
3. Sélectionner les items qui l'intéressent
4. Soumettre sa demande

### Interface d'administration

Accédez à l'interface admin pour gérer les demandes :

**URL :** `http://votre-site.com/admin/contacts`

Fonctionnalités :

- 📊 Vue d'ensemble avec statistiques (total, nouvelles demandes, demandes avec menus)
- 📋 Liste de toutes les demandes avec filtres
- 🔍 Vue détaillée de chaque demande avec items du menu sélectionnés
- 📥 Export CSV de toutes les demandes
- 📧 Liens rapides pour répondre par email
- 📞 Liens pour appeler directement le client

**Note :** Cette interface n'est pas protégée par mot de passe. Pour la production, ajoutez un système d'authentification.

### Côté administrateur (via code)

Vous pouvez également consulter les demandes directement via le code :

```php
$contactModel = new \App\Models\Contact();

// Récupérer toutes les demandes
$requests = $contactModel->getAll();

// Récupérer une demande spécifique avec ses items
$request = $contactModel->getById(1);
print_r($request['menu_items']);
```

## 🔐 Sécurité

- ✅ Requêtes préparées (PDO) contre les injections SQL
- ✅ Validation des données côté serveur
- ✅ Échappement HTML avec `htmlspecialchars()`
- ✅ Validation des emails
- ✅ Gestion des erreurs avec try/catch

## 📧 Prochaines étapes suggérées

1. **Protection anti-spam** : Ajouter un honeypot, une limitation de débit ou un captcha
2. **Observabilité** : Centraliser les erreurs SMTP dans des logs exploitables ou un dashboard
3. **Back-office** : Permettre la relance manuelle d'un accusé de réception depuis l'admin
4. **Templates** : Personnaliser davantage les emails selon le type de demande

## ❓ Dépannage

### Erreur de connexion à la base de données

- Vérifiez les identifiants dans `.env`
- Assurez-vous que MySQL est démarré
- Vérifiez que la base de données existe

### Les assets ne se chargent pas

```bash
npm install
npm run build
```

### Erreur 500 lors de la soumission

- Vérifiez les logs PHP
- Vérifiez que les tables existent
- Vérifiez les permissions du dossier `storage/logs`
