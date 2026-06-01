# CardioPredict — Prédiction du risque cardiovasculaire

> Projet de Machine Learning — Licence 3 MIASHS | Université Paul Valéry, Montpellier  
> Science des données 4 — Année universitaire 2025-2026

[![Site en ligne](https://img.shields.io/badge/Site-cardio--predict.fr-1F5FBF?style=flat-square)](https://cardio-predict.fr)
[![Python](https://img.shields.io/badge/Python-3.11-blue?style=flat-square&logo=python)](https://python.org)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php)](https://php.net)

---

## Description

**CardioPredict** est une plateforme web de prédiction du risque cardiovasculaire par apprentissage automatique. Elle propose deux niveaux d'analyse :

- **Test classique** : variables générales (âge, poids, tension, habitudes de vie) — dataset Cardio
- **Test avancé** : variables cliniques précises issues d'un bilan cardiologique — dataset Heart

Le site est déployé et accessible publiquement : **[cardio-predict.fr](https://cardio-predict.fr)**

---

## Membres du groupe

| Nom | Prénom |
|-----|--------|
| EL BASRI | Samy |
| ALI RACHEDI | Djibril |
| BOUSQUET | Arthur |
| BERETTI-PRENANT | Esteban |

---

## Datasets

| Dataset | Source | Observations | Variables |
|---------|--------|-------------|-----------|
| Cardiovascular Disease | [Kaggle — Sulianova](https://www.kaggle.com/datasets/sulianova/cardiovascular-disease-dataset) | 70 000 | 11 + 3 dérivées |
| Heart Disease | [Kaggle — johnsmith88](https://www.kaggle.com/datasets/johnsmith88/heart-disease-dataset) | 302 (après déduplication) | 13 |

---

## Résultats des modèles

| Dataset | Modèle retenu | Accuracy | ROC-AUC | F1-Score |
|---------|--------------|----------|---------|---------|
| Cardiovascular | Random Forest / XGBoost | 0,732+ | **0,798+** | 0,718+ |
| Heart | Régression Logistique | 0,803 | **0,871** | 0,824 |

Évaluation sur split 80/20 stratifié. Validation croisée 5 plis (Cardio) + StratifiedKFold-5 et Leave-One-Out (Heart).

---

## Structure du projet

```
projet-ml-cardiovasculaire/
│
├── notebooks/
│   ├── 01_exploration_visualisation.ipynb   # EDA, features dérivées, outliers IQR
│   └── 02_modelisation_prediction.ipynb     # 5 modèles, GridSearchCV, SHAP, F2, learning curves
│
├── site/
│   ├── index.php                            # Accueil — hero SVG, compteurs animés
│   ├── prediction.php                       # Formulaire multi-étapes + dashboard résultat
│   ├── visualisations.php                   # Graphiques interactifs Chart.js + heatmap
│   ├── methode.php                          # Méthodologie détaillée
│   ├── apropos.php                          # Présentation projet, datasets, équipe
│   ├── api_predict.php                      # Endpoint AJAX (simulateur What-if)
│   ├── partials_header.php                  # Navigation avec menu hamburger mobile
│   ├── partials_footer.php
│   ├── assets/
│   │   ├── css/style.css                    # Design Inter + palette risque + responsive
│   │   ├── js/app.js
│   │   └── img/                             # Graphiques générés par les notebooks
│   ├── ml/
│   │   ├── predict.py                       # Inférence sans dépendances externes
│   │   ├── models/cardio_model.json         # Random Forest exporté en JSON
│   │   ├── models/heart_model.json          # Régression Logistique en JSON
│   │   └── metadata/                        # Métriques et ordre des features
│   └── data/
│       ├── cardio.csv
│       └── heart.csv
│
└── README.md
```

---

## Installation locale

### Prérequis

| Composant | Version minimale | Usage |
|-----------|-----------------|-------|
| PHP | 8.0+ | Serveur web (XAMPP, Laragon, MAMP…) |
| Python | 3.9+ | `predict.py` appelé via `shell_exec()` |
| Navigateur moderne | — | Chart.js, Inter font |

**Pour les notebooks uniquement (optionnel) :**
```bash
pip install scikit-learn xgboost lightgbm shap pandas numpy matplotlib seaborn
```

### Étapes d'installation

```bash
# 1. Cloner le dépôt
git clone https://github.com/samy954/projet-ml-cardiovasculaire.git
cd projet-ml-cardiovasculaire

# 2. Placer le dossier site/ dans le répertoire web
#    XAMPP  → C:\xampp\htdocs\site\
#    Laragon → C:\laragon\www\site\
#    MAMP   → /Applications/MAMP/htdocs/site/

# 3. Démarrer Apache depuis votre panneau de contrôle (XAMPP/Laragon/MAMP)

# 4. Ouvrir dans le navigateur
#    http://localhost/site/

# Alternative rapide : serveur PHP intégré (sans XAMPP)
cd site
php -S localhost:8080
# Puis ouvrir http://localhost:8080
```

### Vérifier Python

```bash
# Windows
py -3.11 --version

# Linux / macOS
python3 --version
```

> ⚠️ Sur hébergement mutualisé, `shell_exec()` doit être activé dans `php.ini`.

---

## Architecture technique

### Pipeline de prédiction

```
[1] Formulaire PHP  →  [2] JSON temporaire  →  [3] predict.py
                                                      │
                                              [4] Modèle JSON
                                                      │
                                              [5] Résultat + contributions JSON
                                                      │
                                              [6] Dashboard PHP (jauge, radar, SHAP)
```

### Contrainte hébergement mutualisé

Les modèles sont exportés en JSON et réimplémentés en **Python standard pur** (`json`, `math`, `sys`, `pathlib`) — zéro dépendance externe. Cela permet le déploiement sur hébergement mutualisé sans accès pip.

### Endpoint AJAX (simulateur What-if)

`api_predict.php` accepte `POST { mode, values }` et retourne le même format JSON que `predict.py`. Utilisé par le simulateur What-if côté JavaScript.

---

## Notebooks

| Notebook | Contenu |
|----------|---------|
| `01_exploration_visualisation.ipynb` | Chargement, nettoyage, features dérivées (IMC, pression pulsée, catégorie âge), traitement IQR outliers, visualisations EDA, heatmap corrélations, limites |
| `02_modelisation_prediction.ipynb` | 5 modèles (LR/DT/RF/XGBoost/LightGBM), GridSearchCV, validation croisée (5-fold + LOO), courbes d'apprentissage, seuil F2 optimal, analyse SHAP, limites |

---

## Fonctionnalités du site

### Page Prédiction (`prediction.php`)
- Formulaire **multi-étapes** (3 étapes avec progress bar)
- **Dashboard résultat** : jauge semi-circulaire + radar des facteurs de risque
- **Contributions individuelles** : quels facteurs influencent CE patient
- **Tableau comparatif** : profil patient vs moyennes sains/malades du dataset
- **Simulateur What-if** : modifier un paramètre → impact en temps réel (AJAX)
- **Export PDF** : rapport complet via jsPDF
- **Historique session** : tableau des 10 dernières prédictions

### Page Visualisations (`visualisations.php`)
- Tous les graphiques en **Chart.js interactif** (aucun PNG statique)
- **Heatmap de corrélation** interactive (hover = valeur + explication)
- **Radar** comparant les 3 modèles sur 5 métriques
- **KPI cards** calculées dynamiquement depuis les CSV (avec cache)
- Courbes ROC comparatives pour les 2 datasets

### Page À propos (`apropos.php`)
- Présentation du projet, des datasets, de la méthodologie
- Résultats complets des modèles
- Membres de l'équipe
- Sources et références académiques

---

## Encodage cible Heart — point critique

> Dans le dataset johnsmith88/heart-disease-dataset, `target = 1` = **SAIN** et `target = 0` = **MALADE**  
> (encodage inversé par rapport à l'intuition).  
> `predict.py` possède `invert_proba: True` pour ce dataset → il retourne `1 - prob_model`.  
> Ne jamais supprimer cette inversion.

---

## Avertissement médical

CardioPredict est un outil **pédagogique** basé sur des données publiques. Les prédictions sont des **estimations statistiques** et ne constituent en aucun cas un diagnostic médical. Consultez un professionnel de santé pour tout avis médical.

---

## Licence

Projet académique — Université Paul Valéry, Montpellier 3 — 2025-2026
