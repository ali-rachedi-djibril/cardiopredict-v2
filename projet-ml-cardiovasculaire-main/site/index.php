<?php $page = 'accueil'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CardioPredict — Prédiction du risque cardiovasculaire</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <?php include 'partials_header.php'; ?>

  <main>
    <div class="container">

      <!-- ── HERO avec SVG et compteurs animés ────────────────────── -->
      <section class="hero fade-up">
        <div class="hero-inner">

          <div class="hero-content">
            <h1>Prédire le risque cardiovasculaire par apprentissage automatique</h1>
            <p>
              CardioPredict est un projet universitaire de machine learning qui compare
              cinq algorithmes sur deux datasets publics pour estimer le risque cardiovasculaire
              à partir de données cliniques ou comportementales.
            </p>
            <div class="hero-actions">
              <a class="btn" href="prediction.php">Tester une prédiction</a>
              <a class="btn btn-secondary" href="visualisations.php">Voir les visualisations</a>
              <a class="btn btn-secondary" href="apropos.php">À propos du projet</a>
            </div>

            <!-- Compteurs animés -->
            <div class="stats-row" id="statsRow">
              <div class="stat-box">
                <strong><span class="counter" data-target="70302">0</span></strong>
                <span>observations (cardio + heart dédupliqué)</span>
              </div>
              <div class="stat-box">
                <strong><span class="counter" data-target="5">0</span> modèles</strong>
                <span>LR, DT, RF, XGBoost, LightGBM</span>
              </div>
              <div class="stat-box">
                <strong>AUC <span class="counter-dec" data-target="0.871">0.000</span></strong>
                <span>meilleure performance (dataset heart)</span>
              </div>
            </div>
          </div>

          <!-- Illustration SVG -->
          <div class="hero-illustration">
            <svg width="300" height="250" viewBox="0 0 300 250" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <!-- Cercles de fond -->
              <circle cx="150" cy="120" r="115" fill="rgba(31,95,191,.06)"/>
              <circle cx="150" cy="120" r="85"  fill="rgba(31,95,191,.06)"/>

              <!-- Cœur avec animation -->
              <path d="M150 205 C105 172 52 144 52 102 C52 73 75 50 106 50 C128 50 144 63 150 76 C156 63 172 50 194 50 C225 50 248 73 248 102 C248 144 195 172 150 205Z"
                    fill="#EF4444" opacity="0.9"
                    style="animation:heartbeat 1.4s ease-in-out infinite; transform-origin:150px 128px"/>

              <!-- Ligne ECG sur le cœur -->
              <polyline
                points="52,120 88,120 100,88 110,152 122,104 134,120 150,120 166,120 178,96 188,144 200,120 248,120"
                fill="none" stroke="white" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round" opacity="0.92"
                stroke-dasharray="400" stroke-dashoffset="400"
                style="animation: drawECG 2.2s ease forwards, dashFlow 4s 2.2s linear infinite"/>

              <!-- Badge AUC -->
              <rect x="216" y="24" width="72" height="26" rx="8"
                    fill="rgba(16,185,129,.15)" stroke="#10B981" stroke-width="1.5"/>
              <text x="252" y="40" text-anchor="middle"
                    font-family="Inter,sans-serif" font-size="11" font-weight="700" fill="#10B981">
                AUC 0.871
              </text>

              <!-- Badge observations -->
              <rect x="12" y="190" width="82" height="26" rx="8"
                    fill="rgba(31,95,191,.12)" stroke="#1F5FBF" stroke-width="1.5"/>
              <text x="53" y="206" text-anchor="middle"
                    font-family="Inter,sans-serif" font-size="11" font-weight="700" fill="#1F5FBF">
                70 000 obs.
              </text>

              <!-- Badge modèles -->
              <rect x="210" y="190" width="78" height="26" rx="8"
                    fill="rgba(245,158,11,.12)" stroke="#F59E0B" stroke-width="1.5"/>
              <text x="249" y="206" text-anchor="middle"
                    font-family="Inter,sans-serif" font-size="11" font-weight="700" fill="#b45309">
                5 modèles
              </text>

              <defs>
                <style>
                  @keyframes drawECG {
                    to { stroke-dashoffset: 0; }
                  }
                  @keyframes dashFlow {
                    to { stroke-dashoffset: -400; }
                  }
                </style>
              </defs>
            </svg>
          </div>

        </div>
      </section>

      <!-- ── Deux datasets ────────────────────────────────────────── -->
      <section class="grid-2">

        <div class="card fade-up" style="animation-delay:.1s">
          <h2>🫀 Dataset Cardio</h2>
          <p>
            70 000 patients (Sulianova, Kaggle). Variables générales : âge, tension artérielle,
            poids, cholestérol, habitudes de vie. Modèle retenu :
            <strong>Random Forest / XGBoost</strong> (ROC-AUC ≈ 0,80).
          </p>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
            <span class="badge">70 000 obs.</span>
            <span class="badge">11 variables</span>
            <span class="badge">AUC 0.798</span>
          </div>
          <a href="prediction.php?mode=cardio" class="btn btn-secondary" style="margin-top:14px;display:inline-flex">
            Tester le modèle →
          </a>
        </div>

        <div class="card fade-up" style="animation-delay:.2s">
          <h2>❤️ Dataset Heart</h2>
          <p>
            302 observations uniques après déduplication (johnsmith88, Kaggle — 4 sources UCI).
            Variables cliniques : ECG, douleur thoracique, fréquence cardiaque max.
            Modèle retenu : <strong>Régression Logistique</strong> (ROC-AUC ≈ 0,87).
          </p>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
            <span class="badge">302 obs.</span>
            <span class="badge">13 variables</span>
            <span class="badge">AUC 0.871</span>
          </div>
          <a href="prediction.php?mode=heart" class="btn btn-secondary" style="margin-top:14px;display:inline-flex">
            Tester le modèle →
          </a>
        </div>

      </section>

      <!-- ── Avertissement médical ─────────────────────────────────── -->
      <div class="info-strip fade-up" style="animation-delay:.3s">
        <p style="margin:0;font-size:14px;color:#34577f">
          ⚠️ <strong>Avertissement médical :</strong> CardioPredict est un outil pédagogique basé sur des données
          publiques. Les prédictions sont des estimations statistiques et ne constituent pas un diagnostic médical.
          Consultez un professionnel de santé pour tout avis médical.
        </p>
      </div>

    </div>
  </main>

  <?php include 'partials_footer.php'; ?>

  <script>
  /* ── Compteurs animés ───────────────────────────────────────── */
  function animateInt(el, target, duration) {
    const start = performance.now();
    const fmt   = n => n >= 1000 ? Math.floor(n).toLocaleString('fr-FR') : Math.floor(n).toString();
    function step(now) {
      const t = Math.min((now - start) / duration, 1);
      const ease = 1 - Math.pow(1 - t, 3);
      el.textContent = fmt(ease * target);
      if (t < 1) requestAnimationFrame(step);
      else el.textContent = fmt(target);
    }
    requestAnimationFrame(step);
  }

  function animateDec(el, target, duration) {
    const start = performance.now();
    function step(now) {
      const t    = Math.min((now - start) / duration, 1);
      const ease = 1 - Math.pow(1 - t, 3);
      el.textContent = (ease * target).toFixed(3);
      if (t < 1) requestAnimationFrame(step);
      else el.textContent = target.toFixed(3);
    }
    requestAnimationFrame(step);
  }

  const statsRow = document.getElementById('statsRow');
  let triggered  = false;
  const observer = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting && !triggered) {
      triggered = true;
      document.querySelectorAll('.counter').forEach(el => {
        animateInt(el, +el.dataset.target, 1600);
      });
      document.querySelectorAll('.counter-dec').forEach(el => {
        animateDec(el, +el.dataset.target, 1800);
      });
    }
  }, { threshold: 0.3 });
  observer.observe(statsRow);
  </script>
</body>
</html>
