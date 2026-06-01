# CHANGELOG — CardioPredict
# Journal de développement du projet

> **Usage** : Ce fichier retrace toutes les modifications apportées au projet.  
> Chaque nouvelle session de travail ajoute une entrée datée en haut du fichier.  
> Il sert de base pour rédiger la section "Évolution du projet" du rapport final.

---

## [v2.0.0] — 2026-05-29
### Session de développement majeure — 4 parties implémentées

---

### PARTIE 1 — Machine Learning (notebooks)

**Fichiers modifiés :**
- `notebooks/01_exploration_visualisation.ipynb` — réécriture complète (v2)
- `notebooks/02_modelisation_prediction.ipynb` — réécriture complète (v2)

**Modifications détaillées :**

#### Notebook 01 — Exploration & Visualisation
- Ajout des **features dérivées** dans la section 6 :
  - `bmi` = poids / (taille/100)²  clippé [10, 60]
  - `pulse_pressure` = ap_hi − ap_lo (pression pulsée, indicateur de rigidité artérielle)
  - `age_cat` = catégorisation de l'âge en tranches décennales (< 40, 40–50, 50–60, 60+)
- Ajout de la **section 15 — Traitement IQR** :
  - Méthode IQR expliquée et comparée aux alternatives (clip fixe, z-score)
  - Calcul des bornes IQR pour ap_hi, ap_lo, height, weight
  - Visualisation avant/après (4 histogrammes)
  - Statistiques comparatives (mean, std, max avant/après)
- Enrichissement de toutes les cellules Markdown avec des interprétations cliniques
- **Section 18 — Limites et pistes d'amélioration** ajoutée en fin de notebook
- Correction bug : suppression du walrus operator (`:=`) incompatible avec certains environnements → list comprehension standard

#### Notebook 02 — Modélisation & Prédiction
- **Ajout de XGBoost et LightGBM** dans la comparaison (5 modèles au total) :
  - `XGBClassifier(n_estimators, max_depth, learning_rate, eval_metric='logloss')`
  - `LGBMClassifier(n_estimators, max_depth, learning_rate, verbose=-1)`
  - Import sécurisé avec `try/except` (gracieux si non installé)
  - `build_models()` génère les pipelines pour les deux datasets
- **Traitement des outliers IQR** (section 2) :
  - `iqr_bounds()` sur ap_hi et ap_lo
  - Visualisation avant/après
  - `quick_rf_eval()` compare les métriques RF avant/après filtrage
- **Features dérivées intégrées à l'entraînement** (section 3) :
  - `FEATURES_CARDIO` inclut bmi, pulse_pressure, age_cat
  - Visualisations des 3 features dérivées par classe
- **GridSearchCV** implémenté (section 11) :
  - `run_grid_search()` avec grilles adaptées par type de modèle (RF, XGB, LGBM, LR, DT)
  - Comparaison des métriques avant/après optimisation sur le jeu de test
- **Validation croisée Heart** ajoutée (section 10) :
  - `StratifiedKFold(n_splits=5)` — nouveau sur Heart (déjà présent sur Cardio)
  - `LeaveOneOut` — adapté aux petits datasets, 302 modèles entraînés
  - Graphique comparatif KFold vs LOO vs test AUC
- **Courbes d'apprentissage** (section 12) :
  - `plot_learning_curve()` avec `StratifiedKFold`
  - Tracées pour Cardio et Heart
  - Sauvegarde : `site/assets/img/learning_curves.png`
- **Seuil de décision optimal** via F2-score (section 13) :
  - `find_optimal_threshold()` avec courbe Précision-Rappel
  - F2-score vectorisé : β=2 (rappel 2× plus important que précision)
  - Comparaison métriques seuil 0.50 vs seuil optimal
  - Pour Heart : inversion probabilité (cible encodée inversement)
- **Analyse SHAP** (section 14) :
  - `shap.TreeExplainer` pour RF/XGBoost/LightGBM
  - `shap.LinearExplainer` pour Régression Logistique
  - `summary_plot` beeswarm (distribution des valeurs SHAP)
  - Sauvegarde : `cardio_shap_summary.png`, `heart_shap_summary.png`
  - Import conditionnel avec `try/except`
