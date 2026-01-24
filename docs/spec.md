# Projet : Partage de secrets & fichiers chiffrés côté client (Blade + Alpine + WebCrypto)

## Objectif

Construire une web-app auto-hébergeable (Laravel) permettant de **chiffrer côté navigateur** une chaîne ou un fichier, de **stocker uniquement le ciphertext** côté serveur, puis de **générer un lien de partage**. L’app doit aussi permettre l’envoi du lien par email, le suivi de lecture, l’annulation, et une administration sans compte via magic link.

Le projet vise un modèle **zero-knowledge** : le serveur ne doit jamais recevoir le secret en clair.

---

## Stack & contraintes techniques

- Backend : **Laravel 12** (PHP 8.4), Blade
- Front : **Alpine.js** + JS vanilla
- Crypto : **Web Crypto API** (natif navigateur)
- Stockage : DB + stockage fichiers (local/S3 compatible)
- Envoi mail : Laravel Mail + responsive HTML (style “Litmus-friendly”), DKIM via infra (Postfix/SES/Mailgun/etc.)
- Sécurité : HTTPS obligatoire, CSP recommandée, logs sans données sensibles
- Aucun compte utilisateur / pas d’auth classique

---

## Principes cryptographiques (non négociables)

1. Le navigateur génère la **clé de chiffrement** et chiffre localement.
2. Le serveur reçoit et stocke **uniquement** :
    - ciphertext + iv + salt + métadonnées
    - jamais le secret en clair, jamais la clé
3. La clé doit être transmise au destinataire via :
    - **fragment URL** (`#...`) recommandé (non envoyé au serveur)
    - ou clé séparée “out-of-band” (optionnel)
4. Algorithmes :
    - **AES-256-GCM** pour chiffrer
    - **PBKDF2** ou **HKDF** pour dériver une clé si passphrase optionnelle
    - `crypto.getRandomValues()` pour l’aléa
5. Encodages :
    - exporter `ciphertext`, `iv`, `salt` en Base64URL (pas Base64 standard)
6. Pas de crypto “maison”. Pas de libs crypto douteuses si WebCrypto suffit.

---

## Fonctionnalités (MVP + évolutions)

### 1) Création d’un secret (texte)

- Form :
    - champ “secret” (textarea)
    - options :
        - `usage_unique` (bool) : le secret devient illisible après première lecture réussie
        - `expire_at` (durée max) : ex. 1h, 1j, 7j, custom
        - `max_views` optionnel (ex. 1, 3, illimité)
        - `passphrase` optionnelle (si utilisée, dérivation côté client)
    - bouton : “Générer le lien”
- Résultat :
    - lien partage : `https://host/s/<token>#<key_material>`
    - bouton “copier”
    - option “envoyer par mail”

### 2) Création d’un secret (fichier)

- Upload fichier (drag&drop + input)
- Chiffrement côté client AVANT upload
- Upload du ciphertext (blob) + métadonnées (nom original, mime, taille)
- Télécharger côté destinataire après déchiffrement local (blob -> download)

### 3) Consultation / lecture

- Page `GET /s/{token}` :
    - récupère ciphertext + métadonnées depuis serveur
    - le JS lit la clé depuis `location.hash`
    - déchiffre localement
    - affiche :
        - secret texte (avec bouton copier)
        - ou fichier (bouton télécharger)
- Marquer “lu” côté serveur :
    - quand déchiffrement réussit, envoyer un `POST /s/{token}/read` (sans secret)
    - stocker `first_read_at`, `read_count`, `last_read_at`
- Si usage unique :
    - après premier `read` réussi, rendre le secret irréversible côté serveur :
        - supprimer ciphertext / ou marquer invalidé et supprimer blob
        - conserver seulement métadonnées (audit minimal)

### 4) Annulation / révocation

- Permettre d’annuler un secret avant expiration :
    - action “Revoke” via interface d’admin (magic link)
    - côté serveur : marque `revoked_at` + supprime ciphertext/blob
- Après révocation, page de lecture affiche “Secret indisponible”.

### 5) Administration sans compte (par URL + magic link email)

- Lors de la création, si l’utilisateur fournit un email “éditeur” (optionnel mais recommandé) :
    - stocker `creator_email` (hashé ou en clair selon besoin de renvoi magic link ; préférer en clair chiffré au repos si possible)
    - générer un “admin handle” non devinable : `admin_token`
- Interface admin accessible via :
    - URL : `GET /a/{admin_token}` (NE DOIT PAS donner accès direct sans vérif)
    - workflow :
        1. l’utilisateur saisit son email
        2. si email correspond au `creator_email`, envoyer un **magic link** à usage unique
        3. magic link contient un jeton court TTL (15 min)
        4. une fois validé : accès actions admin :
            - voir statut (lu/pas lu, dates, compteurs)
            - révoquer
            - prolonger expiration (si pas encore lu ou si non-unique)
            - renvoyer email au destinataire (si configuré)

