# Revue — Secret Drop (Laravel 12)

Date : 2026-01-25

Cette revue est centrée sur sécurité, zero‑knowledge, robustesse, UX/a11y et bonnes pratiques Laravel. Les points sont classés par criticité.

## Critique

1) ~~**CSP trop permissive en production (risque XSS ⇒ fuite de secret)**~~
**Fichiers** : `app/Http/Middleware/SecurityHeaders.php`
**Statut** : CORRIGÉ - CSP stricte en production (nonce uniquement), unsafe-eval/unsafe-inline seulement en local pour Vite. Header X-XSS-Protection obsolète retiré.

2) ~~**Session fixation sur magic links (admin/superadmin)**~~
**Fichiers** : `app/Http/Controllers/AdminController.php`, `app/Http/Controllers/SuperAdminController.php`
**Statut** : CORRIGÉ - `session()->regenerate()` après validation du magic link, `invalidate()` + `regenerateToken()` sur logout.

3) ~~**Balises SEO/OG/canonical sur pages sensibles**~~
**Fichier** : `resources/views/layouts/app.blade.php`
**Statut** : CORRIGÉ - Balises canonical/OG/Twitter désactivées pour `/s/*`, `/admin/*`, `/superadmin/*`. noindex automatique sur ces pages.

## Élevée

4) ~~**Aucun rate limit sur lecture / download**~~
**Fichiers** : `routes/api.php`, `routes/web.php`
**Statut** : CORRIGÉ - Throttle 60/min sur fetch, confirmRead et download. Throttle 10/min sur revoke.

5) ~~**Headers cache manquants pour pages/API secrets**~~
**Fichiers** : `app/Http/Middleware/NoCacheHeaders.php`, `routes/api.php`, `routes/web.php`
**Statut** : CORRIGÉ - Middleware `no.cache` ajouté sur `/s/{token}`, `/s/{token}/download`, `/api/secrets/{token}`, `/api/secrets/{token}/read`.

6) **Paramètres prod dangereux dans `.env`**
**Fichier** : `.env`
**Constat** : `APP_DEBUG=true`, `LOG_LEVEL=debug`, `APP_URL=http://localhost`.
**Risque** : fuites d'informations + URLs erronées en emails.
**Action** : hardening pour prod (debug=false, log_level=warning, APP_URL=https…).
**Note** : `.env.example` mis à jour avec les valeurs de production commentées.

7) ~~**Cookies de session pas forcés en secure**~~
**Fichiers** : `.env.example`
**Statut** : CORRIGÉ - `SESSION_SECURE_COOKIE=true` et `SESSION_SAME_SITE=strict` documentés dans `.env.example` (à décommenter en production).

## Moyenne

8) ~~**Upload fichier charge tout en mémoire**~~
**Fichier** : `app/Services/SecretStorageService.php`
**Statut** : CORRIGÉ - Utilisation de `writeStream()` avec `fopen()` au lieu de `getContent()`.

9) ~~**Destruction incomplète des métadonnées fichier**~~
**Fichier** : `app/Models/Secret.php`
**Statut** : CORRIGÉ - `destroyContent()` nullifie maintenant `file_path`, `filename`, `mime`, `size` pour les fichiers.

10) ~~**Header X‑XSS‑Protection obsolète**~~
**Fichier** : `app/Http/Middleware/SecurityHeaders.php`
**Statut** : CORRIGÉ au point 1 - header retiré.

## Faible / Backlog

11) **A11y : gestion du focus et des erreurs**  
**Fichiers** : `resources/views/secrets/show.blade.php`, `resources/js/components/secret-viewer.js`  
**Action** : ajouter `aria-live` pour les erreurs, focus management après déchiffrement/erreur.

12) **Surface JS translations globale**  
**Fichier** : `resources/views/layouts/app.blade.php`  
**Action** : limiter à ce qui est nécessaire côté JS si besoin de minimiser surface.

13) **CI/CD**  
**Action** : pipeline minimal `composer test`, `vendor/bin/pint --dirty`, `composer audit`, et build front si nécessaire.

## Notes positives

- Middleware de sanitation des logs (`app/Logging/SanitizeProcessor.php`, `app/Http/Middleware/SanitizeRequestLogging.php`) est un très bon socle zero‑knowledge.
- Crypto WebCrypto côté client est propre et versionnée (`resources/js/crypto.js`).
- Headers de download (no‑sniff, no‑store) déjà bien traités (`app/Services/SecretStorageService.php`).

## Priorités recommandées

1) CSP stricte + headers cache no‑store pour secrets.  
2) Regénération de session + logout hardening.  
3) Throttling lecture/download.  
4) Streaming upload + nettoyage métadonnées.