- Toutes les sections enrichies avec des **cellules Markdown d'interprétation**
- **Section 18 — Limites et pistes d'amélioration** :
  - Qualité des données (biais, taille, population)
  - Limites méthodologiques (calibration, seuil sur test, grille restreinte)
  - Limites déploiement (réimplémentation manuelle, pas de monitoring)
  - Tableau priorisé des pistes d'amélioration

---

### PARTIE 2 — Visualisations interactives

**Fichiers modifiés :**
- `site/visualisations.php` — réécriture complète

**Modifications détaillées :**
- **Remplacement de tous les PNG statiques** par des graphiques Chart.js (0 `<img>` restant) :
  - Répartition cible → `doughnut` Chart.js avec pourcentages en tooltip
  - Importance features → `bar` horizontal Chart.js (couleur graduée)
  - Matrice de confusion → HTML/CSS custom (TP/TN/FP/FN colorés)
  - Courbes ROC → `line` Chart.js, 3 courbes superposées par dataset, approximation analytique `TPR = FPR^((1-AUC)/AUC)`
  - Comparaison modèles → `bar` groupé (Accuracy, F1, AUC)
- **Heatmap de corrélation interactive** (nouvel onglet "Corrélations") :
  - Table HTML PHP générée serveur avec couleurs interpolées (bleu→blanc→rouge)
  - Tooltip CSS au survol : valeur de corrélation + explication contextuelle
  - Deux heatmaps : variables clés Cardio (7×7) et Heart (7×7)
- **Graphique radar** comparant les 3 modèles :
  - 5 axes : Accuracy, Précision, Rappel, F1-Score, ROC-AUC
  - Deux radars : Cardio et Heart, dans l'onglet "Comparaison des modèles"
- **KPI Dashboard** en haut de page :
  - 4 cartes par dataset : total patients, % malades, âge moyen, meilleure AUC
  - `csv_kpis()` lit les fichiers CSV en PHP (streaming `fgetcsv`)
  - Cache JSON dans `site/tmp/` (expire si CSV modifié ou toutes les 24h)
- **4e onglet "Corrélations"** ajouté à la navigation
- Libellé Chart.js : `Chart.defaults.font.family` mis à jour → Inter

---

### PARTIE 3 — Nouvelles fonctionnalités (site PHP)

**Fichiers créés :**
- `site/api_predict.php` — endpoint AJAX pour le simulateur What-if

**Fichiers modifiés :**
- `site/ml/predict.py` — ajout de `compute_contributions()` et `_RF_IMPORTANCES`
- `site/prediction.php` — réécriture complète

**Modifications détaillées :**

#### predict.py
- Ajout du dictionnaire `_RF_IMPORTANCES` (importances approximatives RF Cardio)
- Ajout de `compute_contributions(dataset_key, cfg, model, x_scaled)` :
  - **LR** : contributions exactes `coef[i] × x_scaled[i]` (valeurs réelles du modèle)
  - **RF** : approximation `importance × sign(x_scaled[i])`
  - Inversion pour Heart (encodage cible inversé)
  - Retourne un dict `{feature: pourcentage_signé}`
- Ajout de `input_display_values` dans le JSON de sortie (valeurs brutes saisies)
- Le JSON de réponse inclut désormais `feature_contributions` et `input_display_values`

#### api_predict.php (nouveau)
- Accepte `POST { "mode": "cardio|heart", "values": {...} }`
- Valide le mode avec `in_array()`
- Écrit dans un fichier temporaire unique (`bin2hex(random_bytes(8))`)
- Appelle `predict.py` via `shell_exec`
- Nettoie le fichier temporaire après exécution
- Retourne le JSON identique à `predict.py`

#### prediction.php — nouvelles sections (après prédiction)
- **Facteurs de risque individuels** : graphique Chart.js horizontal
  - Barres rouges : contribution positive au risque
  - Barres vertes : facteur protecteur
  - Triées par contribution absolue décroissante