### 6) Envoi email au destinataire depuis la plateforme

- Après création, possibilité d’envoyer un email au destinataire contenant le lien de partage
- Exigences :
    - template email responsive (Litmus-like) : table-based, inline CSS, fallback dark mode basique
    - DKIM : **à configurer via l’infra d’envoi**, documenter le setup (DNS DKIM, SPF, DMARC)
- Contenu email :
    - CTA “Ouvrir le secret”
    - info expiration / usage unique
    - avertissement : ne pas transférer le lien
    - option : inclure la clé séparément ? (par défaut non ; garder clé dans fragment URL)

### 7) Option PGP pour l’email (complément)

Objectif : permettre, si possible, d’envoyer **le message email** chiffré avec la clé publique du destinataire.

- Deux modes :
    1. **PGP du lien / contenu email** : chiffrer le corps email (ou une section) avec OpenPGP
    2. **PGP d’un “secret secondaire”** : envoyer la passphrase (si passphrase activée) via PGP
- Contraintes :
    - PGP côté serveur nécessite gérer clés publiques -> risque / complexité
    - PGP côté client possible mais emails sortent du navigateur => compliqué
- Proposition réaliste :
    - Implémenter côté serveur : si l’utilisateur fournit une clé publique ASCII armored, chiffrer une “note” ou la passphrase, et l’inclure en bloc PGP
    - Utiliser une lib OpenPGP côté serveur (ex. `singpolyma/openpgp-php` ou équivalent stable) + tests
- Annuaire :
    - option “lookup WKD” (Web Key Directory) si domaine le supporte
    - option “lookup keys.openpgp.org” (attention: politique et fiabilité)
    - Ces lookups doivent être **optionnels** et clairement signalés

---

## Idées pertinentes à ajouter (recommandées)

1. **Séparation lien / clé** :
    - mode “Split” : générer 2 éléments :
        - URL serveur : `https://host/s/<token>`
        - clé : `K...`
    - utile pour envoyer lien par mail et clé par SMS/Signal
2. **Rate limit & anti-abus** :
    - limiter créations par IP / par fenêtre temporelle
    - captcha optionnel (hCaptcha/Turnstile) en mode public
3. **CSP + SRI + build minimal** :
    - CSP stricte pour réduire XSS (XSS = compromission du secret côté client)
4. **Taille fichiers & streaming** :
    - chiffrement chunké pour gros fichiers (option avancée)
    - MVP : limiter ex. 50–200MB
5. **Journalisation minimale** :
    - logs sans IP complète (ou tronquée), sans tokens, sans secrets
6. **Suppression automatique** :
    - scheduler Laravel qui purge expirés + blobs orphelins
7. **Mode “burn after read” correct** :
    - ne marquer “lu” que si le client confirme un déchiffrement réussi
8. **Internationalisation FR/EN** (option)

---

## Modèle de données (proposition)

Table `secrets` :

- `id` (uuid)
- `token` (string unique, public)
- `type` enum: `text|file`
- `cipher_meta` json: `{alg, kdf, iv, salt, aad, version}`
- `ciphertext` longtext (pour text) OU `file_path` (pour blob chiffré)
- `filename` (nullable), `mime` (nullable), `size` (nullable)
- `usage_unique` bool
- `max_views` int nullable
- `read_count` int default 0
- `first_read_at`, `last_read_at` (nullable)
- `expire_at` datetime nullable
- `revoked_at` datetime nullable
- `creator_email` string nullable
- `admin_token` string unique (non exposé publiquement)
- `created_at`, `updated_at`

Table `magic_links` :

- `id`
- `secret_id`
- `email`
- `token_hash`
- `expire_at`
- `used_at`
- `created_at`

---

## Endpoints (proposition)

Public :

- `GET /` : formulaire create
- `POST /api/secrets` : reçoit ciphertext + meta + options -> retourne `{token, admin_token?}`
- `GET /s/{token}` : page de lecture (ne reçoit pas la clé)
- `GET /api/secrets/{token}` : retourne ciphertext + meta + statut (si accessible)
- `POST /api/secrets/{token}/read` : incrémente read_count + timestamps si déchiffrement ok (preuve minimale)
- `POST /api/secrets/{token}/revoke-request` : demande accès admin (email) -> envoie magic link
  Admin :
