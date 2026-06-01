<?php
session_start();

$page   = 'prediction';
$result = null;
$error  = '';

$mode = $_GET['mode'] ?? $_POST['mode'] ?? 'cardio';
if (!in_array($mode, ['cardio', 'heart'], true)) $mode = 'cardio';

/* ─── Étapes du formulaire ──────────────────────────────────────── */
$STEPS = [
  'cardio' => [
    ['label'=>'Profil',      'icon'=>'👤', 'desc'=>'Informations générales', 'fields'=>['age','gender','height','weight']],
    ['label'=>'Mesures',     'icon'=>'🩺', 'desc'=>'Données médicales',       'fields'=>['ap_hi','ap_lo','cholesterol','gluc']],
    ['label'=>'Mode de vie', 'icon'=>'🏃', 'desc'=>'Habitudes quotidiennes',  'fields'=>['smoke','alco','active']],
  ],
  'heart'  => [
    ['label'=>'Profil',    'icon'=>'👤', 'desc'=>'Informations générales', 'fields'=>['age','sex']],
    ['label'=>'Examen',    'icon'=>'🩺', 'desc'=>'Bilan clinique',          'fields'=>['cp','trestbps','chol','fbs','restecg','thalach']],
    ['label'=>'Effort',    'icon'=>'💪', 'desc'=>"Épreuve d'effort",       'fields'=>['exang','oldpeak','slope','ca','thal']],
  ],
];
$currentSteps = $STEPS[$mode];

/* ─── Moyennes sains / malades ──────────────────────────────────── */
$MEANS = [
  'cardio' => [
    'sains'  => ['age'=>51.9,'gender'=>1.67,'height'=>164.9,'weight'=>72.2,'ap_hi'=>120.1,'ap_lo'=>78.3,'cholesterol'=>1.35,'gluc'=>1.12,'smoke'=>0.083,'alco'=>0.050,'active'=>0.819],
    'malades'=> ['age'=>55.7,'gender'=>1.64,'height'=>164.2,'weight'=>75.5,'ap_hi'=>135.8,'ap_lo'=>84.9,'cholesterol'=>1.62,'gluc'=>1.21,'smoke'=>0.095,'alco'=>0.052,'active'=>0.785],
    'labels' => ['age'=>'Âge (ans)','gender'=>'Genre','height'=>'Taille (cm)','weight'=>'Poids (kg)','ap_hi'=>'Pression syst.','ap_lo'=>'Pression diast.','cholesterol'=>'Cholestérol','gluc'=>'Glucose','smoke'=>'Tabagisme','alco'=>'Alcool','active'=>'Activité phys.'],
  ],
  'heart'  => [
    'sains'  => ['age'=>52.6,'sex'=>0.65,'cp'=>1.42,'trestbps'=>129.2,'chol'=>250.8,'fbs'=>0.11,'restecg'=>0.52,'thalach'=>158.4,'exang'=>0.12,'oldpeak'=>0.59,'slope'=>1.60,'ca'=>0.27,'thal'=>1.48],
    'malades'=> ['age'=>56.8,'sex'=>0.82,'cp'=>0.68,'trestbps'=>134.6,'chol'=>251.3,'fbs'=>0.15,'restecg'=>0.72,'thalach'=>139.1,'exang'=>0.55,'oldpeak'=>1.59,'slope'=>1.15,'ca'=>1.15,'thal'=>1.83],
    'labels' => ['age'=>'Âge','sex'=>'Sexe','cp'=>'Douleur thor.','trestbps'=>'Pression repos','chol'=>'Cholestérol','fbs'=>'Glycémie FBS','restecg'=>'ECG repos','thalach'=>'Fréq. max','exang'=>'Angine effort','oldpeak'=>'Oldpeak (ST)','slope'=>'Pente ST','ca'=>'Vaisseaux','thal'=>'Thalassémie'],
  ],
];