- **Tableau comparatif sains/malades** :
  - `$MEANS` : moyennes pré-calculées pour Cardio (11 features) et Heart (13 features)
  - Colonne "Proximité" colorée : vert si proche des sains, rouge si proche des malades
- **Simulateur What-if** :
  - Sélection de la variable à modifier (select + input adapté au type)
  - Appel AJAX debounced (700ms) vers `api_predict.php`
  - Affichage du nouveau score et du delta coloré (↑ rouge / ↓ vert / ~ gris)
- **Export PDF** (jsPDF + jsPDF-AutoTable) :
  - En-tête coloré selon le niveau de risque (vert/orange/rouge)
  - Profil patient en tableau
  - Métriques du modèle
  - Facteurs contributifs
  - Disclaimer médical + footer CardioPredict
- **Historique session PHP** :
  - `$_SESSION['predictions']` : 10 dernières prédictions maximum
  - Tableau affichant date, mode, âge, modèle, probabilité, badge de risque coloré
  - Bouton "Effacer" avec confirmation JS

---

### PARTIE 4 — Refonte design & structure

**Fichiers modifiés :**
- `site/assets/css/style.css` — réécriture complète
- `site/assets/js/app.js` — mise à jour polices canvas
- `site/partials_header.php` — hamburger menu + lien À propos
- `site/index.php` — hero SVG + compteurs animés
- `site/prediction.php` — formulaire multi-étapes + jauge + radar

**Fichiers créés :**
- `site/apropos.php`

**Modifications détaillées :**

#### style.css
- **Police Inter** : `@import url('https://fonts.googleapis.com/css2?family=Inter:...')` en ligne 1
- `font-family: 'Inter', Arial, sans-serif` sur `body`, formulaires, boutons
- **Variables CSS risque** : `--risk-low: #10B981`, `--risk-mod: #F59E0B`, `--risk-high: #EF4444`
- Badges `.risk-badge.faible/modere/eleve` avec fond, texte et bordure assortis
- **Barre de progression multi-étapes** : `.step-progress`, `.step-dot`, `.step-connector`, `.step-label`
- **Jauge circulaire** : `.gauge-wrapper`, `.gauge-center-text`, `.gauge-pct`
- **Menu hamburger mobile** : `.nav-toggle` avec 3 spans animés (rotation 45°)
- Navigation mobile : `nav { display: none }` → `nav.nav-open { display: flex }` à < 760px
- **Animations** : `@keyframes heartbeat`, `@keyframes fadeUp`, `@keyframes dashFlow`
- **Page À propos** : `.team-grid`, `.team-card`, `.dataset-card`, `.pipeline-row`
- Responsive complet : breakpoints 950px (2→1 col), 760px (hamburger), 600px (1 col form)

#### index.php
- **Illustration SVG inline** avec :
  - Cœur SVG avec animation `heartbeat` (CSS keyframes)
  - Ligne ECG animée (`stroke-dashoffset` → `drawECG` + `dashFlow`)
  - Badges AUC, observations, modèles positionnés autour du cœur
- **Compteurs animés** :
  - `animateInt()` et `animateDec()` avec easing `(1-t)^3`
  - Déclenchement via `IntersectionObserver` (seuil 30%)
  - Compteurs : 70 302 observations, 5 modèles, AUC 0.871
- Nouveau lien vers `apropos.php` dans les actions Hero