- `GET /a/{admin_token}` : page admin (demande email si pas de session magic)
- `POST /api/admin/{admin_token}/magic` : vérifie email, émet magic link
- `GET /magic/{token}` : valide le magic link, ouvre session courte
- `POST /api/admin/{admin_token}/revoke` : révoque
- `POST /api/admin/{admin_token}/extend` : étend expiration
- `POST /api/admin/{admin_token}/resend` : renvoie email destinataire

Sessions admin :

- Cookie httpOnly + TTL court OU session Laravel standard, scope secret unique

---

## Frontend : flux précis (à implémenter)

### Création

1. L’utilisateur saisit texte OU sélectionne un fichier
2. JS :
    - génère clé AES-GCM 256
    - génère iv (12 bytes)
    - (optionnel) dérive clé depuis passphrase via PBKDF2 (salt + iterations)
    - chiffre le payload
3. JS envoie au serveur :
    - ciphertext (base64url) ou blob chiffré (multipart)
    - meta: iv, salt, algoVersion, originalName/mime/size
    - options: expire_at, usage_unique, max_views, emails
4. Serveur répond :
    - `token` public
    - `admin_token` (non-public)
5. JS construit URL finale :
    - `shareUrl = /s/{token}#<key_material>`
    - key_material = export JWK ou raw key encodée base64url + paramètres KDF

### Lecture

1. Page charge ciphertext via API
2. JS lit `location.hash`, reconstruit clé
3. Déchiffre localement
4. Si succès -> affiche + POST `/read`
5. Si échec -> afficher erreur (clé invalide / expiré / révoqué)

---

## Sécurité : points à traiter explicitement

- XSS = critique (peut voler secret avant chiffrement ou après déchiffrement)
    - CSP stricte, éviter `unsafe-inline` si possible
    - minimiser dépendances
- Tokens non devinables (>= 128 bits)
- Rate limiting sur création et accès admin
- Protection brute force sur magic links
- Ne jamais logguer :
    - URL complète (car fragment non loggué mais prudence)
    - tokens, admin_token, ciphertext
- Téléchargements : headers sécurisés, pas de mime sniffing

---

## Tests à produire

- Unit :
    - services token generation
    - expiration logic
    - revoke logic
    - magic link issuance/validation
- Feature :
    - create secret (text/file) -> fetch -> revoke -> expired
    - usage unique : 1er read ok, second read KO
    - admin magic link flow
- (Optionnel) E2E (Playwright/Cypress) :
    - chiffrement/déchiffrement JS (sanity)

---

## Livrables attendus

1. Repo Laravel prêt à déployer
2. Docker compose optionnel (app + db + mailhog en dev)
3. Documentation :
    - config DKIM/SPF/DMARC (checklist)
    - variables d’env (mail, storage)
    - limites de taille fichiers
4. UI simple et propre (Blade + Alpine), copy-to-clipboard, états clairs

---

## Questions à trancher par défaut (choix recommandés)

- Default expiration : **7 jours**
- Default usage_unique : **true** pour “secret”, **false** pour “fichier” (à discuter)
- Taille max fichier : **100MB** MVP
- Mode clé : **fragment URL** par défaut + option split
- Passphrase : optionnelle (désactivée par défaut)

---

## Critères d’acceptation

- Le serveur ne reçoit jamais le secret en clair (vérifiable via logs + code)
- Les liens fonctionnent avec clé dans fragment
- Usage unique / expiration / révocation opérationnels
- Fichiers chiffrés/déchiffrés localement et téléchargeables
- Envoi email responsive OK (rendu correct clients majeurs)
- DKIM documenté + compatible
- Administration sans compte via magic link email fonctionnelle
- Statut “lu” visible côté admin

---

## Notes d’implémentation WebCrypto (guidelines)

- AES-GCM iv: 12 bytes
- `additionalData` (AAD) : inclure token/version pour éviter mixups (option)
- Export clé : `crypto.subtle.exportKey("raw", key)` puis base64url
- Passphrase KDF :
    - PBKDF2 SHA-256
    - iterations: 200k (ajuster perf)
- Prévoir versioning (`crypto_version`) pour migrations futures

---

## Ce que l’agent doit produire en plus (bonus)

- Un mode “prévisualiser” secret déchiffré sans l’envoyer
- Un “QR code du lien” pour mobile
- Un bouton “générer clé séparée” (split mode)
- Une page “statut public minimal” (sans admin) : expiré / révoqué / dispo (sans révéler lu/non-lu si privacy)

---

## Instruction finale à l’agent

Livrer une implémentation propre, sobre, auditable, avec priorité à la sécurité (XSS/CSP) et à la simplicité (Blade/Alpine/WebCrypto). Toute fonctionnalité impliquant l’accès au secret en clair côté serveur est interdite.
