<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>API Immobilier - Laravel</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 800px;
            width: 100%;
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
        }

        .api-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .endpoint {
            background: #e9ecef;
            padding: 10px 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin: 5px 0;
        }

        .method {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            color: white;
            font-weight: bold;
            margin-right: 10px;
            min-width: 60px;
            text-align: center;
        }

        .get { background: #28a745; }
        .post { background: #007bff; }
        .put { background: #ffc107; color: #333; }
        .delete { background: #dc3545; }

        .status {
            text-align: center;
            padding: 20px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            color: #155724;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏠 API Immobilier</h1>
        
        <div class="status">
            ✅ API Laravel 12 - En ligne et fonctionnelle
        </div>

        <div class="api-info">
            <h3>📡 Endpoints Principaux</h3>
            
            <div class="endpoint">
                <span class="method get">GET</span>/api/biens
                <small>Liste des biens avec filtres</small>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>/api/biens/{id}
                <small>Détail d'un bien</small>
            </div>
            
            <div class="endpoint">
                <span class="method post">POST</span>/api/auth/register
                <small>Inscription</small>
            </div>
            
            <div class="endpoint">
                <span class="method post">POST</span>/api/auth/login
                <small>Connexion</small>
            </div>
        </div>

        <div class="api-info">
            <h3>🔧 Installation</h3>
            <pre>
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
            </pre>
        </div>

        <div class="api-info">
            <h3>📚 Documentation</h3>
            <p>Consultez le fichier README.md pour la documentation complète de l'API.</p>
        </div>
    </div>
</body>
</html>