#### prediction.php — refonte formulaire et résultat
- **Formulaire multi-étapes** :
  - `$STEPS` PHP : définit les 3 étapes par mode (Cardio : Profil/Mesures/Mode de vie ; Heart : Profil/Examen/Effort)
  - HTML : `.step-progress` avec dots, connecteurs et labels
  - Champs HTML : `.step-fields[data-step=N]`, `active` class gérée par JS
  - Navigation : `nextStep()` / `prevStep()` / validation champ par champ
  - Si POST : `currentStep = N_STEPS` (affichage direct de l'étape finale)
- **Dashboard résultat** (panneau droit) :
  - **Jauge semi-circulaire** : Chart.js doughnut `circumference=180, rotation=270`, `cutout='72%'`
  - Couleur de la jauge selon le niveau de risque
  - **Badge de risque** avec emoji vert/orange/rouge
  - **Radar "profil de risque"** : contributions groupées par catégorie anatomique (4-5 axes)
- Déplacement des sections post-prédiction hors du panneau fixe (full-width)

#### apropos.php (nouveau)
Sections :
1. Hero avec badges technologies
2. Objectifs (test classique / test avancé / pipeline)
3. Datasets (fiches détaillées Cardio + Heart, avertissements, liens Kaggle)
4. Résultats des modèles (tableau complet)
5. Équipe (4 membres avec avatars)
6. Sources et références académiques
7. Avertissement médical

#### partials_header.php
- Ajout du bouton `.nav-toggle` avec 3 `<span>` animables
- Ajout du lien `apropos.php` dans `<nav>`
- Script `toggleNav()` + fermeture au clic extérieur

---

### PARTIE 5 — Corrections qualité & finitions

**Fichiers modifiés :**
- `site/prediction.php` — correction XSS
- `site/visualisations.php` — correction police Chart.js
- `site/assets/js/app.js` — correction police canvas
- `notebooks/01_exploration_visualisation.ipynb` — correction walrus operator
- `README.md` — réécriture complète

**Corrections :**

| # | Fichier | Problème | Correction |
|---|---------|---------|------------|
| 1 | `prediction.php` | `<?= $error ?>` sans échappement → XSS | `<?= htmlspecialchars($error) ?>` |
| 2 | `visualisations.php` | `Chart.defaults.font.family = 'Arial'` | `= "'Inter', Arial, sans-serif"` |
| 3 | `app.js` | `ctx.font = '12px Arial'` (×2) | `ctx.font = "12px 'Inter', Arial"` |
| 4 | `notebook 01, cell-50` | Walrus operator `:=` dans dict literal | List comprehension standard |
| 5 | `README.md` | Incomplet (v1 uniquement) | Réécrit : install, architecture, toutes les features v2 |

---

## [v1.0.0] — 2025-2026 (version initiale)
### Projet initial

**Fichiers créés :**
- `notebooks/01_exploration_visualisation.ipynb` — EDA basique (3 modèles, LR/DT/RF)
- `notebooks/02_modelisation_prediction.ipynb` — modélisation basique
- `site/index.php` — page d'accueil
- `site/prediction.php` — formulaire simple en 1 étape
- `site/visualisations.php` — affichage PNG statiques
- `site/methode.php` — méthodologie
- `site/ml/predict.py` — inférence sans dépendances (JSON models)
- `site/ml/models/cardio_model.json` — Random Forest exporté
- `site/ml/models/heart_model.json` — Régression Logistique exportée
- `site/ml/metadata/cardio_model_info.json` + `heart_model_info.json`
- `site/assets/css/style.css` — design initial (Arial, palette bleue)
- `site/assets/js/app.js` — mini graphique canvas accueil
- `site/data/cardio.csv` + `heart.csv`
- `README.md` — documentation initiale
- `rapport/le_rapport.pdf`

**Performances modèles v1 :**
- Cardio : Random Forest — Accuracy 0.732, ROC-AUC 0.798
- Heart : Régression Logistique — Accuracy 0.803, ROC-AUC 0.871

**Particularité technique :**
- `predict.py` sans aucune dépendance externe (Python stdlib pur)
- Modèles exportés en JSON avec structure d'arbre sérialisée manuellement
- Déploiement sur hébergement mutualisé sans accès pip

---

## Template pour les prochaines entrées

```markdown
## [vX.Y.Z] — YYYY-MM-DD
### Description courte de la session

**Fichiers modifiés :**
- `chemin/fichier.ext` — description

**Fichiers créés :**
- `chemin/fichier.ext` — description

**Modifications détaillées :**
- Point 1
- Point 2

**Métriques avant/après (si applicable) :**
| Modèle | AUC avant | AUC après |
```

---

*Ce fichier est mis à jour à chaque session de développement.*  
*Projet : CardioPredict — L3 MIASHS, Université Paul Valéry Montpellier — 2025-2026*
