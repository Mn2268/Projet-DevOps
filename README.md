# Projet DevSecOps – Pipeline CI/CD sécurisé

## 1. Description
Ce projet consiste à mettre en place un pipeline CI/CD DevSecOps automatisé et sécurisé.
L’application utilisée est une application web PHP (authentification + gestion + base de données SQL).

L’objectif est d’automatiser :
- la récupération du code depuis GitHub
- l’analyse de qualité du code
- la détection de vulnérabilités
- la conteneurisation Docker
- le déploiement automatique
- un monitoring basique

---

## 2. Architecture technique
Outils utilisés :
- **GitHub** : gestion du code source et collaboration
- **Jenkins** : automatisation CI/CD
- **SonarQube** : analyse statique (qualité + vulnérabilités) + Quality Gate bloquant
- **Snyk** : scan de vulnérabilités (dépendances + image Docker)
- **Docker** : conteneurisation et déploiement
- **Prometheus & Grafana** : monitoring basique

---

## 3. Structure du dépôt
├── Jenkinsfile
├── Dockerfile
├── README.md
├── docs/
│ ├── pipeline.md
│ ├── sonar.md
│ ├── snyk.md
│ └── screenshots/
├── voty.sql
├── index.php
├── auth.php
└── ...

---

## 4. Pipeline CI/CD (Jenkins)
Le pipeline est déclenché automatiquement à chaque push sur GitHub.

Étapes principales :
1. Checkout du code depuis GitHub
2. Analyse SonarQube (SAST)
3. Validation du Quality Gate (pipeline bloquant si échec)
4. Scan Snyk des dépendances (SCA)
5. Build de l’image Docker
6. Scan Snyk de l’image Docker
7. Déploiement Docker automatique
8. Vérification de santé (health check)

---

## 5. Exécution en local (sans Docker)
Prérequis :
- PHP installé
- Serveur Apache ou XAMPP
- MySQL/MariaDB

Importer la base de données :
- importer le fichier `voty.sql` dans phpMyAdmin

---

## 6. Exécution avec Docker
Build :
```bash
docker build -t devsecops-php-app .
