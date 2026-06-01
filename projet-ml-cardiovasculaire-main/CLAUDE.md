# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

**CardioPredict** — university ML project (L3 MIASHS, Université Paul Valéry Montpellier, 2025-2026).  
A PHP web site for cardiovascular risk prediction backed by two ML models, plus two Jupyter notebooks for the data science work.  
Live deployment: **cardio-predict.fr**

## Running locally

- Requires: PHP 8+, Python 3.11, a local web server (XAMPP/MAMP/Laragon).
- Place `site/` inside the server's web root (e.g. `htdocs/` for XAMPP).
- Access via `http://localhost/site/`.
- On Windows, PHP calls Python via `"C:\Windows\py.exe" -3.11`. On Linux/Mac it uses `python3`.
- No build step, no package manager, no test suite.

## Architecture

### Prediction pipeline

```
[PHP form]  →  site/tmp/prediction_input_{mode}.json
           →  shell_exec("predict.py {cardio|heart} {input.json}")
           →  stdout JSON  →  PHP renders result
```

`shell_exec()` must be enabled on the server. The script writes a temp JSON to `site/tmp/` before calling Python.

### Python inference (`site/ml/predict.py`)

- **Zero external dependencies** — stdlib only (`json`, `math`, `sys`, `pathlib`). This is intentional: the site is deployed on shared hosting that may not have scikit-learn.
- Implements logistic regression (`predict_logistic`) and random forest (`predict_tree` / `predict_random_forest`) from scratch using the parameters stored in the model JSON files.
- Preprocessing (median imputation + StandardScaler) is also reimplemented manually using `median`, `mean`, and `scale` arrays saved in each model JSON.
- **Critical quirk — Heart dataset**: `target=1` means *healthy* in johnsmith88's dataset (inverted convention). `predict.py` has `invert_proba: True` for `heart`, so it returns `1 - prob_model` to get the disease probability. Never remove this inversion.

### Model files

| File | Type | Notes |
|------|------|-------|
| `site/ml/models/cardio_model.json` | Random Forest | 200 trees, max_depth 6–10 |
| `site/ml/models/heart_model.json` | Logistic Regression | L2 regularization |
| `site/ml/metadata/cardio_model_info.json` | Metrics + feature list | Read by predict.py and returned in JSON response |
| `site/ml/metadata/heart_model_info.json` | Metrics + feature list | Same |

Models are trained once in the notebooks and exported; `predict.py` never retrains.

### PHP site structure

- `partials_header.php` / `partials_footer.php` — shared layout. Header uses `$page` variable for active nav link.
- `index.php` — landing page with stats and dataset cards.
- `prediction.php` — form + result panel. `$formConfigs` array defines both modes (`cardio` / `heart`) with fields, labels, tooltips, defaults. Result is rendered directly on the same page after POST.
- `visualisations.php` — tabbed display of static PNG charts from `assets/img/`. Tab switching is pure JS (`showPanel()`).
- `methode.php` — methodology explanation and comparative metrics table.
- `assets/css/style.css` — custom design system (CSS variables in `:root`, no framework).
- `assets/js/app.js` — small canvas bar chart demo on the home page (currently not wired to real data).

### Datasets

- `notebooks/cardio.csv` (70 000 rows) — Sulianova/Kaggle, Russian data, age stored **in days** (convert: `age_years * 365.25`).
- `notebooks/heart.csv` (1 025 raw → 302 unique) — johnsmith88/Kaggle, 4 UCI sources concatenated. 723 duplicates cause data leakage if not removed; always deduplicate before train/test split.
- Copies also live in `site/data/` for potential server-side use.

### Notebooks

| Notebook | Content |
|----------|---------|
| `notebooks/01_exploration_visualisation.ipynb` | EDA, distributions, correlation heatmap, class balance, outlier analysis |
| `notebooks/02_modelisation_prediction.ipynb` | Preprocessing pipeline, 80/20 stratified split (seed=42), 5-fold CV, LR/DT/RF comparison, metrics, ROC, confusion matrices |

Charts saved as PNGs go to `site/assets/img/` with naming convention `{dataset}_{chart_type}.png` (e.g. `cardio_roc_curve.png`, `heart_feature_importance.png`).

## Key design decisions

- **Age in days (cardio only)**: the cardio dataset stores age in days. `predict.py` converts input years → days (`val * 365.25`) before inference. The heart dataset uses years directly.
- **Model selection criterion**: ROC-AUC (threshold-independent), F1-Score as tiebreaker.
- **No SMOTE / rebalancing**: both datasets are ~50/50, so no rebalancing was needed.
- **Deduplication is mandatory** for the heart dataset before any split — without it, test accuracy inflates to 1.0 (data leakage).
- **Shared hosting constraints** drive the zero-dependency Python approach and the JSON model export format.
