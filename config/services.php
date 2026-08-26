<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides a sane default
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // "Se connecter avec Google" (web + mobile) — voir AuthController::google().
    // Le client_id doit matcher celui utilisé côté frontend/mobile pour générer
    // l'id_token (Google exige que le champ "aud" corresponde exactement).
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
    ],

    // Notifications push (FCM) — voir App\Services\FirebaseNotificationService.
    // `credentials` pointe vers le JSON de compte de service généré dans la
    // console Firebase (Paramètres du projet > Comptes de service > Générer
    // une nouvelle clé privée). Jamais commité : placé hors du repo (ou dans
    // un dossier gitignored) et référencé ici par chemin absolu ou relatif à
    // base_path().
    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS'),
    ],

    // Compte FedaPay de la plateforme LoggyImmo elle-même — utilisé
    // uniquement pour encaisser le paiement de l'abonnement des
    // propriétaires (cf. AbonnementController). Les paiements de loyers/achats
    // reçus par les propriétaires, eux, utilisent les clés personnelles
    // stockées sur `users.fedapay_public_key`/`fedapay_secret_key`, pas
    // celles-ci. `base_url` bascule sandbox/live selon la clé fournie.
    'fedapay' => [
        'public_key' => env('FEDAPAY_PUBLIC_KEY'),
        'secret_key' => env('FEDAPAY_PRIVATE_KEY'),
        'base_url'   => env('FEDAPAY_BASE_URL', 'https://sandbox-api.fedapay.com'),
    ],

    // Abonnement mensuel donnant accès à la réception de paiement — un seul
    // palier pour l'instant (cf. AbonnementController).
    'abonnement' => [
        'montant_mensuel' => env('ABONNEMENT_MONTANT_MENSUEL', 5000),
    ],

    // Recherche en langage naturel ("un logement à Womey, budget 20 000") —
    // cf. BienController::rechercheIA. Modèle rapide/économique : cette
    // tâche n'est qu'une extraction de champs structurés, pas une génération
    // de texte, un modèle "haiku" suffit largement et coûte peu par requête.
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model'   => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
    ],

    // Alternative gratuite à Anthropic pour la même tâche d'extraction —
    // gemini-3.7-flash est gratuit sur le "free tier" de Google AI Studio
    // (voir https://ai.google.dev/gemini-api/docs/pricing), avec une limite
    // de requêtes/jour assez basse (quelques centaines à ~1000/jour selon le
    // projet) : suffisant pour démarrer, à surveiller si le volume grandit.
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model'   => env('GEMINI_MODEL', 'gemini-3.7-flash'),
    ],

    // Groq — free tier sans carte bancaire (30 req/min, ~14 400 req/jour au
    // niveau de l'organisation, voir console.groq.com/docs). API compatible
    // OpenAI, inférence très rapide (LPU) : bon relais si Gemini est à quota.
    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model'   => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    ],

    // Mistral "La Plateforme" — palier gratuit "Experiment" (1 req/s, ~1
    // milliard de tokens/mois), nécessite vérification par téléphone côté
    // console Mistral. Troisième relais avant de retomber sur Anthropic
    // (payant) en tout dernier recours.
    'mistral' => [
        'api_key' => env('MISTRAL_API_KEY'),
        'model'   => env('MISTRAL_MODEL', 'mistral-small-latest'),
    ],

    // Ordre des fournisseurs pour la recherche IA — chacun est essayé à son
    // tour tant que le précédent échoue (clé absente, erreur réseau/API,
    // quota gratuit épuisé), pour que la fonctionnalité continue à
    // fonctionner même si un seul fournisseur atteint sa limite du jour.
    // Ordre par défaut : les 3 fournisseurs gratuits d'abord, Anthropic
    // (payant) en filet de sécurité final.
    'ai_search' => [
        'providers' => array_filter(array_map('trim', explode(
            ',',
            env('AI_SEARCH_PROVIDERS', 'gemini,groq,mistral,anthropic')
        ))),
    ],

];