/* ─── Config formulaires ────────────────────────────────────────── */
$formConfigs = [
  'cardio' => [
    'title'        => 'Test classique',
    'subtitle'     => 'Variables générales — accessibles sans bilan médical.',
    'dataset_name' => 'Dataset cardio (70 000 observations)',
    'description'  => "Dataset cardio (Kaggle – Sulianova). Données générales : âge, taille, poids, tension, cholestérol. Modèle retenu : Random Forest (ROC-AUC ≈ 0,80).",
    'fields' => [
      'age'         => ['label'=>'Âge (années)',               'type'=>'number','placeholder'=>'Exemple : 50','required'=>true,'min'=>1,'max'=>120],
      'gender'      => ['label'=>'Genre',                      'type'=>'select','options'=>['1'=>'Femme','2'=>'Homme']],
      'height'      => ['label'=>'Taille (cm)',                'type'=>'number','placeholder'=>'Exemple : 170','required'=>true,'min'=>50,'max'=>250],
      'weight'      => ['label'=>'Poids (kg)',                 'type'=>'number','step'=>'0.1','placeholder'=>'Exemple : 70','required'=>true,'min'=>20,'max'=>300],
      'ap_hi'       => ['label'=>'Pression systolique (mmHg)', 'type'=>'number','placeholder'=>'Exemple : 120','required'=>true,'min'=>60,'max'=>250],
      'ap_lo'       => ['label'=>'Pression diastolique (mmHg)','type'=>'number','placeholder'=>'Exemple : 80','required'=>true,'min'=>40,'max'=>180],
      'cholesterol' => ['label'=>'Cholestérol',                'type'=>'select','options'=>['1'=>'Normal','2'=>'Au-dessus','3'=>'Bien au-dessus'],
                        'tooltip'=>'Normal < 200 mg/dL, Au-dessus : 200-239, Bien au-dessus : ≥ 240'],
      'gluc'        => ['label'=>'Glycémie',                   'type'=>'select','options'=>['1'=>'Normale','2'=>'Au-dessus','3'=>'Bien au-dessus']],
      'smoke'       => ['label'=>'Fumeur·se',                  'type'=>'select','options'=>['0'=>'Non','1'=>'Oui']],
      'alco'        => ['label'=>"Alcool",                     'type'=>'select','options'=>['0'=>'Non','1'=>'Oui']],
      'active'      => ['label'=>'Activité physique',          'type'=>'select','options'=>['0'=>'Non','1'=>'Oui']],
    ],
    'defaults'=>['age'=>'','gender'=>'1','height'=>'','weight'=>'','ap_hi'=>'','ap_lo'=>'','cholesterol'=>'1','gluc'=>'1','smoke'=>'0','alco'=>'0','active'=>'1'],
  ],
  'heart' => [
    'title'        => 'Test avancé',
    'subtitle'     => 'Variables cliniques — nécessite un bilan médical.',
    'dataset_name' => 'Dataset heart (302 observations uniques)',
    'description'  => "Dataset heart (Kaggle – johnsmith88, 4 sources UCI). Variables cliniques : ECG, douleur thoracique, fréquence max. Modèle retenu : Régression Logistique (ROC-AUC ≈ 0,87).",
    'fields' => [
      'age'      => ['label'=>'Âge (années)',                    'type'=>'number','placeholder'=>'Exemple : 54','required'=>true,'min'=>1,'max'=>120],
      'sex'      => ['label'=>'Sexe',                            'type'=>'select','options'=>['0'=>'Femme','1'=>'Homme']],
      'cp'       => ['label'=>'Douleur thoracique',              'type'=>'select',
                     'options'=>['0'=>'Angine typique','1'=>'Angine atypique','2'=>'Non angineuse','3'=>'Asymptomatique'],
                     'tooltip'=>"Angine typique : douleur à l'effort soulagée par le repos."],
      'trestbps' => ['label'=>'Pression repos (mmHg)',           'type'=>'number','placeholder'=>'Exemple : 130','required'=>true],
      'chol'     => ['label'=>'Cholestérol sérique (mg/dL)',     'type'=>'number','placeholder'=>'Exemple : 246','required'=>true,
                     'tooltip'=>"Valeur issue d'une prise de sang."],
      'fbs'      => ['label'=>'Glycémie à jeun > 120 mg/dL',    'type'=>'select','options'=>['0'=>'Non','1'=>'Oui']],
      'restecg'  => ['label'=>'ECG au repos',                    'type'=>'select','options'=>['0'=>'Normal','1'=>'Anomalie ST-T','2'=>'Hypertrophie VG']],
      'thalach'  => ['label'=>'Fréquence cardiaque max (bpm)',   'type'=>'number','placeholder'=>'Exemple : 150','required'=>true],
      'exang'    => ['label'=>"Angine à l'effort",               'type'=>'select','options'=>['0'=>'Non','1'=>'Oui']],
      'oldpeak'  => ['label'=>'Oldpeak (dépression ST)',         'type'=>'number','step'=>'0.1','placeholder'=>'Exemple : 1.2','required'=>true,
                     'tooltip'=>"Dépression du segment ST à l'effort."],
      'slope'    => ['label'=>'Pente segment ST',                'type'=>'select','options'=>['0'=>'Ascendante','1'=>'Plate','2'=>'Descendante']],
      'ca'       => ['label'=>'Vaisseaux colorés (0–3)',         'type'=>'select','options'=>['0'=>'0','1'=>'1','2'=>'2','3'=>'3'],
                     'tooltip'=>"Nombre de vaisseaux coronaires lors d'une fluoroscopie."],
      'thal'     => ['label'=>'Thalassémie',                     'type'=>'select','options'=>['0'=>'Normal','1'=>'Défaut fixe','2'=>'Défaut réversible']],
    ],
    'defaults'=>['age'=>'','sex'=>'1','cp'=>'0','trestbps'=>'','chol'=>'','fbs'=>'0','restecg'=>'0','thalach'=>'','exang'=>'0','oldpeak'=>'','slope'=>'1','ca'=>'0','thal'=>'0'],
  ],
];

$currentConfig = $formConfigs[$mode];
$values        = $currentConfig['defaults'];

