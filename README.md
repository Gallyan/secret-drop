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

# DKIM (optionnel, si votre SMTP ne signe pas)
# MAIL_DKIM_DOMAIN=example.com
# MAIL_DKIM_SELECTOR=secretdrop
# MAIL_DKIM_PRIVATE_KEY_PATH=storage/dkim/private.key
```

### Configuration DKIM (optionnel)

Si votre serveur SMTP ne signe pas les emails en DKIM, l'application peut le faire :

```bash
# Générer la clé privée
mkdir -p storage/dkim
openssl genrsa -out storage/dkim/private.key 2048
chmod 600 storage/dkim/private.key

# Extraire la clé publique pour le DNS
openssl rsa -in storage/dkim/private.key -pubout -out storage/dkim/public.key
cat storage/dkim/public.key | grep -v "PUBLIC KEY" | tr -d '\n'
```

Puis configurer dans `.env` :
```env
MAIL_DKIM_DOMAIN=votredomaine.com
MAIL_DKIM_SELECTOR=secretdrop
MAIL_DKIM_PRIVATE_KEY_PATH=storage/dkim/private.key
```

Et ajouter l'enregistrement DNS TXT :
```
secretdrop._domainkey.votredomaine.com  TXT  "v=DKIM1; k=rsa; p=VOTRE_CLE_PUBLIQUE"
```

Pour plus de détails (SPF, DMARC, OVH), voir [docs/email-configuration.md](docs/email-configuration.md).

## Scheduler

Pour la purge automatique des données, ajouter au crontab :

```cron
* * * * * cd /path/to/secret-drop && php artisan schedule:run >> /dev/null 2>&1
```

Tâches planifiées (configurées dans `routes/console.php`) :

| Commande | Fréquence | Description |
|----------|-----------|-------------|
| `secrets:clean` | Toutes les heures | Supprime les secrets expirés, révoqués, consommés et les magic links |
| `secrets:clean-blobs` | Quotidien | Supprime les fichiers orphelins (sans secret correspondant) |

Les deux commandes supportent l'option `--dry-run` pour prévisualiser les suppressions.

## Logs Apache (zero-knowledge)

Pour respecter le principe zero-knowledge, les logs Apache ne doivent pas contenir les tokens des URLs sensibles. Utilisez un format de log personnalisé :

```apache
# Dans /etc/apache2/conf-available/secret-drop-log.conf

# Format qui masque les tokens dans les URLs sensibles
LogFormat "%h %l %u %t \"%m %U\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" secretdrop

# %U = URI sans query string (le fragment # n'est jamais envoyé au serveur)
# Les tokens dans /s/{token} restent visibles, voir ci-dessous pour les masquer
```

Pour masquer complètement les tokens, utilisez `mod_rewrite` avec une variable d'environnement :

```apache
<VirtualHost *:443>
    ServerName secret-drop.example.com
    DocumentRoot /var/www/secret-drop/public

    # Masquer les tokens dans les logs
    RewriteEngine On
    RewriteCond %{REQUEST_URI} ^/s/[^/]+
    RewriteRule ^/s/(.*)$ - [E=SANITIZED_URI:/s/[TOKEN]]

    RewriteCond %{REQUEST_URI} ^/api/secrets/[^/]+
    RewriteRule ^/api/secrets/(.*)$ - [E=SANITIZED_URI:/api/secrets/[TOKEN]]

    RewriteCond %{REQUEST_URI} ^/admin/verify/[^/]+
    RewriteRule ^/admin/verify/(.*)$ - [E=SANITIZED_URI:/admin/verify/[TOKEN]]

    RewriteCond %{REQUEST_URI} ^/superadmin/verify/[^/]+
    RewriteRule ^/superadmin/verify/(.*)$ - [E=SANITIZED_URI:/superadmin/verify/[TOKEN]]

    # Format de log sécurisé
    LogFormat "%h %l %u %t \"%m %{SANITIZED_URI}e\" %>s %b" secretdrop_safe
    SetEnvIf Request_URI "." SANITIZED_URI=%{REQUEST_URI}

    CustomLog ${APACHE_LOG_DIR}/secret-drop-access.log secretdrop_safe
    ErrorLog ${APACHE_LOG_DIR}/secret-drop-error.log
</VirtualHost>
```

Note : Le fragment URL (`#...` contenant la clé de chiffrement) n'est **jamais** envoyé au serveur par le navigateur, donc il n'apparaît jamais dans les logs serveur.

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
