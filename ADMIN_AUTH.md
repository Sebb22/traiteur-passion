# 🔐 Système d'authentification Admin - Traiteur Passion

## ✨ Nouvelles fonctionnalités

Un système d'authentification sécurisé a été implémenté pour protéger l'interface d'administration.

### Accès public

- ✅ Page de contact : `/contact`
- ✅ Menu : `/menu`
- ✅ Accueil : `/`
- ✅ À propos : `/a-propos`
- ✅ Soumission de formulaire : `POST /contact`

### Accès administrateur (protégé)

- 🔒 Liste des demandes : `/admin/contacts`
- 🔒 Détail d'une demande : `/admin/contacts/{id}`
- 🔒 Export CSV : `/admin/contacts/export`

## 🔑 Identifiants par défaut

**Utilisateur :** `admin`
**Mot de passe :** `ChangeMe!2026`

⚠️ **À PERSONNALISER IMMÉDIATEMENT** en production

## 🚀 Configuration

### 1. Variables d'environnement (.env)

```env
# Administrateur
ADMIN_USER=admin
ADMIN_PASSWORD_HASH=$2y$10$ksKrVZ8biqaJgEla5/wV.ez9z0utjv2cwp/9JClRlWM8OwXx2U0gG
```

### 2. Changer le mot de passe

#### Générer un nouveau hash

```bash
# Bash/Linux
php -r 'echo password_hash("VotreMotDePasseSecurise", PASSWORD_DEFAULT), PHP_EOL;'
```

#### Ou en Python

```python
import bcrypt
password = "VotreMotDePasseSecurise"
hashed = bcrypt.hashpw(password.encode(), bcrypt.gensalt()).decode()
print(hashed)
```

#### Mettre à jour dans .env

```env
ADMIN_PASSWORD_HASH=<hash_généré>
```

## 🔗 URLs de connexion

- **Formulaire de connexion** : http://localhost:8080/admin/login
- **Se déconnecter** : Bouton "Déconnexion" dans l'interface admin

## 🛡️ Sécurité implémentée

✅ **Sessions sécurisées**

- Cookie sécurisé (HttpOnly, SameSite=Lax)
- Régénération d'ID après connexion
- Destruction complète à la déconnexion

✅ **Authentification forte**

- Hash des mots de passe avec bcrypt (PASSWORD_DEFAULT)
- Comparaison constante (hash_equals) pour éviter les timing attacks
- Délai de 1 seconde après échec de connexion (anti-brute force)

✅ **Protection des routes**

- Vérification d'authentification sur toutes les pages admin
- Redirection vers `/admin/login` si non authentifié
- Protection CSRF via action sur formulaires

## 📝 Flux de connexion

```
1. Utilisateur accède à /admin/contacts
   ↓
2. AdminAuth::requireAuth() vérifie la session
   ↓
3. Si non authentifié → Redirection vers /admin/login
   ↓
4. Formulaire de connexion
   ↓
5. POST /admin/login avec username + password
   ↓
6. Vérification avec AdminAuth::attemptLogin()
   ↓
7. ✅ Succès → Session créée → Redirection vers /admin/contacts
   ou
   ❌ Échec → Affichage "Identifiants invalides"
   ↓
8. Déconnexion via /admin/logout
   ↓
9. Session détruite → Redirection vers /admin/login
```

## 🧪 Tester

### 1. Accéder à l'admin non authentifié

```
Ouvre http://localhost:8080/admin/contacts
→ Tu seras redirigé vers /admin/login
```

### 2. Se connecter

```
Utilisateur : admin
Mot de passe : ChangeMe!2026
→ Tu accéderas à la liste des demandes
```

### 3. Vérifier la session

```
Les cookies de session sont sécurisés:
- HttpOnly (impossible d'y accéder via JS)
- SameSite=Lax (protection CSRF)
- Secure (HTTPS en prod)
```

## 🔴 Améliorer en production

### 1. Changer les identifiants

```bash
# Générer un nouveau mot de passe fort
php -r 'echo password_hash("MDP_TRES_SECURISE_AVEC_CARACTERES_SPECIAUX!2026", PASSWORD_DEFAULT), PHP_EOL;'

# Mettre à jour .env
ADMIN_USER=votreNomUtilisateur
ADMIN_PASSWORD_HASH=<nouveau_hash>
```

### 2. Ajouter une limite de tentatives

```php
// À ajouter dans AuthController::login()
if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 5) {
    // Bloquer pendant 15 minutes
}
```

### 3. Envoyer une notification par email

```php
// À ajouter après une connexion réussie
mail(ADMIN_EMAIL, "Connexion à l'admin", "Connexion à " . date('d/m/Y H:i'));
```

### 4. Logger les accès

```php
// À ajouter dans AdminAuth::attemptLogin()
error_log("Admin login attempt: $username at " . date('Y-m-d H:i:s'));
```

### 5. Forcer HTTPS en production

```php
// Dans bootstrap.php
if (Config::get('APP_ENV') === 'prod' && empty($_SERVER['HTTPS'])) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

## 📊 Architecture du code

### Classes créées

- **AdminAuth.php** : Gestion des sessions et authentification
- **AuthController.php** : Pages de connexion/déconnexion

### Routes ajoutées

```
GET  /admin/login      → Formulaire de connexion
POST /admin/login      → Traitement du login
POST /admin/logout     → Déconnexion
```

### Middleware implicite

```php
AdminAuth::requireAuth();  // À chaque route /admin
```

## ❓ FAQ

**Q: Où sont stockés les mots de passe?**
A: En variable d'environnement dans `.env`, jamais en base de données.

**Q: Puis-je créer plusieurs comptes admin?**
A: Actuellement non. À ajouter pour un vrai projet.

**Q: Les sessions persistent sur rechargement?**
A: Oui, les cookies PHP gèrent la persistance.

**Q: Combien de temps avant expiration?**
A: Jusqu'à la fermeture du navigateur (lifetime=0), ou configurable.

**Q: Peut-on accéder directement à la page sans passer par le login?**
A: Non, `AdminAuth::requireAuth()` redirige.

---

**Installation terminée ! Ton interface admin est maintenant sécurisée.** 🔐
