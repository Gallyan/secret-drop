# Secret Drop

Application de partage de secrets et fichiers chiffrés côté client. Le serveur ne voit jamais les données en clair.

## Principe Zero-Knowledge

- Le navigateur génère la clé et chiffre localement (AES-256-GCM)
- Le serveur stocke uniquement le ciphertext + métadonnées
- La clé est transmise via le fragment URL (`#...`) qui n'est jamais envoyé au serveur
- Passphrase optionnelle avec dérivation PBKDF2

## Fonctionnalités

- **Secrets texte** : messages chiffrés avec copie facile
- **Fichiers chiffrés** : upload/download avec chiffrement côté client
- **Usage unique** : destruction après première lecture
- **Expiration** : 1h, 1j, 7j ou 30j
- **Limite de lectures** : max_views configurable
- **Révocation** : annulation immédiate via admin_token
- **Passphrase** : protection supplémentaire optionnelle

## Stack technique

- **Backend** : Laravel 12, PHP 8.4
- **Frontend** : Alpine.js 3.14, Tailwind CSS 4.0
- **Crypto** : Web Crypto API (navigateur)
- **Base de données** : SQLite

## Installation

```bash
# Cloner le repo
git clone https://github.com/Gallyan/secret-drop.git
cd secret-drop

# Installation et configuration
composer setup

# Lancer en développement
composer dev
```

La commande `composer setup` exécute :
- Installation des dépendances PHP et npm
- Génération de la clé d'application
- Migration de la base de données
- Build des assets

## Commandes

### Développement

```bash
composer dev      # Serveur + queue + logs + Vite
composer test     # Tests PHPUnit
npm run build     # Build production
```

### Artisan

```bash
# Nettoyer les secrets expirés/révoqués/consommés
php artisan secrets:clean

# Mode dry-run (affiche sans supprimer)
php artisan secrets:clean --dry-run
```

Cette commande supprime :
- Les secrets expirés (`expire_at` dépassé)
- Les secrets révoqués (`revoked_at` défini)
- Les secrets ayant atteint leur limite de lectures
- Les secrets à usage unique déjà lus

## API

### Endpoints publics

| Méthode | URL | Description |
|---------|-----|-------------|
| `GET` | `/` | Page de création |
| `POST` | `/api/secrets` | Créer un secret |
| `GET` | `/s/{token}` | Page de lecture |
| `GET` | `/api/secrets/{token}` | Récupérer les métadonnées + ciphertext |
| `POST` | `/api/secrets/{token}/read` | Confirmer la lecture (après déchiffrement) |
| `GET` | `/s/{token}/download` | Télécharger un fichier chiffré |

### Endpoints admin

| Méthode | URL | Description |
|---------|-----|-------------|
| `POST` | `/api/secrets/{adminToken}/revoke` | Révoquer un secret |

## Sécurité

### Ce que le serveur ne voit jamais
- Le secret en clair
- La clé de chiffrement
- Le fragment URL

### Mesures de protection
- CSP stricte avec nonce
- Headers de sécurité (HSTS, X-Frame-Options, etc.)
- Sanitization des logs (pas de tokens, pas de secrets)
- Tokens cryptographiquement sécurisés (128+ bits)

### Données stockées
- `ciphertext` : contenu chiffré (base64url)
- `cipher_meta` : iv, salt, version de l'algorithme
- Métadonnées : type, expiration, compteurs

## Configuration

Variables d'environnement principales :

```env
APP_URL=https://your-domain.com
APP_ENV=production

# Base de données
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite

# Mail (optionnel)
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@example.com
```

## Scheduler

Pour la suppression automatique des secrets expirés, ajouter au crontab :

```cron
* * * * * cd /path/to/secret-drop && php artisan schedule:run >> /dev/null 2>&1
```

Puis dans `routes/console.php` :

```php
Schedule::command('secrets:clean')->daily();
```

## Tests

```bash
# Tous les tests
composer test

# Un test spécifique
php artisan test --filter=ShowSecretTest

# Avec couverture
php artisan test --coverage
```

## Licence

MIT
