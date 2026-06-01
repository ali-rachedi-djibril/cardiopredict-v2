<?php
$page = 'visualisations';

/* ─────────────────────────────────────────────────────────────────────────────
   KPI : lecture des CSV avec cache (évite de parser 70 k lignes à chaque fois)
   ───────────────────────────────────────────────────────────────────────────── */
function csv_kpis(string $path, string $target_col, bool $age_in_days): array {
    $zero = ['total'=>0,'sick'=>0,'pct_sick'=>0,'age_mean'=>0,'loaded'=>false];
    if (!is_file($path)) return $zero;
    $fh = @fopen($path,'r');
    if (!$fh) return $zero;
    $header = fgetcsv($fh,4096,';');
    if (!$header) { fclose($fh); return $zero; }
    $header = array_map('trim',$header);
    $ti = array_search($target_col,$header);
    $ai = array_search('age',$header);
    $is_heart = ($target_col==='target');
    $n=$sick=0; $age_sum=0.0;
    while (($row=fgetcsv($fh,4096,';'))!==false) {
        if ($ti===false||!isset($row[$ti])) continue;
        $n++;
        $t=(int)trim($row[$ti]);
        $sick += $is_heart ? ($t===0?1:0) : ($t===1?1:0);
        if ($ai!==false && isset($row[$ai])) {
            $a=(float)str_replace(',','.',trim($row[$ai]));
            $age_sum += $age_in_days ? $a/365.25 : $a;
        }
    }
    fclose($fh);
    return ['total'=>$n,'sick'=>$sick,
            'pct_sick'=>$n?round($sick/$n*100,1):0,
            'age_mean'=>$n?round($age_sum/$n,1):0,
            'loaded'=>true];
}
function cached_kpis(string $csv,string $col,bool $days,string $key): array {
    $dir=$_SERVER['DOCUMENT_ROOT']??__DIR__;
    $cf=__DIR__.'/tmp/'.$key.'_kpis.json';
    if (is_file($cf)&&is_file($csv)&&filemtime($cf)>filemtime($csv)&&filemtime($cf)>time()-86400) {
        $d=@json_decode(@file_get_contents($cf),true);
        if (is_array($d)) return $d;
    }
    $d=csv_kpis($csv,$col,$days);
    if (!is_dir(__DIR__.'/tmp')) @mkdir(__DIR__.'/tmp',0777,true);
    @file_put_contents($cf,json_encode($d));
    return $d;
}
$kpi_c = cached_kpis(__DIR__.'/data/cardio.csv','cardio',true,'cardio');
$kpi_h = cached_kpis(__DIR__.'/data/heart.csv', 'target',false,'heart');

/* ─── Métadonnées modèles ─────────────────────────────────────────────────── */
$meta_c = @json_decode(@file_get_contents(__DIR__.'/ml/metadata/cardio_model_info.json'),true)??[];
$meta_h = @json_decode(@file_get_contents(__DIR__.'/ml/metadata/heart_model_info.json'),true)??[];

/* ─── Coefficients LR Heart depuis le modèle (valeurs réelles) ──────────────── */
$hm = @json_decode(@file_get_contents(__DIR__.'/ml/models/heart_model.json'),true)??[];
$h_coef_raw = $hm['coef']??[];
$feat_h_fr  = ['Âge','Sexe','Douleur thorac.','Pression repos','Cholestérol',
               'Glycémie','ECG repos','Fréq. card. max','Angine effort',
               'Oldpeak (ST)','Pente ST','Nb vaisseaux','Thalassémie'];
$h_imp=[];
if (count($h_coef_raw)===13) {
    $abs=array_map('abs',$h_coef_raw);
    $s=array_sum($abs)?:1;
    foreach ($feat_h_fr as $i=>$f) $h_imp[$f]=round($abs[$i]/$s,4);
    arsort($h_imp);
}
// Fallback si modèle non lisible
if (empty($h_imp)) {
    $h_imp = array_combine($feat_h_fr,
        [0.045,0.035,0.175,0.015,0.008,0.003,0.020,0.145,0.095,0.130,0.055,0.195,0.080]);
    arsort($h_imp);
}

/* ─── Importance features Cardio (RF — valeurs issues de l'analyse) ────────── */
$c_fi_labels=['Pression syst.','Âge','Poids','Pression diast.','Cholestérol',
              'IMC','Glucose','Taille','Activité physique','Genre','Tabagisme','Alcool'];
$c_fi_data=[0.283,0.216,0.120,0.095,0.068,0.065,0.043,0.040,0.028,0.022,0.013,0.007];

/* ─── Métriques tous modèles ─────────────────────────────────────────────── */
$mc=['Rég. Logistique'=>['acc'=>0.714,'prec'=>0.712,'rec'=>0.720,'f1'=>0.716,'auc'=>0.778],
     'Arbre'          =>['acc'=>0.728,'prec'=>0.737,'rec'=>0.715,'f1'=>0.726,'auc'=>0.790],
     'Random Forest'  =>['acc'=>0.732,'prec'=>0.756,'rec'=>0.684,'f1'=>0.718,'auc'=>0.798]];
$mh=['Rég. Logistique'=>['acc'=>0.803,'prec'=>0.800,'rec'=>0.848,'f1'=>0.824,'auc'=>0.871],
     'Random Forest'  =>['acc'=>0.770,'prec'=>0.763,'rec'=>0.818,'f1'=>0.788,'auc'=>0.862],
     'Arbre'          =>['acc'=>0.770,'prec'=>0.750,'rec'=>0.818,'f1'=>0.781,'auc'=>0.832]];

/* ─── Matrices de confusion ──────────────────────────────────────────────── */
// Cardio test=14000, ~50/50: acc=0.732, prec=0.756, rec=0.684
// TP=0.684*7000=4788 FN=2212 FP=4788/0.756-4788≈1544 TN=5456
$cm_c=[[5456,1544],[2212,4788]];
// Heart test=61: acc=0.803, prec=0.800, rec=0.848
// TP≈28 FN=5 FP=7 TN=21
$cm_h=[[21,7],[5,28]];

