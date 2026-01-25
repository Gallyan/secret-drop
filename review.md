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

4) **Aucun rate limit sur lecture / download**  
**Fichiers** : `routes/api.php`, `routes/web.php`  
**Constat** : `/api/secrets/{token}`, `/api/secrets/{token}/read`, `/s/{token}/download` ne sont pas throttlés.  
**Risque** : bruteforce du token et DoS.  
**Action** : ajouter un middleware de throttle (même léger) sur ces routes.

5) **Headers cache manquants pour pages/API secrets**  
**Fichiers** : `app/Http/Middleware/SecurityHeaders.php`, `app/Http/Controllers/SecretsController.php`  
**Constat** : le download force `no-store`, mais pas la page `/s/{token}` ni l’API `/api/secrets/{token}`.  
**Risque** : contenu sensible ou métadonnées en cache navigateur/proxy.  
**Action** : ajouter `Cache-Control: no-store` et `Pragma: no-cache` sur ces endpoints.

6) **Paramètres prod dangereux dans `.env`**  
**Fichier** : `.env`  
**Constat** : `APP_DEBUG=true`, `LOG_LEVEL=debug`, `APP_URL=http://localhost`.  
**Risque** : fuites d’informations + URLs erronées en emails.  
**Action** : hardening pour prod (debug=false, log_level=info/warn, APP_URL=https…).

7) **Cookies de session pas forcés en secure**  
**Fichiers** : `.env`, `config/session.php`  
**Constat** : `SESSION_SECURE_COOKIE` absent dans `.env`.  
**Risque** : cookie envoyé en HTTP si mal configuré.  
**Action** : `SESSION_SECURE_COOKIE=true` en prod + vérifier `SESSION_SAME_SITE`.

## Moyenne

8) **Upload fichier charge tout en mémoire**  
**Fichier** : `app/Services/SecretStorageService.php`  
**Constat** : `UploadedFile::getContent()` charge tout en RAM.  
**Risque** : mémoire élevée sur fichiers volumineux, vecteur DoS.  
**Action** : stream vers disk (ex. `readStream()` + `put()` stream).

9) **Destruction incomplète des métadonnées fichier**  
**Fichier** : `app/Models/Secret.php`  
**Constat** : `destroyContent()` ne nettoie pas `file_path`, `filename`, `mime`, `size`.  
**Risque** : conservation de métadonnées sensibles après destruction.  
**Action** : nullifier ces champs lors de la destruction.

10) **Header X‑XSS‑Protection obsolète**  
**Fichier** : `app/Http/Middleware/SecurityHeaders.php`  
**Constat** : header legacy présent.  
**Risque** : faux sentiment de sécurité.  
**Action** : retirer.

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
