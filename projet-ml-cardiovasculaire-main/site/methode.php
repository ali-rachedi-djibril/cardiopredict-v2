<?php $page = 'methode'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CardioPredict - Méthode</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .step-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 22px; }
    @media (max-width: 700px) { .step-grid { grid-template-columns: 1fr; } }
    .step-card { background: #fff; border: 1px solid var(--line); border-radius: var(--radius);
                 box-shadow: var(--shadow); padding: 22px 24px; }
    .step-num  { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), #5ea3ff);
                 color: #fff; font-weight: 800; font-size: 15px; display: flex;
                 align-items: center; justify-content: center; margin-bottom: 10px; }
    .step-card h3 { margin: 0 0 8px; color: var(--primary-dark); font-size: 16px; }
    .step-card p, .step-card ul { color: var(--muted); font-size: 14px; margin: 0; }
    .step-card ul { padding-left: 18px; }
    .metrics-compare { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 14px; }
    .metrics-compare th { background: var(--primary-soft); color: var(--primary-dark); padding: 9px 14px; text-align: left; }
    .metrics-compare td { padding: 8px 14px; border-bottom: 1px solid var(--line); color: var(--text); }
    .metrics-compare tr:last-child td { border-bottom: none; }
    .highlight-row td { font-weight: 700; color: var(--primary-dark); background: #f0f7ff; }
    .tag { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
    .tag-best { background: #d1fae5; color: #065f46; }
    .tag-ok   { background: #dbeafe; color: #1e40af; }
    .alert-box { background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 0 12px 12px 0;
                 padding: 12px 18px; font-size: 14px; color: #78350f; margin-top: 14px; }
  </style>
</head>
<body>
  <?php include 'partials_header.php'; ?>

  <main>
    <div class="container">
      <section class="hero">
        <h1>Méthode suivie pour le projet</h1>
        <p>
          Cette page détaille les choix techniques effectués à chaque étape : de la sélection des données
          à l'intégration du modèle dans le site, en passant par les décisions de prétraitement et les
          résultats comparatifs des modèles testés.
        </p>
      </section>

      <!-- Étapes -->
      <div class="step-grid">

        <div class="step-card">
          <div class="step-num">1</div>
          <h3>Sélection des datasets</h3>
          <p>Deux datasets publics issus de Kaggle ont été retenus :</p>
          <ul>
            <li><strong>Cardio</strong> (Sulianova) — 70 000 observations, 11 variables cliniques et comportementales, données russes anonymisées.</li>
            <li><strong>Heart</strong> (johnsmith88) — concaténation de 4 sources UCI (Cleveland, Hungarian, Suisse, VA), 1 025 lignes brutes.</li>
          </ul>
        </div>

        <div class="step-card">
          <div class="step-num">2</div>
          <h3>Analyse exploratoire</h3>
          <p>Avant tout entraînement, nous avons vérifié :</p>
          <ul>
            <li>Distribution des classes (équilibre ≈ 50/50 dans les deux cas).</li>
            <li>Valeurs manquantes (absentes dans les deux datasets).</li>
            <li>Outliers sur la tension artérielle (<em>ap_hi</em>, <em>ap_lo</em>) dans le dataset cardio.</li>
            <li>Corrélation entre variables (heatmap).</li>
          </ul>
        </div>

        <div class="step-card">
          <div class="step-num">3</div>
          <h3>Prétraitement</h3>
          <ul>
            <li>Suppression des colonnes d'identifiant (<em>id</em>, <em>id_heart</em>, <em>dataset_id</em>).</li>
            <li>Correction des décimales (virgule → point) sur <em>weight</em> et <em>oldpeak</em>.</li>
            <li><strong>Déduplication du dataset heart</strong> : 723 lignes dupliquées supprimées (1 025 → 302 uniques). Sans cette étape, les mêmes observations se retrouvent en train et en test, ce qui crée un <em>data leakage</em> et gonfle les métriques à 1,0.</li>
            <li>Pipeline sklearn : imputation médiane + standardisation (StandardScaler).</li>
          </ul>
        </div>

        <div class="step-card">
          <div class="step-num">4</div>
          <h3>Split train / test</h3>
          <ul>
            <li>Séparation 80 % / 20 % avec stratification (même proportion de classes dans train et test).</li>
            <li>Graine aléatoire fixée à 42 pour la reproductibilité.</li>
            <li>Cardio : 56 000 train / 14 000 test.</li>
            <li>Heart : 241 train / 61 test (après déduplication).</li>
          </ul>
        </div>

        <div class="step-card">
          <div class="step-num">5</div>
          <h3>Modèles testés</h3>
          <p>Trois classifieurs ont été comparés sur chaque dataset :</p>
          <ul>
            <li><strong>Régression Logistique</strong> — modèle linéaire, interprétable, bonne régularisation L2 par défaut.</li>
            <li><strong>Arbre de décision</strong> — max_depth limité (5–6) pour éviter le surapprentissage.</li>
            <li><strong>Random Forest</strong> — 200 arbres, max_depth 6–10 selon le dataset, parallélisé (n_jobs=-1).</li>
          </ul>
        </div>

        <div class="step-card">
          <div class="step-num">6</div>
          <h3>Critère de sélection</h3>
          <p>
            Le critère principal est la <strong>ROC-AUC</strong> (Area Under Curve), car elle mesure la
            capacité de discrimination indépendamment du seuil de décision — utile quand le coût des faux
            négatifs (malade non détecté) est élevé. En cas d'égalité, le F1-Score départage.
          </p>
        </div>

      </div>

      <!-- Résultats comparatifs -->
      <div class="section-box">
        <h2>Résultats comparatifs sur l'ensemble de test</h2>

        <h3 style="color:var(--primary-dark);margin-top:18px;">Dataset Cardio (14 000 observations de test)</h3>
        <table class="metrics-compare">
          <tr><th>Modèle</th><th>Accuracy</th><th>Précision</th><th>Rappel</th><th>F1-Score</th><th>ROC-AUC</th><th></th></tr>
          <tr><td>Régression Logistique</td><td>0,714</td><td>—</td><td>—</td><td>—</td><td>0,778</td><td><span class="tag tag-ok">OK</span></td></tr>
          <tr><td>Arbre de décision</td><td>0,728</td><td>—</td><td>—</td><td>—</td><td>0,790</td><td><span class="tag tag-ok">OK</span></td></tr>
          <tr class="highlight-row"><td>Random Forest</td><td>0,732</td><td>0,756</td><td>0,684</td><td>0,718</td><td>0,798</td><td><span class="tag tag-best">Retenu ✓</span></td></tr>
        </table>

        <h3 style="color:var(--primary-dark);margin-top:22px;">Dataset Heart (61 observations de test, après déduplication)</h3>
        <table class="metrics-compare">
          <tr><th>Modèle</th><th>Accuracy</th><th>Précision</th><th>Rappel</th><th>F1-Score</th><th>ROC-AUC</th><th></th></tr>
          <tr class="highlight-row"><td>Régression Logistique</td><td>0,803</td><td>0,800</td><td>0,848</td><td>0,824</td><td>0,871</td><td><span class="tag tag-best">Retenu ✓</span></td></tr>
          <tr><td>Random Forest</td><td>0,770</td><td>—</td><td>—</td><td>0,788</td><td>0,862</td><td><span class="tag tag-ok">OK</span></td></tr>
          <tr><td>Arbre de décision</td><td>0,770</td><td>—</td><td>—</td><td>0,781</td><td>0,832</td><td><span class="tag tag-ok">OK</span></td></tr>
        </table>

        <div class="alert-box">
          <strong>Note sur le dataset Heart :</strong> la Régression Logistique surpasse le Random Forest sur un dataset aussi
          petit (302 lignes uniques). Les méthodes d'ensemble nécessitent davantage de données pour exprimer leur avantage.
          C'est un résultat classique en machine learning : la complexité du modèle doit être adaptée à la taille des données.
        </div>
      </div>

      <div class="section-box">
        <h2>Architecture technique</h2>
        <p>
          Le site repose sur une architecture simple en deux couches : un front-end PHP qui collecte les données utilisateur
          et les passe à un script Python via <code>shell_exec()</code>. Le script charge le modèle sauvegardé au format
          <code>.joblib</code>, effectue la prédiction et retourne un objet JSON que PHP affiche. Les modèles sont
          entraînés une seule fois et sauvegardés ; le site ne réentraîne que si le fichier est absent.
        </p>
        <div class="badge-row" style="margin-top:12px;">
          <span class="badge">PHP 8+</span>
          <span class="badge">Python 3 / scikit-learn</span>
          <span class="badge">joblib</span>
          <span class="badge">JSON API interne</span>
          <span class="badge">Pipeline sklearn</span>
        </div>
      </div>

    </div>
  </main>

  <?php include 'partials_footer.php'; ?>
</body>
</html>