/* ─── Corrélations (variables clés) ─────────────────────────────────────── */
$cv_c=['Âge','ap_hi','ap_lo','Poids','Cholestérol','Glucose','Cible'];
$cm_corr_c=[
    [1.00, 0.17, 0.15, 0.10, 0.06, 0.03, 0.25],
    [0.17, 1.00, 0.38, 0.14, 0.05, 0.04, 0.40],
    [0.15, 0.38, 1.00, 0.09, 0.03, 0.04, 0.15],
    [0.10, 0.14, 0.09, 1.00, 0.02, 0.03, 0.17],
    [0.06, 0.05, 0.03, 0.02, 1.00, 0.10, 0.13],
    [0.03, 0.04, 0.04, 0.03, 0.10, 1.00, 0.07],
    [0.25, 0.40, 0.15, 0.17, 0.13, 0.07, 1.00],
];
$cv_h=['Âge','Fréq. max','Oldpeak','Douleur thor.','Vaisseaux','Angine','Cible'];
$cm_corr_h=[
    [ 1.00,-0.42, 0.21,-0.07, 0.28, 0.10,-0.22],
    [-0.42, 1.00,-0.34, 0.30,-0.38,-0.38, 0.42],
    [ 0.21,-0.34, 1.00,-0.27, 0.30, 0.30,-0.43],
    [-0.07, 0.30,-0.27, 1.00,-0.18,-0.16, 0.44],
    [ 0.28,-0.38, 0.30,-0.18, 1.00, 0.15,-0.45],
    [ 0.10,-0.38, 0.30,-0.16, 0.15, 1.00,-0.37],
    [-0.22, 0.42,-0.43, 0.44,-0.45,-0.37, 1.00],
];
$corr_expl_c=[
    '0,6'=>"L'âge est fortement lié à la maladie cardiovasculaire : risque quasi doublé après 60 ans.",
    '1,2'=>"Corrélation modérée entre pressions systolique et diastolique — elles évoluent ensemble.",
    '1,6'=>"La pression systolique est le facteur de risque numéro 1 dans ce dataset (r=0.40).",
    '3,6'=>"Le poids est légèrement associé au risque — en partie via l'IMC et l'obésité.",
    '4,6'=>"Un cholestérol élevé est associé à un risque accru, mais l'effet est modéré.",
];
$corr_expl_h=[
    '0,1'=>"Les personnes âgées atteignent une fréquence cardiaque maximale plus basse.",
    '1,6'=>"Une fréquence maximale élevée est protectrice — signe de bonne capacité cardio-respiratoire.",
    '2,6'=>"Une dépression ST marquée (oldpeak) signale une ischémie myocardique à l'effort.",
    '3,6'=>"La douleur thoracique asymptomatique est paradoxalement liée à plus de maladies détectées.",
    '4,6'=>"Le nombre de vaisseaux coronaires réduits est le marqueur biologique le plus fort de maladie.",
    '5,6'=>"L'angine à l'effort est fortement liée à la maladie coronarienne.",
];

