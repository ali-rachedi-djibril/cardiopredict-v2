<?php $page = 'apropos'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CardioPredict — À propos</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <?php include 'partials_header.php'; ?>

  <main>
    <div class="container">

      <!-- ── Hero ──────────────────────────────────────────────── -->
      <section class="hero" style="padding:44px 38px;margin-bottom:24px">
        <div class="about-hero-icon">🫀</div>
        <h1>À propos de CardioPredict</h1>
        <p>
          Projet universitaire de machine learning appliqué à la prédiction du risque cardiovasculaire.
          Réalisé dans le cadre du cours <strong>Science des données 4</strong> — L3 MIASHS,
          Université Paul Valéry Montpellier 3, année 2025-2026.
        </p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px">
          <span class="badge">Python 3.11</span>
          <span class="badge">PHP 8.4</span>
          <span class="badge">scikit-learn</span>
          <span class="badge">XGBoost</span>
          <span class="badge">LightGBM</span>
          <span class="badge">Chart.js</span>
        </div>
      </section>

      <!-- ── Présentation du projet ────────────────────────────── -->
      <div class="section-box">
        <h2>🎯 Objectifs du projet</h2>
        <p>
          CardioPredict est une plateforme web de prédiction du risque cardiovasculaire par
          apprentissage automatique supervisé. Elle propose deux niveaux d'analyse complémentaires :
        </p>
        <div class="grid-2" style="margin-top:16px;gap:16px">
          <div style="background:var(--primary-soft);border-radius:14px;padding:18px">
            <h3 style="margin:0 0 8px;color:var(--primary-dark)">🫀 Test classique</h3>
            <p style="margin:0;font-size:14px">
              Variables générales accessibles à tous (âge, poids, tension, habitudes de vie).
              Basé sur le dataset Cardio — 70 000 patients. Modèle : Random Forest / XGBoost.
            </p>
          </div>
          <div style="background:#fce7f3;border-radius:14px;padding:18px">
            <h3 style="margin:0 0 8px;color:#be123c">❤️ Test avancé</h3>
            <p style="margin:0;font-size:14px">
              Variables cliniques précises (ECG, douleur thoracique, scintigraphie).
              Basé sur le dataset Heart — 302 patients uniques. Modèle : Régression Logistique.
            </p>
          </div>
        </div>

        <div style="margin-top:22px">
          <h3>Pipeline technique</h3>
          <div class="pipeline-row">
            <div class="pipeline-step">📄 Formulaire PHP</div>
            <span class="pipeline-arrow">→</span>
            <div class="pipeline-step">🔧 predict.py</div>
            <span class="pipeline-arrow">→</span>
            <div class="pipeline-step">🤖 Modèle JSON</div>
            <span class="pipeline-arrow">→</span>
            <div class="pipeline-step">📊 Résultat PHP</div>
          </div>
          <p style="font-size:13px;color:var(--muted);margin-top:8px">
            Les modèles sont exportés en JSON et réimplémentés en Python standard (sans dépendances externes),
            ce qui permet le déploiement sur hébergement mutualisé où scikit-learn n'est pas disponible.
          </p>
        </div>
      </div>

      <!-- ── Datasets ───────────────────────────────────────────── -->
      <div class="section-box">
        <h2>📁 Les données utilisées</h2>

        <div class="dataset-card">
          <h3>🫀 Dataset Cardiovascular Disease</h3>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div>
              <p style="font-size:14px;margin:0 0 10px">
                Dataset principal du projet. Collecté en Russie, il contient des données
                comportementales et médicales générales sur 70 000 patients.
              </p>
              <table style="font-size:13px;border-collapse:collapse;width:100%">
                <tr><td style="padding:4px 0;color:var(--muted)">Source</td><td><strong>Kaggle — Sulianova</strong></td></tr>
                <tr><td style="padding:4px 0;color:var(--muted)">Observations</td><td><strong>70 000</strong></td></tr>
                <tr><td style="padding:4px 0;color:var(--muted)">Variables</td><td><strong>11</strong> + 3 dérivées</td></tr>
                <tr><td style="padding:4px 0;color:var(--muted)">Cible</td><td><strong>cardio</strong> (0=sain, 1=malade)</td></tr>
                <tr><td style="padding:4px 0;color:var(--muted)">Équilibre</td><td>≈ 50/50</td></tr>
              </table>
            </div>
            <div>
              <p style="font-size:13px;font-weight:700;color:var(--primary-dark);margin:0 0 8px">Variables clés</p>
              <ul style="font-size:13px;margin:0;padding-left:18px">
                <li>Âge (en jours → converti en années)</li>
                <li>Taille, poids, IMC (dérivé)</li>
                <li>Pression systolique / diastolique</li>
                <li>Cholestérol, glycémie (niveaux 1/2/3)</li>
                <li>Tabagisme, alcool, activité physique</li>
              </ul>
              <div style="margin-top:12px">
                <a href="https://www.kaggle.com/datasets/sulianova/cardiovascular-disease-dataset"
                   target="_blank" class="source-link">
                  🔗 Voir sur Kaggle
                </a>
              </div>
            </div>
          </div>
          <div style="background:#fff8e1;border-left:3px solid #F59E0B;border-radius:0 8px 8px 0;padding:10px 14px;margin-top:14px;font-size:13px;color:#78350f">
            ⚠️ Ce dataset contient des valeurs aberrantes de pression artérielle (ap_hi &gt; 300 mmHg, ap_lo &lt; 0).
            Un filtrage IQR est appliqué dans le notebook de modélisation pour les supprimer.
          </div>
        </div>

        <div class="dataset-card heart-card" style="margin-top:16px">
          <h3>❤️ Dataset Heart Disease</h3>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div>
              <p style="font-size:14px;margin:0 0 10px">
                Concaténation de 4 sources UCI (Cleveland, Hungarian, Switzerland, VA Long Beach).
                Variables cliniques spécialisées issues d'un bilan cardiologique complet.
              </p>
              <table style="font-size:13px;border-collapse:collapse;width:100%">
                <tr><td style="padding:4px 0;color:var(--muted)">Source</td><td><strong>Kaggle — johnsmith88</strong></td></tr>
                <tr><td style="padding:4px 0;color:var(--muted)">Lignes brutes</td><td><strong>1 025</strong></td></tr>
                <tr><td style="padding:4px 0;color:var(--muted)">Après dédup.</td><td><strong>302</strong> observations uniques</td></tr>
                <tr><td style="padding:4px 0;color:var(--muted)">Variables</td><td><strong>13</strong></td></tr>
                <tr><td style="padding:4px 0;color:var(--muted)">Cible</td><td><strong>target</strong> (⚠️ 1=sain, 0=malade)</td></tr>
              </table>
            </div>
            <div>
              <p style="font-size:13px;font-weight:700;color:#be123c;margin:0 0 8px">Variables clés</p>
              <ul style="font-size:13px;margin:0;padding-left:18px">
                <li>cp — type de douleur thoracique</li>
                <li>thalach — fréquence cardiaque maximale</li>
                <li>ca — vaisseaux coronaires (fluoroscopie)</li>
                <li>oldpeak — dépression ST à l'effort</li>
                <li>thal — test au thallium</li>
              </ul>
              <div style="margin-top:12px">
                <a href="https://www.kaggle.com/datasets/johnsmith88/heart-disease-dataset"
                   target="_blank" class="source-link" style="color:#be123c">
                  🔗 Voir sur Kaggle
                </a>
              </div>
            </div>
          </div>
          <div style="background:#fff0f0;border-left:3px solid #EF4444;border-radius:0 8px 8px 0;padding:10px 14px;margin-top:14px;font-size:13px;color:#991b1b">
            ⚠️ <strong>Encodage inversé :</strong> dans ce dataset, <code>target = 1</code> signifie SAIN
            et <code>target = 0</code> signifie MALADE — contrairement à l'intuition.
            Le modèle prédit la santé ; <code>predict.py</code> inverse la probabilité pour afficher le risque de maladie.
          </div>
        </div>
      </div>

      <!-- ── Résultats modèles ──────────────────────────────────── -->
      <div class="section-box">
        <h2>📊 Résultats des modèles</h2>
        <p>
          Cinq classifieurs comparés par dataset. Critère de sélection : <strong>ROC-AUC</strong>
          (mesure la discrimination indépendamment du seuil de décision).
        </p>

        <h3 style="color:var(--primary-dark);margin-top:18px">Dataset Cardio — 14 000 observations de test</h3>
        <div style="overflow-x:auto">
          <table class="table-like">
            <tr><th>Modèle</th><th>Accuracy</th><th>Précision</th><th>Rappel</th><th>F1-Score</th><th>ROC-AUC</th><th></th></tr>
            <tr><td>Régression Logistique</td><td>0,714</td><td>0,712</td><td>0,720</td><td>0,716</td><td>0,778</td><td></td></tr>
            <tr><td>Arbre de décision</td><td>0,728</td><td>0,737</td><td>0,715</td><td>0,726</td><td>0,790</td><td></td></tr>
            <tr style="font-weight:700;background:#eaf2ff"><td>Random Forest</td><td>0,732</td><td>0,756</td><td>0,684</td><td>0,718</td><td>0,798</td>
              <td><span class="badge" style="background:#d1fae5;color:#065f46;border-color:#a7f3d0">Retenu</span></td></tr>
            <tr><td>XGBoost</td><td colspan="5" style="color:var(--muted);font-style:italic">Dépend de l'exécution du notebook v2</td><td></td></tr>
            <tr><td>LightGBM</td><td colspan="5" style="color:var(--muted);font-style:italic">Dépend de l'exécution du notebook v2</td><td></td></tr>
          </table>
        </div>

        <h3 style="color:#be123c;margin-top:22px">Dataset Heart — 61 observations de test (après déduplication)</h3>
        <div style="overflow-x:auto">
          <table class="table-like">
            <tr><th>Modèle</th><th>Accuracy</th><th>Précision</th><th>Rappel</th><th>F1-Score</th><th>ROC-AUC</th><th></th></tr>
            <tr style="font-weight:700;background:#fce7f3"><td>Régression Logistique</td><td>0,803</td><td>0,800</td><td>0,848</td><td>0,824</td><td>0,871</td>
              <td><span class="badge" style="background:#d1fae5;color:#065f46;border-color:#a7f3d0">Retenu</span></td></tr>
            <tr><td>Random Forest</td><td>0,770</td><td>0,763</td><td>0,818</td><td>0,788</td><td>0,862</td><td></td></tr>
            <tr><td>Arbre de décision</td><td>0,770</td><td>0,750</td><td>0,818</td><td>0,781</td><td>0,832</td><td></td></tr>
          </table>
        </div>
        <div class="info-strip" style="margin-top:14px">
          <p style="margin:0;font-size:13px">
            Sur Heart (302 obs.), la Régression Logistique surpasse les méthodes d'ensemble — résultat classique :
            la complexité du modèle doit être adaptée à la taille des données.
          </p>
        </div>
      </div>

      <!-- ── Équipe ─────────────────────────────────────────────── -->
      <div class="section-box">
        <h2>👥 L'équipe</h2>
        <p>Projet réalisé par 4 étudiants en L3 MIASHS — Université Paul Valéry Montpellier 3.</p>
        <div class="team-grid">
          <div class="team-card">
            <div class="team-avatar">S</div>
            <h4>EL BASRI Samy</h4>
            <p>Développement web</p>
          </div>
          <div class="team-card">
            <div class="team-avatar">D</div>
            <h4>ALI RACHEDI Djibril</h4>
            <p>Machine Learning</p>
          </div>
          <div class="team-card">
            <div class="team-avatar">A</div>
            <h4>BOUSQUET Arthur</h4>
            <p>Analyse de données</p>
          </div>
          <div class="team-card">
            <div class="team-avatar">E</div>
            <h4>BERETTI-PRENANT Esteban</h4>
            <p>Visualisations</p>
          </div>
        </div>
      </div>

      <!-- ── Sources ────────────────────────────────────────────── -->
      <div class="section-box">
        <h2>📚 Sources et références</h2>
        <div style="display:flex;flex-direction:column;gap:10px;font-size:14px">
          <div style="display:flex;gap:10px;align-items:flex-start">
            <span style="color:var(--primary);font-size:18px;flex-shrink:0">📊</span>
            <div>
              <strong>Cardiovascular Disease Dataset</strong> — Sulianova, Kaggle, 2018<br>
              <a href="https://www.kaggle.com/datasets/sulianova/cardiovascular-disease-dataset" target="_blank" class="source-link">
                kaggle.com/datasets/sulianova/cardiovascular-disease-dataset
              </a>
            </div>
          </div>
          <div style="display:flex;gap:10px;align-items:flex-start">
            <span style="color:#e11d48;font-size:18px;flex-shrink:0">📊</span>
            <div>
              <strong>Heart Disease Dataset</strong> — johnsmith88, Kaggle (sources UCI)<br>
              <a href="https://www.kaggle.com/datasets/johnsmith88/heart-disease-dataset" target="_blank" class="source-link">
                kaggle.com/datasets/johnsmith88/heart-disease-dataset
              </a>
            </div>
          </div>
          <div style="display:flex;gap:10px;align-items:flex-start">
            <span style="font-size:18px;flex-shrink:0">📖</span>
            <div>
              <strong>UCI Machine Learning Repository</strong> — Heart Disease Data Set<br>
              Detrano R., Janosi A., Steinbrunn W. et al. (1989). <em>International Application of a New Probability Algorithm for the Diagnosis of Coronary Artery Disease</em>. American Journal of Cardiology.
            </div>
          </div>
          <div style="display:flex;gap:10px;align-items:flex-start">
            <span style="font-size:18px;flex-shrink:0">🛠️</span>
            <div>
              <strong>Scikit-learn</strong> — Pedregosa F. et al. (2011). <em>Scikit-learn: Machine Learning in Python</em>. JMLR 12, pp. 2825-2830.
            </div>
          </div>
        </div>
      </div>

      <!-- ── Avertissement ──────────────────────────────────────── -->
      <div class="info-strip">
        <h2 style="font-size:15px">⚠️ Avertissement médical</h2>
        <p style="margin:0;font-size:13px">
          CardioPredict est un outil <strong>pédagogique</strong> basé sur des données publiques. Les prédictions
          sont des estimations statistiques entraînées sur des populations spécifiques et ne constituent
          <strong>en aucun cas un diagnostic médical</strong>. Consultez impérativement un professionnel de
          santé (médecin, cardiologue) pour tout avis médical ou cardiovasculaire.
        </p>
      </div>

    </div>
  </main>

  <?php include 'partials_footer.php'; ?>
</body>
</html>
