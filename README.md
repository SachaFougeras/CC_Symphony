# Hotel Management System

Ce projet est une application Symfony pour la gestion des hôtels, des chambres et des réservations. Il utilise MongoDB comme base de données et inclut des fonctionnalités telles que la recherche, la pagination et la gestion des utilisateurs.

---

## **Prérequis**

Avant de commencer, assurez-vous d'avoir les outils suivants installés sur votre machine :

- PHP 8.1 ou supérieur
- Composer
- Symfony CLI
- MongoDB (local ou distant)
- Node.js et npm (pour gérer les assets si nécessaire)

---

## **Installation**

1. Clonez le dépôt :
   ```bash


2. Installez les dépendances PHP :
   ```bash
   composer install
   ```

3. Configurez les variables d'environnement dans le fichier `.env` :
   ```properties
   MONGODB_URL="mongodb://localhost:27017"
   MONGODB_DB=hotel
   ```

4. Assurez-vous que MongoDB est en cours d'exécution :
   ```bash
   sudo service mongod start
   ```

5. Lancez le serveur Symfony :
   ```bash
   symfony server:start
   ```

6. Accédez à l'application dans votre navigateur à l'adresse :
   ```
   http://127.0.0.1:8000
   ```

---

## **Fonctionnalités**

### **1. Gestion des hôtels**
- Ajouter, modifier et supprimer des hôtels.
- Rechercher des hôtels par nom ou ville.
- Pagination des hôtels (10 par page).
- Afficher les détails d'un hôtel, y compris ses chambres.

### **2. Gestion des chambres**
- Ajouter, modifier et supprimer des chambres.
- Afficher les chambres par hôtel.
- Pagination des chambres (10 par page).
- Champs disponibles pour une chambre :
  - **Numéro** : Identifiant unique de la chambre.
  - **Type** : Single, Double, Suite, etc.
  - **Capacité** : Nombre de lits.
  - **Étage** : Étage où se trouve la chambre.
  - **Prix** : Prix par nuit.

### **3. Réservations**
- Réserver une chambre pour une période donnée.
- Afficher les réservations d'un utilisateur connecté.
- Gestion des conflits de réservation (vérification des disponibilités).

### **4. Gestion des utilisateurs**
- Liste des utilisateurs avec pagination.
- Rechercher des utilisateurs par nom ou email.
- Ajouter, modifier et supprimer des utilisateurs.
- Gestion des rôles (par exemple, `ROLE_ADMIN`, `ROLE_USER`).

### **5. Recherche**
- Rechercher des hôtels par nom ou ville.
- Rechercher des utilisateurs par nom ou email.

### **6. Système de pagination**
- Pagination pour les hôtels (10 par page).
- Pagination pour les chambres (10 par page).
- Pagination pour les utilisateurs (10 par page).

### **7. Authentification et sécurité**
- Connexion et déconnexion des utilisateurs.
- Gestion des rôles pour restreindre l'accès à certaines fonctionnalités (par exemple, seuls les administrateurs peuvent ajouter ou supprimer des hôtels).

### **8. Réinitialisation du mot de passe**
- Réinitialisation du mot de passe via un code PIN.
- Formulaire pour définir un code PIN pour les utilisateurs connectés.
- Réinitialisation sécurisée avec vérification du code PIN.

---

## **Configuration**

### **Base de données MongoDB**
Le projet utilise MongoDB comme base de données. Assurez-vous que MongoDB est configuré correctement dans le fichier `.env` :

```properties
MONGODB_URL="mongodb://localhost:27017"
MONGODB_DB=hotel
```

### **Mailer**
Pour envoyer des emails (par exemple, pour la réinitialisation de mot de passe), configurez le transporteur dans le fichier `.env` :

```properties
MAILER_DSN=smtp://<EMAIL>:<MOT_DE_PASSE>@smtp.gmail.com:587
```

---

## **Commandes utiles**

### **1. Vider le cache**
```bash
php bin/console cache:clear
```

### **2. Vérifier la configuration**
```bash
php bin/console debug:config
```

### **3. Lancer le serveur Symfony**
```bash
symfony server:start
```

### **4. Vérifier les routes**
```bash
php bin/console debug:router
```

---

## **Structure du projet**

### **Principaux dossiers**
- **`src/Controller`** : Contient les contrôleurs de l'application.
- **`src/Document`** : Contient les entités MongoDB (documents).
- **`templates/`** : Contient les fichiers Twig pour les vues.
- **`config/`** : Contient les fichiers de configuration (YAML).

---

## **Contributions**

Les contributions sont les bienvenues ! Si vous souhaitez contribuer, veuillez suivre ces étapes :

1. Forkez le projet.
2. Créez une branche pour votre fonctionnalité :
   ```bash
   git checkout -b feature/ma-fonctionnalite
   ```
3. Commitez vos modifications :
   ```bash
   git commit -m "Ajout de ma fonctionnalité"
   ```
4. Poussez votre branche :
   ```bash
   git push origin feature/ma-fonctionnalite
   ```
5. Ouvrez une Pull Request.

---

## **Licence**

Ce projet est sous licence MIT. Consultez le fichier `LICENSE` pour plus d'informations.