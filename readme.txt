Configuration d'un nouveau projet :

1) Mise à jour du fichier .env

2) Mise à jour des fixtures (rôles, utilisateurs, etc.)

3) Mise à jour graphique :
B - Mise à jour favicon : /public/assets/favicon/*
C - Mise à jour images/logos : /public/assets/img/* (Attention au sous-dossier /email/)

4) Installation :
php -d memory_limit=-1 composer.phar install
php -d memory_limit=-1 bin/console doctrine:schema:update --force
php -d memory_limit=-1 bin/console doctrine:fixtures:load
modifier le chemin du projet dans webpack.config.js
yarn encore dev


Une documentation accessible uniquement par un utilisateur "ROLE_LIMEO" est disponible via le menu.

5) Installation API ( SSL keys Lexik)
php bin/console lexik:jwt:generate-keypair

6) Modifier durée de validité des JWT  dans config/packages/lexik_jwt_authentication.yaml : token_ttl
7) pour générer un JWT  : curl -X POST -H "Content-Type: application/json" http://localhost/socle-sf5-v2/public/api/login_check -d '{"username":"user@demo.com","password":"123"}'
8) Pour modifier le payload du JWT : src/Listener/JWTCreatedListener et ajouter données
9) Rafraichir le token : /api/token/refresh : payload: {"refresh_token": "refresh_token}
