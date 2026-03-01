# Optivac Consent — Plugin WordPress

Gestion des consentements newsletter et offres promotionnelles via l'API REST Optivac.

## Installation

1. Copier le dossier `optivac-consent` dans `/wp-content/plugins/`
2. Activer le plugin depuis **Extensions → Extensions installées**
3. Aller dans **Optivac → Settings** et renseigner :
   - **API Base URL** : URL de l'API Optivac (ex: `https://ws-test-optivac.makeessens.fr`)
   - **API Key** : clé d'authentification (laisser vide si non requise)

## Fonctionnement

### Front-end (modal)
À chaque chargement de page, si un email est détecté (utilisateur connecté ou champ email visible), le plugin interroge l'API pour vérifier les consentements manquants. Une modale s'affiche si un consentement est attendu.

### WooCommerce
Des cases à cocher sont ajoutées au formulaire de checkout. Le consentement est envoyé à l'API lors de la validation de la commande. Les choix sont également stockés dans les meta de la commande et de l'utilisateur.

### Admin
Le menu **Optivac** permet de rechercher les consentements d'un email via l'API en temps réel.

## Endpoints API couverts

| Méthode | Endpoint | Usage |
|---------|----------|-------|
| GET | `/consents/status/all` | Statuts newsletter + offres |
| GET | `/consents/status` | Statut par type (query param) |
| GET | `/consents/status/{type}` | Statut par type (path param) |
| GET | `/external/brevo/check` | Vérification côté Brevo |
| GET | `/external/wordpress/check` | Vérification côté WordPress |
| POST | `/consents/validate` | Soumettre un consentement |

## Hooks AJAX

| Action | Paramètres POST | Description |
|--------|-----------------|-------------|
| `optivac_status` | `email`, `nonce` | Retourne le statut des consentements |
| `optivac_validate` | `email`, `newsletter`, `offers`, `policyVersion`, `nonce` | Enregistre les consentements |

## Cache

Les statuts sont mis en cache 15 minutes via la Transients API WordPress. Le cache est invalidé automatiquement après un `validate`.

## Structure

```
optivac-consent/
├── src/
│   ├── Ajax/          ConsentController
│   ├── Admin/         AdminMenu, ConsentAdminPage, ConsentListTable, SettingsPage
│   ├── API/           ConsentApi
│   ├── Core/          Autoloader, Container, Plugin
│   ├── Domain/        ConsentManager, ValueObject/ConsentStatus
│   ├── Http/          HttpClient, ApiException
│   ├── Infrastructure/ AuditLogger, Cache, EmailResolver, Installer, Logger, WooCommerceIntegration
│   ├── Presentation/  AssetsManager
│   └── Support/       Constants
├── assets/
│   ├── css/consent.css
│   └── js/consent.js
├── optivac-consent.php
└── uninstall.php
```
