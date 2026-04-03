# 📧 Système de Contact avec Base de Données - Traiteur Passion

## ✨ Nouveautés

Ce système permet aux clients de :

- ✅ Remplir un formulaire de contact détaillé
- ✅ **Sélectionner directement des items du menu** dans leur demande
- ✅ Recevoir une confirmation instantanée (AJAX)

Côté administrateur :

- ✅ Toutes les demandes sont enregistrées en base de données
- ✅ Interface d'administration complète
- ✅ Export CSV des demandes
- ✅ Vue détaillée avec items de menu sélectionnés

## 🚀 Installation rapide

```bash
# 1. Configurer la BDD
cp .env.example .env
nano .env  # Éditer les identifiants MySQL

# 2. Créer les tables
mysql -u root -p < storage/database.sql

# 3. (Optionnel) Insérer des données de test
mysql -u root -p < storage/sample-data.sql

# 4. Compiler les assets
npm install && npm run build

# 5. Tester
./test-installation.sh
```

## 📖 Documentation

- **🚀 Démarrage rapide** : [QUICKSTART.md](QUICKSTART.md)
- **📚 Installation complète** : [INSTALLATION_CONTACT.md](INSTALLATION_CONTACT.md)

## 🔗 URLs

- Page contact : `/contact`
- Interface admin : `/admin/contacts`
- Export CSV : `/admin/contacts/export`

## 🗄️ Structure BDD

### Table `contact_requests`

Stocke les demandes de contact avec informations client et événement

### Table `contact_menu_items`

Stocke les items de menu sélectionnés par le client

## 📁 Fichiers principaux

```
app/
├── Core/
│   ├── Database.php          # Connexion PDO singleton
│   └── Router.php             # Router avec support des paramètres
├── Models/
│   └── Contact.php            # Modèle CRUD pour les contacts
├── Controllers/
│   ├── ContactController.php  # Gestion du formulaire
│   └── AdminController.php    # Interface d'administration
└── Views/
    ├── pages/
    │   └── contact.php        # Formulaire avec sélection menu
    └── admin/
        ├── contacts.php       # Liste des demandes
        └── contact-detail.php # Détail d'une demande

resources/
├── js/
│   └── contact/
│       └── contactForm.js     # Gestion AJAX du formulaire
└── scss/
    └── pages/contact/
        └── _contact.scss      # Styles du formulaire

storage/
├── database.sql               # Script de création des tables
└── sample-data.sql           # Données de test
```

## 🧪 Test

```bash
# Tester l'installation
./test-installation.sh

# Démarrer le serveur
php -S localhost:8000 -t public

# Accéder au site
# - Contact : http://localhost:8000/contact
# - Admin : http://localhost:8000/admin/contacts
```

## 🔐 Sécurité

⚠️ **Important** : L'interface admin n'est pas protégée par défaut.

Pour la production, ajoutez :

- Un système d'authentification
- Une protection CSRF pour les formulaires
- Une limitation du taux de soumissions
- Un système de captcha

## 📧 Prochaines étapes

Améliorations suggérées :

1. 🔒 Ajouter une authentification admin
2. 📧 Envoyer des emails de notification
3. 📱 Rendre le formulaire mobile-friendly
4. 🔔 Notifications en temps réel
5. 📊 Dashboard avec graphiques

## ❓ Support

Problèmes courants :

- **Erreur 500** : Vérifiez la connexion BDD et que les tables existent
- **Formulaire ne s'envoie pas** : Recompilez les assets avec `npm run build`
- **Admin vide** : Vérifiez qu'il y a des données dans la BDD

---

**Développé pour Traiteur Passion** 🍽️