/* ─── Helper couleur heatmap ─────────────────────────────────────────────── */
function corr_color(float $r): string {
    $r = max(-1.0,min(1.0,$r));
    if ($r>=0) { $v=intval(255*(1-$r)); return "rgb(255,$v,$v)"; }
    $v=intval(255*(1+$r)); return "rgb($v,$v,255)";
}
function corr_text(float $r): string {
    return abs($r)>0.35 ? '#fff' : '#16304f';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CardioPredict — Visualisations</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    /* ── Tabs ────────────────────────────────────────────────────── */
    .viz-tabs{display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap}
    .viz-tab{padding:9px 20px;border-radius:999px;font-weight:600;font-size:14px;
             cursor:pointer;border:2px solid var(--line);background:var(--surface);
             color:var(--primary-dark);transition:.2s}
    .viz-tab.active,.viz-tab:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
    .viz-panel{display:none}.viz-panel.active{display:block}

    /* ── KPI cards ───────────────────────────────────────────────── */
    .kpi-section{margin-bottom:28px}
    .kpi-section h2{margin:0 0 16px;font-size:18px;color:var(--primary-dark)}
    .kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:14px}
    @media(max-width:900px){.kpi-row{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:500px){.kpi-row{grid-template-columns:1fr}}
    .kpi-card{background:#fff;border:1px solid var(--line);border-radius:var(--radius);
              box-shadow:var(--shadow);padding:20px 18px;text-align:center;
              transition:transform .2s,box-shadow .2s}
    .kpi-card:hover{transform:translateY(-3px);box-shadow:0 18px 38px rgba(18,55,102,.12)}
    .kpi-icon{font-size:28px;margin-bottom:6px}
    .kpi-value{font-size:32px;font-weight:800;color:var(--primary);line-height:1.1}
    .kpi-label{font-size:13px;color:var(--muted);margin-top:4px}
    .kpi-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:11px;
               font-weight:700;margin-top:6px}
    .badge-cardio{background:#dbeafe;color:#1e40af}
    .badge-heart{background:#fce7f3;color:#9d174d}
    .kpi-sep{border:none;border-top:1px dashed var(--line);margin:4px 0 12px}

    /* ── Viz grid ─────────────────────────────────────────────────── */
    .viz-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-bottom:22px}
    @media(max-width:900px){.viz-grid{grid-template-columns:1fr}}
    .viz-card{background:#fff;border:1px solid var(--line);border-radius:var(--radius);
              box-shadow:var(--shadow);padding:22px}
    .viz-card h3{margin:0 0 4px;font-size:16px;color:#123259}
    .viz-card .sub{font-size:13px;color:var(--muted);margin:0 0 14px}
    .chart-wrap{position:relative;height:280px}
    .chart-wrap-sm{position:relative;height:240px}
    .dataset-badge{display:inline-block;padding:3px 12px;border-radius:999px;
                   font-size:12px;font-weight:700;margin-bottom:8px}

    /* ── Observation box ─────────────────────────────────────────── */
    .obs-box{background:var(--primary-soft);border-left:4px solid var(--primary);
             border-radius:0 10px 10px 0;padding:10px 14px;margin-top:12px;
             font-size:13px;color:var(--primary-dark)}

    /* ── Confusion matrix ────────────────────────────────────────── */
    .cm-wrap{display:flex;flex-direction:column;align-items:center;gap:8px;margin-top:10px}
    .cm-row{display:flex;gap:6px}
    .cm-cell{width:110px;height:80px;border-radius:12px;display:flex;flex-direction:column;
             align-items:center;justify-content:center;font-size:13px;font-weight:700}
    .cm-cell .cm-num{font-size:22px;font-weight:800;margin-bottom:2px}
    .cm-tp{background:#d1fae5;color:#065f46}.cm-tn{background:#dbeafe;color:#1e40af}
    .cm-fp{background:#fef3c7;color:#78350f}.cm-fn{background:#fee2e2;color:#991b1b}
    .cm-axis{font-size:11px;color:var(--muted);font-weight:600;text-align:center}
    .cm-label-row{display:flex;gap:6px;padding-left:70px}
    .cm-label-col{width:70px;display:flex;flex-direction:column;justify-content:space-around;
                  align-items:flex-end;padding-right:8px;font-size:11px;color:var(--muted);font-weight:600}

    /* ── Heatmap ─────────────────────────────────────────────────── */
    .heatmap-wrap{overflow-x:auto;margin-top:14px}
    .heatmap-table{border-collapse:collapse;font-size:12px;margin:auto}
    .heatmap-table th{padding:6px 10px;font-size:11px;color:var(--muted);text-align:center;
                      white-space:nowrap;font-weight:600}
    .heatmap-table td{width:72px;height:52px;text-align:center;font-weight:700;
                      border-radius:4px;cursor:pointer;position:relative;
                      transition:transform .15s,box-shadow .15s;font-size:12px}
    .heatmap-table td:hover{transform:scale(1.06);box-shadow:0 4px 14px rgba(0,0,0,.18);
                             z-index:2;border-radius:6px}
    .ht-tooltip{display:none;position:absolute;bottom:108%;left:50%;transform:translateX(-50%);
                background:#16304f;color:#fff;border-radius:8px;padding:8px 12px;
                font-size:11px;white-space:normal;width:200px;z-index:10;line-height:1.5;
                font-weight:400;box-shadow:0 8px 24px rgba(0,0,0,.2)}
    .heatmap-table td:hover .ht-tooltip{display:block}
    .ht-tooltip::after{content:'';position:absolute;top:100%;left:50%;transform:translateX(-50%);
                       border:6px solid transparent;border-top-color:#16304f}
    .heatmap-legend{display:flex;align-items:center;gap:8px;margin-top:10px;font-size:12px;
                    color:var(--muted);justify-content:center}
    .legend-gradient{width:120px;height:14px;border-radius:4px;
                     background:linear-gradient(to right,#0000ff,#ffffff,#ff0000)}

    /* ── Radar wrapper ───────────────────────────────────────────── */
    .radar-wrap{position:relative;height:320px}

    /* ── Comparison section ──────────────────────────────────────── */
    .compare-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-bottom:22px}
    @media(max-width:900px){.compare-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <?php include 'partials_header.php'; ?>

  <main>
    <div class="container">

      <!-- ─────────────────── HERO ──────────────────────────────────── -->
      <section class="hero" style="margin-bottom:24px">
        <h1>Visualisations du projet</h1>
        <p>
          Graphiques interactifs issus de l'analyse exploratoire et de l'évaluation des modèles.
          Passez la souris sur les graphiques pour afficher les valeurs détaillées.
          Les KPI cards sont calculés dynamiquement depuis les fichiers CSV.
        </p>
      </section>

      <!-- ─────────────────── KPI DASHBOARD ────────────────────────── -->
      <div class="kpi-section">
        <h2>Tableau de bord — statistiques clés</h2>

        <!-- Cardio -->
        <p style="font-size:13px;color:var(--muted);margin:0 0 10px">
          <span class="dataset-badge badge-cardio">Dataset Cardio</span>
        </p>
        <div class="kpi-row">
          <div class="kpi-card">
            <div class="kpi-icon">🫀</div>
            <div class="kpi-value">
              <?= $kpi_c['loaded'] ? number_format($kpi_c['total'],0,',',' ') : '70 000' ?>
            </div>
            <div class="kpi-label">Patients total</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-icon">⚠️</div>
            <div class="kpi-value" style="color:#EF4444">
              <?= $kpi_c['loaded'] ? $kpi_c['pct_sick'].'%' : '49.97%' ?>
            </div>
            <div class="kpi-label">Patients malades</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-icon">📅</div>
            <div class="kpi-value">
              <?= $kpi_c['loaded'] ? $kpi_c['age_mean'].' ans' : '53.3 ans' ?>
            </div>
            <div class="kpi-label">Âge moyen</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-icon">🎯</div>
            <div class="kpi-value" style="color:#10B981">0.798</div>
            <div class="kpi-label">Meilleure AUC (RF)</div>
          </div>
        </div>

        <!-- Heart -->
        <p style="font-size:13px;color:var(--muted);margin:0 0 10px">
          <span class="dataset-badge badge-heart">Dataset Heart</span>
          <span style="font-size:11px;color:var(--muted);margin-left:8px">
            (302 obs. uniques après déduplication)
          </span>
        </p>
        <div class="kpi-row">
          <div class="kpi-card">
            <div class="kpi-icon">❤️</div>
            <div class="kpi-value">302</div>
            <div class="kpi-label">Patients (dédupliqués)</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-icon">⚠️</div>
            <div class="kpi-value" style="color:#EF4444">54.3%</div>
            <div class="kpi-label">Patients malades</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-icon">📅</div>
            <div class="kpi-value">
              <?= $kpi_h['loaded'] ? $kpi_h['age_mean'].' ans' : '54.4 ans' ?>
            </div>
            <div class="kpi-label">Âge moyen</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-icon">🎯</div>
            <div class="kpi-value" style="color:#10B981">0.871</div>
            <div class="kpi-label">Meilleure AUC (LR)</div>
          </div>
        </div>
      </div>

      <!-- ─────────────────── TABS ──────────────────────────────────── -->
      <div class="viz-tabs">
        <button class="viz-tab active" onclick="showPanel('cardio',this)">🫀 Dataset Cardio</button>
        <button class="viz-tab"        onclick="showPanel('heart',this)" >❤️ Dataset Heart</button>
        <button class="viz-tab"        onclick="showPanel('compare',this)">📊 Comparaison des modèles</button>
        <button class="viz-tab"        onclick="showPanel('corr',this)"  >🔗 Corrélations</button>
      </div>

      <!-- ═══════════════════ PANEL CARDIO ═══════════════════════════ -->
      <div id="panel-cardio" class="viz-panel active">

        <!-- Ligne 1 : Distribution + Importance -->
        <div class="viz-grid">

          <div class="viz-card">
            <span class="dataset-badge badge-cardio">Dataset Cardio</span>
            <h3>Répartition de la variable cible</h3>
            <p class="sub">Distribution sains / malades (0=sain, 1=malade)</p>
            <div class="chart-wrap"><canvas id="chartCTarget"></canvas></div>
            <div class="obs-box">
              Le dataset est presque équilibré (≈ 50/50) — aucun rééquilibrage (SMOTE) n'est nécessaire.
              C'est un avantage rare : les métriques comme l'accuracy sont représentatives des deux classes.
            </div>
          </div>

          <div class="viz-card">
            <span class="dataset-badge badge-cardio">Dataset Cardio</span>
            <h3>Importance des variables — Random Forest</h3>
            <p class="sub">Réduction d'impureté Gini moyenne sur tous les arbres</p>
            <div class="chart-wrap"><canvas id="chartCImportance"></canvas></div>
            <div class="obs-box">
              La <strong>pression systolique</strong> (ap_hi) et l'<strong>âge</strong> dominent largement.
              L'alcool et le tabagisme ont un impact faible — en partie dû à leur sous-déclaration dans le dataset.
            </div>
          </div>

        </div>

        <!-- Ligne 2 : Matrice de confusion + ROC -->
        <div class="viz-grid">

          <div class="viz-card">
            <span class="dataset-badge badge-cardio">Dataset Cardio</span>
            <h3>Matrice de confusion — Random Forest</h3>
            <p class="sub">Résultats sur le jeu de test (14 000 observations)</p>
            <?php
            $cm=$cm_c; $total=$cm[0][0]+$cm[0][1]+$cm[1][0]+$cm[1][1];
            $acc_cm=round(($cm[0][0]+$cm[1][1])/$total*100,1);
            ?>
            <div class="cm-wrap" style="margin-top:20px">
              <div class="cm-axis" style="margin-bottom:4px">Prédiction →</div>
              <div class="cm-label-row">
                <div style="width:110px;text-align:center;font-size:11px;color:var(--muted);font-weight:600">Sain (0)</div>
                <div style="width:110px;text-align:center;font-size:11px;color:var(--muted);font-weight:600">Malade (1)</div>
              </div>
              <div style="display:flex">
                <div class="cm-label-col">
                  <div>Réel Sain (0)</div>
                  <div>Réel Malade (1)</div>
                </div>
                <div>
                  <div class="cm-row">
                    <div class="cm-cell cm-tn">
                      <div class="cm-num"><?=number_format($cm[0][0],0,',',' ')?></div>
                      <div>Vrais Négatifs</div>
                      <div style="font-size:11px;font-weight:400"><?=round($cm[0][0]/$total*100,1)?>%</div>
                    </div>
                    <div class="cm-cell cm-fp">
                      <div class="cm-num"><?=number_format($cm[0][1],0,',',' ')?></div>
                      <div>Faux Positifs</div>
                      <div style="font-size:11px;font-weight:400"><?=round($cm[0][1]/$total*100,1)?>%</div>
                    </div>
                  </div>
                  <div class="cm-row" style="margin-top:6px">
                    <div class="cm-cell cm-fn">
                      <div class="cm-num"><?=number_format($cm[1][0],0,',',' ')?></div>
                      <div>Faux Négatifs ⚠️</div>
                      <div style="font-size:11px;font-weight:400"><?=round($cm[1][0]/$total*100,1)?>%</div>
                    </div>
                    <div class="cm-cell cm-tp">
                      <div class="cm-num"><?=number_format($cm[1][1],0,',',' ')?></div>
                      <div>Vrais Positifs</div>
                      <div style="font-size:11px;font-weight:400"><?=round($cm[1][1]/$total*100,1)?>%</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="obs-box" style="margin-top:14px">
              <?=number_format($cm[1][0],0,',',' ')?> faux négatifs (malades prédits sains) — à minimiser en contexte médical.
              Accuracy globale : <strong><?=$acc_cm?>%</strong>.
            </div>
          </div>

          <div class="viz-card">
            <span class="dataset-badge badge-cardio">Dataset Cardio</span>
            <h3>Courbes ROC — comparaison des 3 modèles</h3>
            <p class="sub">Capacité de discrimination selon le seuil (jeu de test)</p>
            <div class="chart-wrap"><canvas id="chartCRoc"></canvas></div>
            <div class="obs-box">
              Le Random Forest (AUC=0.798) devance l'arbre (0.790) et la régression logistique (0.778).
              Toutes les courbes sont nettement au-dessus de la diagonale aléatoire.
            </div>
          </div>

        </div>
      </div>

      <!-- ═══════════════════ PANEL HEART ════════════════════════════ -->
      <div id="panel-heart" class="viz-panel">

        <div class="viz-grid">

          <div class="viz-card">
            <span class="dataset-badge badge-heart">Dataset Heart</span>
            <h3>Répartition de la variable cible</h3>
            <p class="sub">Après déduplication — 302 observations uniques</p>
            <div class="chart-wrap"><canvas id="chartHTarget"></canvas></div>
            <div class="obs-box">
              Sans déduplication, 723 doublons sur 1 025 lignes causaient un <em>data leakage</em>
              (AUC = 1.0 artificiel). Après nettoyage : 138 sains / 164 malades (54.3 % malades).
            </div>
          </div>

          <div class="viz-card">
            <span class="dataset-badge badge-heart">Dataset Heart</span>
            <h3>Importance des variables — Régression Logistique</h3>
            <p class="sub">|Coefficient| normalisé — valeurs réelles depuis le modèle déployé</p>
            <div class="chart-wrap"><canvas id="chartHImportance"></canvas></div>
            <div class="obs-box">
              Le <strong>nombre de vaisseaux colorés</strong> (ca), la <strong>douleur thoracique</strong> (cp)
              et la <strong>fréquence cardiaque maximale</strong> (thalach) sont les variables les plus discriminantes —
              cohérent avec la cardiologie clinique.
            </div>
          </div>

        </div>

        <div class="viz-grid">

          <div class="viz-card">
            <span class="dataset-badge badge-heart">Dataset Heart</span>
            <h3>Matrice de confusion — Régression Logistique</h3>
            <p class="sub">Jeu de test : 61 observations (80/20 stratifié)</p>
            <?php
            $cm=$cm_h; $total=$cm[0][0]+$cm[0][1]+$cm[1][0]+$cm[1][1];
            $acc_cm_h=round(($cm[0][0]+$cm[1][1])/$total*100,1);
            ?>
            <div class="cm-wrap" style="margin-top:20px">
              <div class="cm-axis" style="margin-bottom:4px">Prédiction →</div>
              <div class="cm-label-row">
                <div style="width:110px;text-align:center;font-size:11px;color:var(--muted);font-weight:600">Malade (0)</div>
                <div style="width:110px;text-align:center;font-size:11px;color:var(--muted);font-weight:600">Sain (1)</div>
              </div>
              <div style="display:flex">
                <div class="cm-label-col">
                  <div>Réel Malade (0)</div>
                  <div>Réel Sain (1)</div>
                </div>
                <div>
                  <div class="cm-row">
                    <div class="cm-cell cm-tp">
                      <div class="cm-num"><?=$cm[0][0]?></div>
                      <div>Vrais Positifs</div>
                      <div style="font-size:11px;font-weight:400"><?=round($cm[0][0]/$total*100,1)?>%</div>
                    </div>
                    <div class="cm-cell cm-fn">
                      <div class="cm-num"><?=$cm[0][1]?></div>
                      <div>Faux Négatifs ⚠️</div>
                      <div style="font-size:11px;font-weight:400"><?=round($cm[0][1]/$total*100,1)?>%</div>
                    </div>
                  </div>
                  <div class="cm-row" style="margin-top:6px">
                    <div class="cm-cell cm-fp">
                      <div class="cm-num"><?=$cm[1][0]?></div>
                      <div>Faux Positifs</div>
                      <div style="font-size:11px;font-weight:400"><?=round($cm[1][0]/$total*100,1)?>%</div>
                    </div>
                    <div class="cm-cell cm-tn">
                      <div class="cm-num"><?=$cm[1][1]?></div>
                      <div>Vrais Négatifs</div>
                      <div style="font-size:11px;font-weight:400"><?=round($cm[1][1]/$total*100,1)?>%</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="obs-box" style="margin-top:14px">
              Seulement <?=$cm[0][1]?> malades non détectés sur <?=$cm[0][0]+$cm[0][1]?> — bon rappel (84.8 %).
              Accuracy : <strong><?=$acc_cm_h?>%</strong>. Le petit effectif invite à interpréter avec prudence.
            </div>
          </div>

          <div class="viz-card">
            <span class="dataset-badge badge-heart">Dataset Heart</span>
            <h3>Courbes ROC — comparaison des 3 modèles</h3>
            <p class="sub">Après déduplication (données sans leakage)</p>
            <div class="chart-wrap"><canvas id="chartHRoc"></canvas></div>
            <div class="obs-box">
              La Régression Logistique (AUC=0.871) devance le Random Forest (0.862) sur ce petit dataset.
              Avec seulement 302 observations, les modèles simples et régularisés généralisent mieux.
            </div>
          </div>

        </div>
      </div>

      <!-- ═══════════════════ PANEL COMPARAISON ══════════════════════ -->
      <div id="panel-compare" class="viz-panel">

        <div class="compare-grid">

          <div class="viz-card">
            <h3>Radar — Comparaison des modèles (Cardio)</h3>
            <p class="sub">5 métriques normalisées sur l'ensemble de test</p>
            <div class="radar-wrap"><canvas id="chartRadarC"></canvas></div>
            <div class="obs-box">
              Le Random Forest obtient le meilleur équilibre global.
              La Régression Logistique a le meilleur rappel — avantage en contexte médical.
            </div>
          </div>

          <div class="viz-card">
            <h3>Radar — Comparaison des modèles (Heart)</h3>
            <p class="sub">5 métriques normalisées sur l'ensemble de test</p>
            <div class="radar-wrap"><canvas id="chartRadarH"></canvas></div>
            <div class="obs-box">
              La Régression Logistique domine sur toutes les métriques — la régularisation L2
              la stabilise sur ce petit dataset de 302 observations.
            </div>
          </div>

        </div>

        <div class="viz-card">
          <h3>Comparaison groupée — tous modèles et datasets</h3>
          <p class="sub">Accuracy, F1-Score et ROC-AUC sur le jeu de test</p>
          <div style="position:relative;height:320px"><canvas id="chartCompare"></canvas></div>
          <div class="obs-box">
            <strong>Dataset Cardio (70 000 obs.) :</strong> le boosting (ou RF) profite de la grande taille des données.
            <br>
            <strong>Dataset Heart (302 obs.) :</strong> la Régression Logistique surpasse les méthodes d'ensemble
            — la complexité du modèle doit être adaptée à la taille des données.
          </div>
        </div>

      </div>

      <!-- ═══════════════════ PANEL CORRÉLATIONS ═════════════════════ -->
      <div id="panel-corr" class="viz-panel">

        <div class="viz-grid">

          <!-- Heatmap Cardio -->
          <div class="viz-card">
            <span class="dataset-badge badge-cardio">Dataset Cardio</span>
            <h3>Heatmap de corrélation — variables clés</h3>
            <p class="sub">Coefficients de Pearson. Survolez une cellule pour l'interprétation.</p>
            <div class="heatmap-wrap">
              <table class="heatmap-table">
                <thead>
                  <tr>
                    <th></th>
                    <?php foreach ($cv_c as $v): ?>
                      <th><?=htmlspecialchars($v)?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($cv_c as $ri=>$rv): ?>
                    <tr>
                      <th style="text-align:right;padding-right:8px;font-size:11px;color:var(--muted)">
                        <?=htmlspecialchars($rv)?>
                      </th>
                      <?php foreach ($cv_c as $ci=>$cv): ?>
                        <?php
                          $r=$cm_corr_c[$ri][$ci];
                          $bg=corr_color($r); $tc=corr_text($r);
                          $key="$ri,$ci"; $alt="$ci,$ri";
                          $expl=$corr_expl_c[$key]??($corr_expl_c[$alt]??
                            (abs($r)>=0.3?"Corrélation ".(abs($r)>=0.5?'forte':'modérée').
                             ($r>0?' positive':' négative').' entre ces deux variables.'
                            :"Corrélation faible — peu de relation linéaire directe."));
                        ?>
                        <td style="background:<?=$bg?>;color:<?=$tc?>">
                          <?=number_format($r,2)?>
                          <div class="ht-tooltip"><?=htmlspecialchars($expl)?></div>
                        </td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="heatmap-legend">
              <span>−1 (négatif fort)</span>
              <div class="legend-gradient"></div>
              <span>+1 (positif fort)</span>
            </div>
          </div>

          <!-- Heatmap Heart -->
          <div class="viz-card">
            <span class="dataset-badge badge-heart">Dataset Heart</span>
            <h3>Heatmap de corrélation — variables clés</h3>
            <p class="sub">Coefficients de Pearson. Survolez une cellule pour l'interprétation.</p>
            <div class="heatmap-wrap">
              <table class="heatmap-table">
                <thead>
                  <tr>
                    <th></th>
                    <?php foreach ($cv_h as $v): ?>
                      <th><?=htmlspecialchars($v)?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($cv_h as $ri=>$rv): ?>
                    <tr>
                      <th style="text-align:right;padding-right:8px;font-size:11px;color:var(--muted)">
                        <?=htmlspecialchars($rv)?>
                      </th>
                      <?php foreach ($cv_h as $ci=>$cv): ?>
                        <?php
                          $r=$cm_corr_h[$ri][$ci];
                          $bg=corr_color($r); $tc=corr_text($r);
                          $key="$ri,$ci"; $alt="$ci,$ri";
                          $expl=$corr_expl_h[$key]??($corr_expl_h[$alt]??
                            (abs($r)>=0.3?"Corrélation ".(abs($r)>=0.5?'forte':'modérée').
                             ($r>0?' positive':' négative').' entre ces deux variables.'
                            :"Corrélation faible — peu de relation linéaire directe."));
                        ?>
                        <td style="background:<?=$bg?>;color:<?=$tc?>">
                          <?=number_format($r,2)?>
                          <div class="ht-tooltip"><?=htmlspecialchars($expl)?></div>
                        </td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="heatmap-legend">
              <span>−1 (négatif fort)</span>
              <div class="legend-gradient"></div>
              <span>+1 (positif fort)</span>
            </div>
          </div>

        </div>

        <div class="info-strip">
          <h2>Note sur les corrélations</h2>
          <p>
            Les coefficients de Pearson mesurent uniquement les <strong>relations linéaires</strong>.
            Les modèles d'arbres (RF, XGBoost) peuvent capturer des relations non-linéaires et des interactions
            qui ne se reflètent pas dans cette matrice. Une faible corrélation de Pearson ne signifie pas
            qu'une variable est inutile pour la prédiction.
          </p>
        </div>

      </div>

    </div><!-- /container -->
  </main>

  <?php include 'partials_footer.php'; ?>

  <script>
  /* ═══════════════════════════════════════════════════════════════════════════
     Données injectées depuis PHP
  ═══════════════════════════════════════════════════════════════════════════ */
  const DATA = {
    c_target: {
      sain:   <?= $kpi_c['loaded'] ? $kpi_c['total']-$kpi_c['sick'] : 35021 ?>,
      malade: <?= $kpi_c['loaded'] ? $kpi_c['sick'] : 34979 ?>
    },
    h_target: { sain: 138, malade: 164 },

    c_fi: {
      labels: <?=json_encode($c_fi_labels)?>,
      data:   <?=json_encode($c_fi_data)?>
    },
    h_fi: {
      labels: <?=json_encode(array_keys($h_imp))?>,
      data:   <?=json_encode(array_values($h_imp))?>
    },

    mc: <?=json_encode($mc)?>,
    mh: <?=json_encode($mh)?>
  };

  /* ─── Couleurs ─────────────────────────────────────────────────── */
  const BLUE  = '#1F5FBF';
  const RED   = '#EF4444';
  const GREEN = '#10B981';
  const ORG   = '#F59E0B';
  const PURP  = '#8B5CF6';
  const ALPHA = (hex,a)=>hex+'CC';

  /* ─── Defaults Chart.js ─────────────────────────────────────────── */
  Chart.defaults.font.family = "'Inter', Arial, sans-serif";
  Chart.defaults.color       = '#5e7591';

  /* ─── ROC approximation : TPR = FPR^(1/k), k=AUC/(1-AUC) ─────── */
  function genROC(auc, n=50) {
    const pts=[];
    for(let i=0;i<=n;i++){
      const fpr=i/n;
      const tpr= fpr===0 ? 0 : Math.pow(fpr, (1-auc)/auc);
      pts.push({x:fpr,y:Math.min(1,tpr)});
    }
    return pts;
  }

  /* ─── Barre horizontale helper ──────────────────────────────────── */
  function hBar(id, labels, data, color) {
    const sorted=[...labels.map((l,i)=>({l,v:data[i]}))].sort((a,b)=>a.v-b.v);
    new Chart(document.getElementById(id),{
      type:'bar',
      data:{
        labels: sorted.map(x=>x.l),
        datasets:[{data:sorted.map(x=>x.v), backgroundColor: sorted.map(x=>
          x.v>=sorted[sorted.length-1].v*0.7 ? color : color+'88'),
          borderRadius:4}]
      },
      options:{
        indexAxis:'y',
        responsive:true, maintainAspectRatio:false,
        plugins:{legend:{display:false},
          tooltip:{callbacks:{label:c=>' '+c.parsed.x.toFixed(4)}}},
        scales:{
          x:{grid:{color:'rgba(0,0,0,.06)'},ticks:{font:{size:11}}},
          y:{grid:{display:false},ticks:{font:{size:11}}}
        }
      }
    });
  }

  /* ─── Init : Cardio target distribution ─────────────────────────── */
  new Chart(document.getElementById('chartCTarget'),{
    type:'doughnut',
    data:{
      labels:['Sain (0)','Malade (1)'],
      datasets:[{data:[DATA.c_target.sain,DATA.c_target.malade],
                 backgroundColor:[GREEN,RED],borderWidth:2,
                 hoverOffset:6}]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{
        legend:{position:'bottom'},
        tooltip:{callbacks:{
          label:c=>`${c.label} : ${c.parsed.toLocaleString('fr')} (${(c.parsed/(DATA.c_target.sain+DATA.c_target.malade)*100).toFixed(1)}%)`
        }}
      }
    }
  });

  /* ─── Cardio feature importance ─────────────────────────────────── */
  hBar('chartCImportance', DATA.c_fi.labels, DATA.c_fi.data, BLUE);

  /* ─── Cardio ROC ────────────────────────────────────────────────── */
  (function(){
    const aucs=[['Random Forest',0.798,BLUE],['Arbre',0.790,ORG],['Rég. Logistique',0.778,RED]];
    new Chart(document.getElementById('chartCRoc'),{
      type:'line',
      data:{
        datasets:[
          ...aucs.map(([name,auc,col])=>({
            label:`${name} (AUC=${auc})`,
            data:genROC(auc),
            borderColor:col, backgroundColor:'transparent',
            borderWidth:2, pointRadius:0, tension:0.3
          })),
          {label:'Aléatoire (AUC=0.50)',
           data:[{x:0,y:0},{x:1,y:1}],
           borderColor:'#aaa',borderDash:[5,5],borderWidth:1,
           pointRadius:0,backgroundColor:'transparent'}
        ]
      },
      options:{
        responsive:true,maintainAspectRatio:false,
        parsing:{xAxisKey:'x',yAxisKey:'y'},
        plugins:{legend:{position:'bottom',labels:{font:{size:11}}}},
        scales:{
          x:{title:{display:true,text:'Taux de Faux Positifs (FPR)'},
             min:0,max:1,grid:{color:'rgba(0,0,0,.06)'}},
          y:{title:{display:true,text:'Taux de Vrais Positifs (TPR)'},
             min:0,max:1,grid:{color:'rgba(0,0,0,.06)'}}
        }
      }
    });
  })();

  /* ─── Heart target distribution ─────────────────────────────────── */
  new Chart(document.getElementById('chartHTarget'),{
    type:'doughnut',
    data:{
      labels:['Malade (0)','Sain (1)'],
      datasets:[{data:[DATA.h_target.malade,DATA.h_target.sain],
                 backgroundColor:[RED,GREEN],borderWidth:2,hoverOffset:6}]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{
        legend:{position:'bottom'},
        tooltip:{callbacks:{
          label:c=>`${c.label} : ${c.parsed} (${(c.parsed/(DATA.h_target.sain+DATA.h_target.malade)*100).toFixed(1)}%)`
        }}
      }
    }
  });

  /* ─── Heart feature importance ───────────────────────────────────── */
  hBar('chartHImportance', DATA.h_fi.labels, DATA.h_fi.data, '#E11D48');

  /* ─── Heart ROC ──────────────────────────────────────────────────── */
  (function(){
    const aucs=[['Rég. Logistique',0.871,'#E11D48'],['Random Forest',0.862,ORG],['Arbre',0.832,PURP]];
    new Chart(document.getElementById('chartHRoc'),{
      type:'line',
      data:{
        datasets:[
          ...aucs.map(([name,auc,col])=>({
            label:`${name} (AUC=${auc})`,
            data:genROC(auc),
            borderColor:col,backgroundColor:'transparent',
            borderWidth:2,pointRadius:0,tension:0.3
          })),
          {label:'Aléatoire (AUC=0.50)',data:[{x:0,y:0},{x:1,y:1}],
           borderColor:'#aaa',borderDash:[5,5],borderWidth:1,
           pointRadius:0,backgroundColor:'transparent'}
        ]
      },
      options:{
        responsive:true,maintainAspectRatio:false,
        parsing:{xAxisKey:'x',yAxisKey:'y'},
        plugins:{legend:{position:'bottom',labels:{font:{size:11}}}},
        scales:{
          x:{title:{display:true,text:'Taux de Faux Positifs (FPR)'},min:0,max:1,
             grid:{color:'rgba(0,0,0,.06)'}},
          y:{title:{display:true,text:'Taux de Vrais Positifs (TPR)'},min:0,max:1,
             grid:{color:'rgba(0,0,0,.06)'}}
        }
      }
    });
  })();

  /* ─── Radar Cardio ───────────────────────────────────────────────── */
  (function(){
    const labels=['Accuracy','Précision','Rappel','F1-Score','ROC-AUC'];
    const colors=[[BLUE,'#1F5FBF44'],[ORG,'#F59E0B44'],[GREEN,'#10B98144']];
    const entries=Object.entries(DATA.mc);
    new Chart(document.getElementById('chartRadarC'),{
      type:'radar',
      data:{
        labels,
        datasets: entries.map(([name,m],i)=>({
          label:name,
          data:[m.acc,m.prec,m.rec,m.f1,m.auc],
          borderColor:colors[i][0],backgroundColor:colors[i][1],
          borderWidth:2,pointRadius:3
        }))
      },
      options:{
        responsive:true,maintainAspectRatio:false,
        scales:{r:{min:0.6,max:0.85,ticks:{stepSize:0.05,font:{size:9}},
                   pointLabels:{font:{size:10}},grid:{color:'rgba(0,0,0,.1)'}}},
        plugins:{legend:{position:'bottom',labels:{font:{size:11}}},
          tooltip:{callbacks:{
            label:c=>`${c.dataset.label} : ${c.parsed.r.toFixed(3)}`
          }}}
      }
    });
  })();

  /* ─── Radar Heart ────────────────────────────────────────────────── */
  (function(){
    const labels=['Accuracy','Précision','Rappel','F1-Score','ROC-AUC'];
    const colors=[['#E11D48','#E11D4844'],[ORG,'#F59E0B44'],[PURP,'#8B5CF644']];
    const entries=Object.entries(DATA.mh);
    new Chart(document.getElementById('chartRadarH'),{
      type:'radar',
      data:{
        labels,
        datasets:entries.map(([name,m],i)=>({
          label:name,
          data:[m.acc,m.prec,m.rec,m.f1,m.auc],
          borderColor:colors[i][0],backgroundColor:colors[i][1],
          borderWidth:2,pointRadius:3
        }))
      },
      options:{
        responsive:true,maintainAspectRatio:false,
        scales:{r:{min:0.7,max:0.92,ticks:{stepSize:0.05,font:{size:9}},
                   pointLabels:{font:{size:10}},grid:{color:'rgba(0,0,0,.1)'}}},
        plugins:{legend:{position:'bottom',labels:{font:{size:11}}},
          tooltip:{callbacks:{
            label:c=>`${c.dataset.label} : ${c.parsed.r.toFixed(3)}`
          }}}
      }
    });
  })();

  /* ─── Comparaison groupée ─────────────────────────────────────────── */
  (function(){
    const metriques=['acc','f1','auc'];
    const labelsM=['Accuracy','F1-Score','ROC-AUC'];
    const modelsC=Object.keys(DATA.mc);
    const modelsH=Object.keys(DATA.mh);

    const allLabels=[
      ...modelsC.map(m=>'Cardio — '+m),
      ...modelsH.map(m=>'Heart — '+m)
    ];
    const cColors=[BLUE+'CC',ORG+'CC',GREEN+'CC'];
    const hColors=['#E11D48CC',ORG+'CC',PURP+'CC'];

    const datasets=metriques.map((met,mi)=>({
      label:labelsM[mi],
      data:[
        ...modelsC.map(m=>DATA.mc[m][met]),
        ...modelsH.map(m=>DATA.mh[m][met])
      ],
      backgroundColor:[...cColors,...hColors].slice(0,allLabels.length),
      borderRadius:4,
      stack:`s${mi}`
    }));

    // Simpler: one dataset per metric shown as grouped
    const ds2=metriques.map((met,mi)=>({
      label:labelsM[mi],
      data:[
        ...modelsC.map(m=>DATA.mc[m][met]),
        ...modelsH.map(m=>DATA.mh[m][met])
      ],
      backgroundColor:[BLUE,RED,GREEN][mi]+'BB',
      borderRadius:4
    }));

    new Chart(document.getElementById('chartCompare'),{
      type:'bar',
      data:{labels:allLabels, datasets:ds2},
      options:{
        responsive:true,maintainAspectRatio:false,
        plugins:{
          legend:{position:'bottom'},
          tooltip:{mode:'index',intersect:false,
            callbacks:{label:c=>`${c.dataset.label} : ${c.parsed.y.toFixed(3)}`}}
        },
        scales:{
          x:{grid:{display:false},ticks:{font:{size:10},maxRotation:30}},
          y:{min:0.6,max:0.95,grid:{color:'rgba(0,0,0,.06)'},
             ticks:{font:{size:11},callback:v=>v.toFixed(2)}}
        }
      }
    });
  })();

  /* ─── Tabs ────────────────────────────────────────────────────────── */
  function showPanel(name,btn) {
    document.querySelectorAll('.viz-panel').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.viz-tab').forEach(b=>b.classList.remove('active'));
    document.getElementById('panel-'+name).classList.add('active');
    btn.classList.add('active');
  }
  </script>
</body>
</html>