/* ─── Traitement POST ───────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $default) {
        if (isset($_POST[$key])) $values[$key] = trim((string)$_POST[$key]);
    }

    $tmpDir    = __DIR__ . DIRECTORY_SEPARATOR . 'tmp';
    if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);
    $inputFile = $tmpDir . DIRECTORY_SEPARATOR . 'prediction_input_' . $mode . '.json';
    file_put_contents($inputFile, json_encode($values, JSON_UNESCAPED_UNICODE));

    $escapedScript = escapeshellarg(__DIR__ . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR . 'predict.py');
    $escapedInput  = escapeshellarg($inputFile);
    $escapedMode   = escapeshellarg($mode);

    if (PHP_OS_FAMILY === 'Windows') {
        $cmd = 'chcp 65001 > nul && set PYTHONIOENCODING=utf-8 && "C:\\Windows\\py.exe" -3.11 '.$escapedScript.' '.$escapedMode.' '.$escapedInput.' 2>&1';
    } else {
        $cmd = 'python3 '.$escapedScript.' '.$escapedMode.' '.$escapedInput.' 2>&1';
    }

    $raw = shell_exec($cmd);
    if ($raw === null || trim($raw) === '') {
        $error = 'Impossible de lancer Python. Vérifie que shell_exec est activé et Python accessible.';
    } else {
        $clean = trim($raw);
        if (!mb_check_encoding($clean,'UTF-8')) $clean = mb_convert_encoding($clean,'UTF-8','Windows-1252');
        $decoded = json_decode($clean, true);
        if (is_array($decoded)) {
            if (($decoded['status']??'') === 'ok') {
                $result = $decoded;
                if (!isset($_SESSION['predictions'])) $_SESSION['predictions'] = [];
                $_SESSION['predictions'][] = [
                    'ts'         => date('d/m/Y H:i'),
                    'mode'       => $mode,
                    'mode_label' => $currentConfig['title'],
                    'prob'       => $result['probability_percent']??0,
                    'risk_label' => $result['risk_label']??'—',
                    'risk_css'   => $result['risk_label_css']??'',
                    'age'        => $values['age']??'—',
                    'model_name' => $result['model_name']??'—',
                ];
                $_SESSION['predictions'] = array_slice($_SESSION['predictions'], -10);
            } else {
                $error = $decoded['message']??'Erreur inconnue.';
            }
        } else {
            $error = 'Sortie Python invalide : '.htmlspecialchars($clean);
        }
    }
}

if (isset($_GET['clear_history'])) {
    unset($_SESSION['predictions']);
    header('Location: prediction.php?mode='.$mode);
    exit;
}

$history      = $_SESSION['predictions'] ?? [];
$means_mode   = $MEANS[$mode];
$display_vals = $result ? ($result['input_display_values'] ?? $values) : $values;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CardioPredict — Prédiction</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
  <style>
    /* ── Tooltip ──────────────────────────────────────────────── */
    .tooltip-wrap{position:relative;display:inline-block;margin-left:5px;cursor:help}
    .tooltip-icon{width:16px;height:16px;border-radius:50%;background:var(--primary-soft);
                  color:var(--primary);font-size:10px;font-weight:700;display:inline-flex;
                  align-items:center;justify-content:center;border:1px solid var(--line)}
    .tooltip-text{visibility:hidden;opacity:0;width:220px;background:#16304f;color:#fff;
                  border-radius:10px;padding:8px 12px;font-size:12px;line-height:1.5;
                  position:absolute;z-index:99;bottom:130%;left:50%;transform:translateX(-50%);
                  transition:opacity .2s;pointer-events:none}
    .tooltip-wrap:hover .tooltip-text{visibility:visible;opacity:1}
    .field-block label{display:flex;align-items:center;gap:4px}

    /* ── Jauge ────────────────────────────────────────────────── */
    .gauge-outer{text-align:center;padding:8px 0 0}
    .gauge-wrapper{position:relative;height:140px;max-width:260px;margin:0 auto}
    .gauge-center-text{position:absolute;bottom:10px;left:50%;transform:translateX(-50%);
                       text-align:center;pointer-events:none;width:100%}
    .gauge-pct{font-size:30px;font-weight:800;line-height:1;display:block}
    .gauge-sub{font-size:12px;color:var(--muted);margin-top:2px;display:block}

    /* ── Résultat post-prédiction ─────────────────────────────── */
    .result-extra{margin-top:26px;display:flex;flex-direction:column;gap:18px}
    .extra-card{background:#fff;border:1px solid var(--line);border-radius:var(--radius);
                box-shadow:var(--shadow);padding:20px}
    .extra-card h3{margin:0 0 10px;font-size:15px;color:#123259}
    .extra-card .sub{font-size:13px;color:var(--muted);margin:0 0 12px}
    .chart-h260{position:relative;height:260px}
    .chart-h220{position:relative;height:220px}

    /* ── Comparaison table ────────────────────────────────────── */
    .cmp-table{width:100%;border-collapse:collapse;font-size:12px}
    .cmp-table th{background:var(--primary-soft);color:var(--primary-dark);
                  padding:7px 10px;text-align:left;font-weight:700;font-size:11px}
    .cmp-table td{padding:6px 10px;border-bottom:1px solid var(--line);font-size:12px}
    .cmp-table tr:last-child td{border-bottom:none}
    .cmp-sick{color:#991b1b;font-weight:700}.cmp-safe{color:#065f46;font-weight:700}

    /* ── What-if ──────────────────────────────────────────────── */
    .wi-row{display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:end;margin-bottom:12px}
    @media(max-width:600px){.wi-row{grid-template-columns:1fr}}
    .risk-indicator{display:flex;align-items:center;gap:16px;padding:12px 16px;
                    border-radius:12px;border:1.5px solid var(--line);background:var(--surface-2)}
    .risk-num{font-size:26px;font-weight:800;color:var(--primary)}
    .risk-delta{padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700}
    .delta-up{background:#fee2e2;color:#991b1b}.delta-down{background:#d1fae5;color:#065f46}
    .delta-eq{background:#f3f4f6;color:var(--muted)}

    /* ── History ──────────────────────────────────────────────── */
    .hist-table{width:100%;border-collapse:collapse;font-size:12px}
    .hist-table th{background:var(--primary-soft);color:var(--primary-dark);
                   padding:8px 10px;text-align:left;font-weight:700;font-size:11px}
    .hist-table td{padding:7px 10px;border-bottom:1px solid var(--line)}
    .hist-table tr:last-child td{border-bottom:none}
    .badge-faible{background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700}
    .badge-modere{background:#fef3c7;color:#78350f;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700}
    .badge-eleve {background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700}

    /* ── Actions ──────────────────────────────────────────────── */
    .action-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
    .btn-pdf{background:#fff;color:#16304f;border:1px solid #cfe0f7;box-shadow:none;font-size:13px}
    .btn-pdf:hover{background:#f3f8ff}
  </style>
</head>
<body>
  <?php include 'partials_header.php'; ?>

  <main>
    <div class="container">

      <section class="hero prediction-hero" style="padding:36px 32px;margin-bottom:22px">
        <h1 style="font-size:28px">Choisir un mode de prédiction</h1>
        <p style="font-size:15px">
          Le test classique (cardio) utilise des variables générales sans bilan médical.
          Le test avancé (heart) demande des données cliniques précises.
        </p>
        <div class="mode-switch">
          <a href="prediction.php?mode=cardio" class="mode-pill <?= $mode==='cardio'?'active':'' ?>">🫀 Test classique</a>
          <a href="prediction.php?mode=heart"  class="mode-pill <?= $mode==='heart' ?'active':'' ?>">❤️ Test avancé</a>
        </div>
      </section>

      <div class="grid-2 prediction-layout">

        <!-- ════════════ FORMULAIRE MULTI-ÉTAPES ════════════════ -->
        <div class="card">
          <div style="margin-bottom:16px">
            <span class="badge"><?= htmlspecialchars($currentConfig['title']) ?></span>
            <h2 style="margin:8px 0 4px;font-size:17px"><?= htmlspecialchars($currentConfig['dataset_name']) ?></h2>
            <p class="small-note"><?= htmlspecialchars($currentConfig['subtitle']) ?></p>
          </div>

          <!-- Progress bar ────────────────────────────────────── -->
          <div class="step-progress" id="stepProgress">
            <?php foreach ($currentSteps as $si => $step): ?>
              <?php if ($si > 0): ?><div class="step-connector" id="conn<?= $si ?>"></div><?php endif; ?>
              <div class="step-item">
                <div class="step-dot <?= $si===0?'active':'' ?>" id="dot<?= $si+1 ?>">
                  <?= $si===0 ? $step['icon'] : $si+1 ?>
                </div>
                <span class="step-label <?= $si===0?'active':'' ?>" id="lbl<?= $si+1 ?>">
                  <?= htmlspecialchars($step['label']) ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>

          <p class="small-note" id="stepDesc" style="margin-bottom:16px">
            <?= htmlspecialchars($currentSteps[0]['desc']) ?>
          </p>

          <form method="post" id="predForm">
            <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>">

            <?php foreach ($currentSteps as $si => $step): ?>
            <div class="step-fields <?= $si===0?'active':'' ?>" id="stepFields<?= $si+1 ?>" data-step="<?= $si+1 ?>">
              <div class="form-grid">
                <?php foreach ($step['fields'] as $name):
                  $field = $currentConfig['fields'][$name]; ?>
                <div class="field-block">
                  <label for="<?= htmlspecialchars($name) ?>">
                    <?= htmlspecialchars($field['label']) ?>
                    <?php if (!empty($field['tooltip'])): ?>
                      <span class="tooltip-wrap">
                        <span class="tooltip-icon">?</span>
                        <span class="tooltip-text"><?= htmlspecialchars($field['tooltip']) ?></span>
                      </span>
                    <?php endif; ?>
                  </label>
                  <?php if ($field['type']==='select'): ?>
                    <select id="<?= htmlspecialchars($name) ?>" name="<?= htmlspecialchars($name) ?>">
                      <?php foreach ($field['options'] as $ov=>$ol): ?>
                        <option value="<?= htmlspecialchars($ov) ?>" <?= (string)$values[$name]===(string)$ov?'selected':'' ?>>
                          <?= htmlspecialchars($ol) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  <?php else: ?>
                    <input type="<?= htmlspecialchars($field['type']) ?>"
                           id="<?= htmlspecialchars($name) ?>"
                           name="<?= htmlspecialchars($name) ?>"
                           value="<?= htmlspecialchars($values[$name]) ?>"
                           placeholder="<?= htmlspecialchars($field['placeholder']??'') ?>"
                           <?= isset($field['step'])    ?'step="'.htmlspecialchars($field['step']).'"':'' ?>
                           <?= isset($field['min'])     ?'min="' .htmlspecialchars($field['min']) .'"':'' ?>
                           <?= isset($field['max'])     ?'max="' .htmlspecialchars($field['max']) .'"':'' ?>
                           <?= !empty($field['required'])?'required':'' ?>>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>

            <!-- Navigation étapes -->
            <div class="step-nav">
              <button type="button" class="btn btn-prev" id="btnPrev" onclick="prevStep()" style="display:none">
                ← Précédent
              </button>
              <button type="button" class="btn btn-next" id="btnNext" onclick="nextStep()">
                Suivant →
              </button>
              <button type="submit" class="btn btn-submit" id="btnSubmit" style="display:none">
                🔍 Lancer la prédiction
              </button>
            </div>
          </form>
        </div>

        <!-- ════════════ PANNEAU RÉSULTAT ════════════════════════ -->
        <div class="card result-panel">
          <h2>Résultat du modèle</h2>

          <?php if ($error !== ''): ?>
            <div class="error-box"><?= htmlspecialchars($error) ?></div>

          <?php elseif ($result): ?>
            <?php
              $prob = (float)($result['probability_percent'] ?? 0);
              $rcss = $result['risk_label_css'] ?? 'modere';
              $gaugeColor = ['faible'=>'#10B981','modere'=>'#F59E0B','eleve'=>'#EF4444'][$rcss] ?? '#1F5FBF';
            ?>

            <!-- Jauge semi-circulaire -->
            <div class="gauge-outer">
              <div class="gauge-wrapper">
                <canvas id="gaugeChart"></canvas>
                <div class="gauge-center-text">
                  <span class="gauge-pct" style="color:<?= $gaugeColor ?>"><?= $prob ?>%</span>
                  <span class="gauge-sub">de risque estimé</span>
                </div>
              </div>
            </div>

            <!-- Badge de risque -->
            <div style="text-align:center;margin:10px 0 14px">
              <span class="risk-badge <?= htmlspecialchars($rcss) ?>" style="font-size:16px;padding:8px 20px">
                <?= ['faible'=>'🟢','modere'=>'🟡','eleve'=>'🔴'][$rcss]??'•' ?>
                Risque <?= htmlspecialchars($result['risk_label']??'') ?>
              </span>
            </div>

            <!-- Radar par groupe de facteurs -->
            <div style="position:relative;height:200px;margin-bottom:14px">
              <canvas id="radarResult"></canvas>
            </div>

            <div class="result-box <?= htmlspecialchars($rcss) ?>" style="padding:14px;font-size:13px">
              <p style="margin:0 0 6px"><strong><?= htmlspecialchars($result['prediction_label']??'') ?></strong></p>
              <p style="margin:0;color:#6b7e94;font-style:italic;font-size:12px">
                ⚠️ Estimation statistique — ne remplace pas un avis médical.
              </p>
            </div>

            <div class="action-row">
              <button class="btn btn-pdf" onclick="exportPDF()">📄 Exporter PDF</button>
            </div>

            <table class="table-like metrics-table" style="margin-top:14px;font-size:12px">
              <tr><th>Indicateur</th><th>Valeur</th></tr>
              <tr><td>Modèle</td><td><?= htmlspecialchars($result['model_name']??'') ?></td></tr>
              <?php if (!empty($result['accuracy'])):  ?><tr><td>Accuracy</td><td><?= $result['accuracy'] ?></td></tr><?php endif; ?>
              <?php if (!empty($result['roc_auc'])):   ?><tr><td>ROC-AUC</td><td><?= $result['roc_auc'] ?></td></tr><?php endif; ?>
              <?php if (!empty($result['f1_score'])):  ?><tr><td>F1-Score</td><td><?= $result['f1_score'] ?></td></tr><?php endif; ?>
            </table>

          <?php else: ?>
            <div class="empty-state">
              <div class="empty-state-icon">🔬</div>
              <p><strong>Aucune prédiction pour le moment.</strong></p>
              <p style="font-size:13px">Remplis le formulaire étape par étape et lance la prédiction.</p>
            </div>
          <?php endif; ?>
        </div>

      </div><!-- /grid-2 -->

      <?php if ($result): ?>
      <!-- ══════════════════════════════════════════════════════════
           SECTIONS POST-PRÉDICTION
      ══════════════════════════════════════════════════════════ -->
      <div class="result-extra">

        <!-- Contributions par feature -->
        <div class="extra-card">
          <h3>🔍 Facteurs de risque pour ce patient</h3>
          <p class="sub">
            <span style="color:#EF4444;font-weight:700">Rouge</span> = augmente le risque •
            <span style="color:#10B981;font-weight:700">Vert</span> = facteur protecteur
          </p>
          <div class="chart-h260"><canvas id="chartContrib"></canvas></div>
        </div>

        <!-- Comparaison profil -->
        <div class="extra-card">
          <h3>📊 Profil comparé aux moyennes du dataset</h3>
          <p class="sub">
            Comparaison aux moyennes des patients
            <span style="color:#10B981;font-weight:700">sains</span> et
            <span style="color:#EF4444;font-weight:700">malades</span> d'entraînement.
          </p>
          <table class="cmp-table">
            <thead>
              <tr><th>Variable</th><th>Votre valeur</th><th style="color:#10B981">Sains (moy.)</th><th style="color:#EF4444">Malades (moy.)</th><th>Proximité</th></tr>
            </thead>
            <tbody>
              <?php foreach ($means_mode['labels'] as $feat => $label):
                $vp = isset($display_vals[$feat]) ? (float)$display_vals[$feat] : null;
                $vs = $means_mode['sains'][$feat]   ?? null;
                $vm = $means_mode['malades'][$feat] ?? null;
                if ($vp===null||$vs===null||$vm===null) continue;
                $closer = abs($vp-$vs) <= abs($vp-$vm) ? 'safe' : 'sick';
              ?>
              <tr>
                <td><?= htmlspecialchars($label) ?></td>
                <td><strong><?= number_format($vp,2) ?></strong></td>
                <td style="color:#10B981"><?= number_format($vs,2) ?></td>
                <td style="color:#EF4444"><?= number_format($vm,2) ?></td>
                <td><span class="cmp-<?= $closer ?>"><?= $closer==='safe'?'✓ Proche sains':'⚠ Proche malades' ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Simulateur What-if -->
        <div class="extra-card">
          <h3>🧪 Simulateur "What if"</h3>
          <p class="sub">Modifiez une variable et voyez l'impact en temps réel sur le score de risque.</p>
          <div class="wi-row">
            <div>
              <label style="font-weight:600;font-size:13px;display:block;margin-bottom:6px">Variable à modifier</label>
              <select id="wi_feat" onchange="updateWhatIf()" style="width:100%;padding:10px 12px;border:1.5px solid #c9dbf2;border-radius:10px;font-size:13px;font-family:inherit">
                <?php foreach ($currentConfig['fields'] as $fname => $fdata): ?>
                  <option value="<?= htmlspecialchars($fname) ?>"
                          data-type="<?= htmlspecialchars($fdata['type']) ?>"
                          data-val="<?= htmlspecialchars($values[$fname]??'') ?>"
                          data-min="<?= htmlspecialchars($fdata['min']??'') ?>"
                          data-max="<?= htmlspecialchars($fdata['max']??'') ?>"
                          data-step="<?= htmlspecialchars($fdata['step']??'1') ?>">
                    <?= htmlspecialchars($fdata['label']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div id="wi_num_wrap">
              <label style="font-weight:600;font-size:13px;display:block;margin-bottom:6px">Nouvelle valeur</label>
              <input type="number" id="wi_val" oninput="debounceSimulate()"
                     style="width:100%;padding:10px 12px;border:1.5px solid #c9dbf2;border-radius:10px;font-size:13px;font-family:inherit">
            </div>
            <div id="wi_sel_wrap" style="display:none">
              <label style="font-weight:600;font-size:13px;display:block;margin-bottom:6px">Nouvelle valeur</label>
              <select id="wi_sel_val" onchange="debounceSimulate()"
                      style="width:100%;padding:10px 12px;border:1.5px solid #c9dbf2;border-radius:10px;font-size:13px;font-family:inherit"></select>
            </div>
            <div><button class="btn" onclick="simulate()" style="white-space:nowrap;font-size:13px">▶ Simuler</button></div>
          </div>
          <div id="wi_result" style="display:none">
            <div class="risk-indicator">
              <div><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Nouveau score</div>
                <div class="risk-num" id="wi_prob">—</div>
                <div id="wi_rlabel" style="font-size:12px;color:var(--muted)"></div></div>
              <div><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Variation</div>
                <div class="risk-delta delta-eq" id="wi_delta">0%</div></div>
            </div>
          </div>
          <div id="wi_loading" style="display:none;font-size:13px;color:var(--muted);margin-top:8px">⏳ Simulation…</div>
          <div id="wi_error"   style="display:none" class="error-box"></div>
        </div>

      </div><!-- /result-extra -->
      <?php endif; ?>

      <!-- ══════════════════════════════════════════════════════════
           HISTORIQUE SESSION
      ══════════════════════════════════════════════════════════ -->
      <?php if (!empty($history)): ?>
      <div class="section-box" style="margin-top:26px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px">
          <h2 style="margin:0">📋 Historique des prédictions</h2>
          <a href="prediction.php?mode=<?= $mode ?>&clear_history=1" class="btn btn-secondary btn-sm"
             onclick="return confirm('Effacer l\'historique ?')">🗑 Effacer</a>
        </div>
        <div style="overflow-x:auto">
          <table class="hist-table">
            <thead>
              <tr><th>Date</th><th>Mode</th><th>Âge</th><th>Modèle</th><th>Probabilité</th><th>Risque</th></tr>
            </thead>
            <tbody>
              <?php foreach (array_reverse($history) as $h): ?>
              <tr>
                <td><?= htmlspecialchars($h['ts']) ?></td>
                <td><span class="badge" style="font-size:11px"><?= htmlspecialchars($h['mode_label']) ?></span></td>
                <td><?= htmlspecialchars((string)$h['age']) ?> ans</td>
                <td style="color:var(--muted)"><?= htmlspecialchars($h['model_name']) ?></td>
                <td><strong><?= htmlspecialchars((string)$h['prob']) ?>%</strong></td>
                <td><span class="badge-<?= htmlspecialchars($h['risk_css']) ?>"><?= htmlspecialchars($h['risk_label']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </main>

  <?php include 'partials_footer.php'; ?>

  <!-- ── JAVASCRIPT ─────────────────────────────────────────────── -->
  <script>
  /* ════════════════ Données PHP ═══════════════════════════════ */
  const N_STEPS = <?= count($currentSteps) ?>;
  const STEPS_DESC = <?= json_encode(array_column($currentSteps,'desc')) ?>;
  const STEPS_ICONS = <?= json_encode(array_column($currentSteps,'icon')) ?>;
  <?php if ($result): ?>
  const PRED = <?= json_encode([
    'mode'          => $mode,
    'probability'   => $result['probability_percent']??0,
    'risk_label'    => $result['risk_label']??'',
    'risk_css'      => $result['risk_label_css']??'',
    'contributions' => $result['feature_contributions']??[],
    'feature_order' => $result['feature_order']??[],
    'input_values'  => $values,
    'model_name'    => $result['model_name']??'',
    'dataset_name'  => $result['dataset_name']??'',
    'accuracy'      => $result['accuracy']??null,
    'roc_auc'       => $result['roc_auc']??null,
    'f1_score'      => $result['f1_score']??null,
  ], JSON_UNESCAPED_UNICODE) ?>;
  const FIELD_LABELS = <?= json_encode(array_map(fn($f)=>$f['label'], $currentConfig['fields']), JSON_UNESCAPED_UNICODE) ?>;
  const FIELD_CFG    = <?= json_encode(array_map(fn($f)=>['type'=>$f['type'],'options'=>($f['options']??[]),'min'=>($f['min']??''),'max'=>($f['max']??''),'step'=>($f['step']??'1')], $currentConfig['fields']), JSON_UNESCAPED_UNICODE) ?>;
  <?php endif; ?>

  /* ════════════════ Multi-step form ═══════════════════════════ */
  let currentStep = 1;

  function updateStepUI() {
    for (let i = 1; i <= N_STEPS; i++) {
      const dot  = document.getElementById('dot' + i);
      const lbl  = document.getElementById('lbl' + i);
      const sf   = document.getElementById('stepFields' + i);
      if (!dot) continue;
      dot.classList.remove('active','done');
      lbl.classList.remove('active','done');
      sf.classList.remove('active');
      if (i < currentStep)       { dot.classList.add('done');  lbl.classList.add('done');  dot.textContent = '✓'; }
      else if (i === currentStep) { dot.classList.add('active');lbl.classList.add('active');dot.textContent = STEPS_ICONS[i-1]||i; sf.classList.add('active'); }
      else                        { dot.textContent = i; }
      // Connector
      const conn = document.getElementById('conn' + i);
      if (conn) conn.classList.toggle('done', i <= currentStep);
    }
    document.getElementById('stepDesc').textContent = STEPS_DESC[currentStep - 1] || '';
    document.getElementById('btnPrev').style.display   = currentStep > 1 ? '' : 'none';
    document.getElementById('btnNext').style.display   = currentStep < N_STEPS ? '' : 'none';
    document.getElementById('btnSubmit').style.display = currentStep === N_STEPS ? '' : 'none';
  }

  function validateStep() {
    const sf = document.getElementById('stepFields' + currentStep);
    let ok = true;
    sf.querySelectorAll('input[required]').forEach(el => {
      if (!el.value.trim()) { el.style.borderColor='#EF4444'; ok = false; }
      else el.style.borderColor = '';
    });
    return ok;
  }

  function nextStep() {
    if (!validateStep()) { return; }
    if (currentStep < N_STEPS) { currentStep++; updateStepUI(); }
  }
  function prevStep() {
    if (currentStep > 1) { currentStep--; updateStepUI(); }
  }

  // Si POST (résultat visible) — passer directement à l'étape 3
  <?php if ($result || $error): ?>
  currentStep = N_STEPS;
  <?php endif; ?>
  updateStepUI();

  /* ════════════════ Jauge circulaire ══════════════════════════ */
  <?php if ($result): ?>
  (function() {
    const prob  = <?= (float)($result['probability_percent']??0) ?>;
    const color = '<?= $gaugeColor ?>';
    new Chart(document.getElementById('gaugeChart'), {
      type: 'doughnut',
      data: {
        datasets: [{
          data: [prob, 100 - prob],
          backgroundColor: [color, '#e8eef8'],
          borderWidth: 0,
          circumference: 180,
          rotation: 270,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: { legend: { display: false }, tooltip: { enabled: false } }
      }
    });
  })();

  /* ════════════════ Radar profil de risque ════════════════════ */
  (function() {
    const GROUPS = {
      cardio: [
        { label: 'Pression', feats: ['ap_hi','ap_lo'] },
        { label: 'Morphologie', feats: ['age','weight','height'] },
        { label: 'Biologie', feats: ['cholesterol','gluc'] },
        { label: 'Mode de vie', feats: ['smoke','alco','active'] },
        { label: 'Genre', feats: ['gender'] },
      ],
      heart: [
        { label: 'Symptômes', feats: ['cp','exang'] },
        { label: 'Mesures', feats: ['trestbps','chol','fbs'] },
        { label: 'ECG/Effort', feats: ['restecg','oldpeak','slope','thal'] },
        { label: 'Coronaires', feats: ['ca'] },
        { label: 'Profil', feats: ['age','sex','thalach'] },
      ]
    };
    const groups  = GROUPS[PRED.mode] || [];
    const contribs = PRED.contributions || {};
    const radarData = groups.map(g => {
      const sum = g.feats.reduce((acc, f) => acc + Math.abs(contribs[f] || 0), 0);
      return parseFloat(sum.toFixed(1));
    });
    const labels = groups.map(g => g.label);
    const maxVal = Math.max(...radarData, 5);
    const color  = '<?= $gaugeColor ?>';

    new Chart(document.getElementById('radarResult'), {
      type: 'radar',
      data: {
        labels,
        datasets: [{
          label: 'Profil de risque',
          data: radarData,
          borderColor: color,
          backgroundColor: color + '33',
          borderWidth: 2,
          pointRadius: 3,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          r: {
            min: 0,
            max: Math.ceil(maxVal / 5) * 5,
            ticks: { font: { size: 9 }, stepSize: 5 },
            pointLabels: { font: { size: 10 } },
            grid: { color: 'rgba(0,0,0,.08)' }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: c => ' ' + c.parsed.r.toFixed(1) + '% de contribution' } }
        }
      }
    });
  })();

  /* ════════════════ Graphique contributions ═══════════════════ */
  (function() {
    const c = PRED.contributions;
    if (!c || !Object.keys(c).length) return;
    const entries = Object.entries(c).sort((a,b) => Math.abs(b[1]) - Math.abs(a[1]));
    new Chart(document.getElementById('chartContrib'), {
      type: 'bar',
      data: {
        labels:   entries.map(([f]) => FIELD_LABELS[f] || f),
        datasets: [{
          data:            entries.map(([,v]) => v),
          backgroundColor: entries.map(([,v]) => v > 0 ? '#EF4444BB' : '#10B981BB'),
          borderColor:     entries.map(([,v]) => v > 0 ? '#EF4444' : '#10B981'),
          borderWidth: 1, borderRadius: 4,
        }]
      },
      options: {
        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: c => ' ' + (c.parsed.x>0?'+':'') + c.parsed.x.toFixed(1) + '% — ' + (c.parsed.x>0?'augmente le risque':'protecteur') } }
        },
        scales: {
          x: { grid:{color:'rgba(0,0,0,.05)'}, ticks:{callback:v=>v+'%',font:{size:10}} },
          y: { grid:{display:false}, ticks:{font:{size:10}} }
        }
      }
    });
  })();

  /* ════════════════ What-if simulator ════════════════════════ */
  let _wiDebounce = null;
  function updateWhatIf() {
    const sel   = document.getElementById('wi_feat');
    const opt   = sel.options[sel.selectedIndex];
    const fname = opt.value;
    const cfg   = FIELD_CFG[fname];
    const numW  = document.getElementById('wi_num_wrap');
    const selW  = document.getElementById('wi_sel_wrap');
    document.getElementById('wi_result').style.display = 'none';
    if (cfg && cfg.type === 'select') {
      numW.style.display = 'none'; selW.style.display = 'block';
      const sv = document.getElementById('wi_sel_val');
      sv.innerHTML = '';
      Object.entries(cfg.options).forEach(([v,l]) => {
        const o = document.createElement('option');
        o.value = v; o.textContent = l;
        if (v == PRED.input_values[fname]) o.selected = true;
        sv.appendChild(o);
      });
    } else {
      numW.style.display = 'block'; selW.style.display = 'none';
      const wi = document.getElementById('wi_val');
      wi.value = PRED.input_values[fname] || '';
      if (cfg) { wi.min = cfg.min; wi.max = cfg.max; wi.step = cfg.step; }
    }
  }
  function debounceSimulate() { clearTimeout(_wiDebounce); _wiDebounce = setTimeout(simulate, 700); }
  async function simulate() {
    const sel   = document.getElementById('wi_feat');
    const fname = sel.value;
    const cfg   = FIELD_CFG[fname];
    const nv    = cfg && cfg.type === 'select'
      ? document.getElementById('wi_sel_val').value
      : document.getElementById('wi_val').value;
    if (nv === '' || nv === null) return;
    document.getElementById('wi_loading').style.display = 'block';
    document.getElementById('wi_result').style.display  = 'none';
    document.getElementById('wi_error').style.display   = 'none';
    try {
      const resp = await fetch('api_predict.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mode: PRED.mode, values: Object.assign({}, PRED.input_values, {[fname]: nv}) })
      });
      const data = await resp.json();
      document.getElementById('wi_loading').style.display = 'none';
      if (data.status !== 'ok') {
        document.getElementById('wi_error').textContent = data.message || 'Erreur';
        document.getElementById('wi_error').style.display = 'block'; return;
      }
      const np = data.probability_percent;
      const d  = np - parseFloat(PRED.probability);
      document.getElementById('wi_prob').textContent   = np + '%';
      document.getElementById('wi_rlabel').textContent = 'Risque : ' + data.risk_label;
      const del = document.getElementById('wi_delta');
      del.textContent = (d >= 0 ? '+' : '') + d.toFixed(1) + '%';
      del.className = 'risk-delta ' + (d > 1 ? 'delta-up' : d < -1 ? 'delta-down' : 'delta-eq');
      document.getElementById('wi_result').style.display = 'block';
    } catch(e) {
      document.getElementById('wi_loading').style.display = 'none';
      document.getElementById('wi_error').textContent = 'Erreur réseau: ' + e.message;
      document.getElementById('wi_error').style.display = 'block';
    }
  }
  updateWhatIf();

  /* ════════════════ Export PDF ════════════════════════════════ */
  function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ unit: 'mm', format: 'a4' });
    const today = new Date().toLocaleDateString('fr-FR', {year:'numeric',month:'long',day:'numeric',hour:'2-digit',minute:'2-digit'});
    doc.setFillColor(31,95,191); doc.rect(0,0,210,26,'F');
    doc.setTextColor(255,255,255); doc.setFont('helvetica','bold'); doc.setFontSize(18);
    doc.text('CardioPredict', 14, 15);
    doc.setFontSize(9); doc.setFont('helvetica','normal');
    doc.text('Rapport de prédiction cardiovasculaire', 14, 22);
    doc.text(today, 196, 22, {align:'right'});
    let y = 36;
    const rc = {'faible':[16,185,129],'modere':[245,158,11],'eleve':[239,68,68]}[PRED.risk_css]||[31,95,191];
    doc.setFillColor(...rc); doc.roundedRect(14,y,182,16,4,4,'F');
    doc.setTextColor(255,255,255); doc.setFontSize(12); doc.setFont('helvetica','bold');
    doc.text('Risque ' + PRED.risk_label + '  —  ' + PRED.probability + '% de probabilité estimée', 105, y+10, {align:'center'});
    y += 24;
    doc.setTextColor(22,48,79); doc.setFont('helvetica','bold'); doc.setFontSize(11); doc.text('Profil du patient', 14, y); y += 4;
    doc.autoTable({ startY:y, head:[['Variable','Valeur']], body: Object.entries(PRED.input_values).map(([f,v])=>[FIELD_LABELS[f]||f,String(v)]),
      theme:'striped', headStyles:{fillColor:[31,95,191],textColor:255,fontSize:9}, bodyStyles:{fontSize:8}, alternateRowStyles:{fillColor:[234,242,255]}, margin:{left:14,right:14} });
    y = doc.lastAutoTable.finalY + 8;
    doc.setTextColor(22,48,79); doc.setFont('helvetica','bold'); doc.setFontSize(11); doc.text('Métriques modèle : ' + PRED.model_name, 14, y); y += 4;
    doc.autoTable({ startY:y, head:[['Métrique','Valeur']], body:[['Dataset',PRED.dataset_name],['Accuracy',PRED.accuracy?PRED.accuracy:'-'],['ROC-AUC',PRED.roc_auc?PRED.roc_auc:'-'],['F1-Score',PRED.f1_score?PRED.f1_score:'-']],
      theme:'striped', headStyles:{fillColor:[31,95,191],textColor:255,fontSize:9}, bodyStyles:{fontSize:8}, margin:{left:14,right:14} });
    y = doc.lastAutoTable.finalY + 8;
    if (y > 255) { doc.addPage(); y = 14; }
    doc.setFillColor(255,243,205); doc.roundedRect(14,y,182,14,3,3,'F');
    doc.setTextColor(120,53,15); doc.setFontSize(8); doc.setFont('helvetica','normal');
    doc.text('⚠ Estimation statistique — ne constitue pas un diagnostic médical. Consultez un professionnel de santé.', 18, y+9);
    doc.setFillColor(31,95,191); doc.rect(0,283,210,14,'F');
    doc.setTextColor(200,220,255); doc.setFontSize(7);
    doc.text('CardioPredict — L3 MIASHS, Université Paul Valéry Montpellier — cardio-predict.fr', 105, 291, {align:'center'});
    doc.save('cardiopredict_' + Date.now() + '.pdf');
  }
  <?php endif; ?>
  </script>
</body>
</html>
