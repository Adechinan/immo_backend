# API Immobilier — Laravel 12

API RESTful complète pour une plateforme immobilière (vente & location de biens).

---

## 📦 Installation

```bash
composer create-project laravel/laravel immobilier
cd immobilier

# Copier les fichiers du projet dans le dossier Laravel
# Installer Sanctum pour l'authentification
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Configurer .env (DB_DATABASE, DB_USERNAME, DB_PASSWORD...)
cp .env.example .env
php artisan key:generate

# Migrations
php artisan migrate

# Lancer le serveur (port 8010 — doit matcher NUXT_PUBLIC_API_BASE du frontend)
php artisan serve --port=8010
# ou : composer serve
```

---

## 🔒 Certificats SSL manquants (PHP/Windows)

Si un appel HTTPS sortant échoue avec `cURL error 60: SSL certificate ...
unable to get local issuer certificate` (ex: connexion Google), c'est que
PHP sur ce poste n'a pas de bundle de certificats racine configuré — ça
affecte tous les appels HTTPS sortants (Google, FedaPay...), pas un
endpoint en particulier. `AuthController::google()` contourne ça
temporairement en local (`'verify' => !app()->isLocal()`), mais la vraie
correction :

1. Télécharger https://curl.se/ca/cacert.pem
2. Trouver le php.ini utilisé : `php --ini`
3. Y ajouter :
   ```ini
   curl.cainfo = "C:\chemin\vers\cacert.pem"
   openssl.cafile = "C:\chemin\vers\cacert.pem"
   ```
4. Relancer `php artisan serve`

---

## 🗄️ Structure de la base de données

| Table              | Description                               |
|--------------------|-------------------------------------------|
| `users`            | Utilisateurs (acheteur, locataire, admin) |
| `biens`            | Propriétés immobilières                   |
| `tofs`             | Photos liées à un bien                    |
| `options`          | Options disponibles (piscine, parking…)   |
| `bien_option`      | Pivot Bien ↔ Options                      |
| `statuts`          | Statuts d'un bien (à vendre, à louer, vendu, loué, réservé) et leur couleur |
| `achats`           | Transactions d'achat                      |
| `locations`        | Contrats de location                      |
| `mensualites`      | Paiements mensuels d'une location         |
| `demandes`         | Demandes de contact sur un bien           |

---

## 🔐 Authentification (Sanctum)

| Méthode | Endpoint         | Description         |
|---------|------------------|---------------------|
| POST    | /api/auth/register | Créer un compte   |
| POST    | /api/auth/login    | Se connecter      |
| POST    | /api/auth/logout   | Se déconnecter 🔒 |
| GET     | /api/auth/me       | Profil courant 🔒 |

---

## 🏠 Biens

| Méthode | Endpoint              | Auth | Description                        |
|---------|-----------------------|------|------------------------------------|
| GET     | /api/biens            | -    | Liste avec filtres                 |
| GET     | /api/biens/{id}       | -    | Détail d'un bien                   |
| POST    | /api/biens            | 🔒   | Créer un bien                      |
| PUT     | /api/biens/{id}       | 🔒   | Modifier un bien                   |
| DELETE  | /api/biens/{id}       | 🔒   | Supprimer un bien                  |

### Filtres disponibles (GET /api/biens)
```
?ville=Paris
?prix_min=100000&prix_max=500000
?statut=disponible
?type=vente           (vente | location)
?chambres=3
?surface_min=50
?per_page=20
```

### Payload POST/PUT
```json
{
  "titre": "Appartement T3 lumineux",
  "surface": 75,
  "description": "Bel appartement...",
  "pieces": 3,
  "chambres": 2,
  "etage": 4,
  "adresse": "12 rue de la Paix",
  "ville": "Paris",
  "codePostal": "75001",
  "statut": "disponible",
  "prix": 350000,
  "type_bien": "vente",
  "options": [1, 2, 3]
}
```

---

## 📸 Photos (Tofs)

| Méthode | Endpoint                      | Auth | Description      |
|---------|-------------------------------|------|------------------|
| GET     | /api/biens/{id}/tofs          | -    | Liste des photos |
| POST    | /api/biens/{id}/tofs          | 🔒   | Ajouter une photo|
| DELETE  | /api/biens/{id}/tofs/{tof_id} | 🔒   | Supprimer        |

---

## 💰 Achats

| Méthode | Endpoint           | Auth | Description        |
|---------|--------------------|------|--------------------|
| GET     | /api/achats        | 🔒   | Mes achats         |
| POST    | /api/achats        | 🔒   | Enregistrer achat  |
| GET     | /api/achats/{id}   | 🔒   | Détail             |
| DELETE  | /api/achats/{id}   | 🔒   | Supprimer          |

### Payload POST
```json
{
  "bien_en_vente_id": 1,
  "dateAchat": "2024-06-15"
}
```

---

## 🏡 Locations

| Méthode | Endpoint              | Auth | Description          |
|---------|-----------------------|------|----------------------|
| GET     | /api/locations        | 🔒   | Mes locations        |
| POST    | /api/locations        | 🔒   | Créer une location   |
| GET     | /api/locations/{id}   | 🔒   | Détail               |
| DELETE  | /api/locations/{id}   | 🔒   | Supprimer            |

---

## 💳 Mensualités

| Méthode | Endpoint                                      | Auth |
|---------|-----------------------------------------------|------|
| GET     | /api/locations/{id}/mensualites               | 🔒   |
| POST    | /api/locations/{id}/mensualites               | 🔒   |
| GET     | /api/locations/{id}/mensualites/{mens_id}     | 🔒   |
| PUT     | /api/locations/{id}/mensualites/{mens_id}     | 🔒   |
| DELETE  | /api/locations/{id}/mensualites/{mens_id}     | 🔒   |

---

## 📨 Demandes de contact

| Méthode | Endpoint                    | Auth | Description          |
|---------|-----------------------------|------|----------------------|
| POST    | /api/biens/{id}/demandes    | -    | Envoyer une demande  |
| GET     | /api/demandes               | 🔒   | Toutes les demandes  |
| GET     | /api/demandes/{id}          | 🔒   | Détail               |
| DELETE  | /api/demandes/{id}          | 🔒   | Supprimer            |

---

## ⚙️ Options

| Méthode | Endpoint             | Auth | Description    |
|---------|----------------------|------|----------------|
| GET     | /api/options         | -    | Liste           |
| POST    | /api/options         | 🔒   | Créer          |
| PUT     | /api/options/{id}    | 🔒   | Modifier       |
| DELETE  | /api/options/{id}    | 🔒   | Supprimer      |

---

## 📁 Structure des fichiers

```
app/
├── Models/
│   ├── User.php
│   ├── Bien.php
│   ├── Tof.php
│   ├── Option.php
│   ├── BiensEnVente.php
│   ├── BiensEnLocation.php
│   ├── Achat.php
│   ├── Location.php
│   ├── Mensualite.php
│   └── Demande.php
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── BienController.php
│   │   ├── TofController.php
│   │   ├── OptionController.php
│   │   ├── AchatController.php
│   │   ├── LocationController.php
│   │   ├── MensualiteController.php
│   │   └── DemandeController.php
│   ├── Requests/         (validation)
│   └── Resources/        (transformation JSON)
database/
└── migrations/           (9 migrations)
routes/
└── api.php
```
