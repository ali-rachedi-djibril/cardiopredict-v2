"""
predict.py — version sans dependances externes
Fonctionne avec Python standard uniquement (json, math, sys, pathlib).

NOTE IMPORTANTE — Dataset Heart :
Dans le dataset johnsmith88/heart-disease-dataset (Kaggle), la variable
cible est encodee de facon inversee par rapport a l'intuition :
  target=1 → patient SAIN
  target=0 → patient MALADE
Le modele a donc appris a predire la SANTE (target=1).
Pour obtenir la probabilite de MALADIE, on calcule : prob_maladie = 1 - prob_modele.
"""
import json
import math
import sys
from pathlib import Path

# Importances features RF approx. issues de l'analyse (notebook 02)
_RF_IMPORTANCES = {
    'cardio': {
        'ap_hi':0.283,'age':0.216,'weight':0.120,'ap_lo':0.095,
        'cholesterol':0.068,'gluc':0.043,'height':0.040,
        'active':0.028,'gender':0.022,'smoke':0.013,'alco':0.007
    }
}

BASE_DIR  = Path(__file__).resolve().parent.parent
MODEL_DIR = BASE_DIR / "ml" / "models"
META_DIR  = BASE_DIR / "ml" / "metadata"

CONFIG = {
    "cardio": {
        "dataset_name":   "Dataset cardio",
        "features":       ["age","gender","height","weight","ap_hi","ap_lo",
                           "cholesterol","gluc","smoke","alco","active"],
        "model_file":     "cardio_model.json",
        "meta_file":      "cardio_model_info.json",
        "positive_label": "Risque cardiovasculaire detecte",
        "negative_label": "Risque cardiovasculaire non detecte",
        "age_in_days":    True,
        "invert_proba":   False,  # target=1 = malade dans ce dataset
    },
    "heart": {
        "dataset_name":   "Dataset heart",
        "features":       ["age","sex","cp","trestbps","chol","fbs","restecg",
                           "thalach","exang","oldpeak","slope","ca","thal"],
        "model_file":     "heart_model.json",
        "meta_file":      "heart_model_info.json",
        "positive_label": "Maladie cardiaque detectee",
        "negative_label": "Maladie cardiaque non detectee",
        "age_in_days":    False,
        "invert_proba":   True,   # target=1=SAIN dans ce dataset → on inverse
    },
}


def emit(payload):
    print(json.dumps(payload, ensure_ascii=False))
    raise SystemExit(0)


def preprocess(values, median, mean, scale):
    result = []
    for i, v in enumerate(values):
        if v is None or math.isnan(v):
            v = median[i]
        result.append((v - mean[i]) / scale[i])
    return result


def sigmoid(x):
    if x >= 0:
        return 1.0 / (1.0 + math.exp(-x))
    ex = math.exp(x)
    return ex / (1.0 + ex)


def predict_logistic(model, x_scaled):
    dot = sum(c * xi for c, xi in zip(model['coef'], x_scaled))
    return sigmoid(dot + model['intercept'])


def predict_tree(tree, x_scaled):
    node = 0
    cl, cr, f, th, v = tree['cl'], tree['cr'], tree['f'], tree['th'], tree['v']
    while cl[node] != -1:
        node = cl[node] if x_scaled[f[node]] <= th[node] else cr[node]
    neg, pos = v[node][0], v[node][1]
    total = neg + pos
    return pos / total if total > 0 else 0.5


def predict_random_forest(model, x_scaled):
    probs = [predict_tree(t, x_scaled) for t in model['trees']]
    return sum(probs) / len(probs)


def load_model(dataset_key):
    cfg        = CONFIG[dataset_key]
    model_path = MODEL_DIR / cfg['model_file']
    meta_path  = META_DIR  / cfg['meta_file']

    if not model_path.exists():
        emit({'status': 'error',
              'message': f"Fichier modele introuvable : {cfg['model_file']}"})

    with open(model_path, 'r') as f:
        model = json.load(f)

    meta = {}
    if meta_path.exists():
        with open(meta_path, 'r', encoding='utf-8') as f:
            meta = json.load(f)

    return cfg, model, meta


def build_user_values(payload, cfg):
    features = cfg['features']
    missing  = [col for col in features
                if col not in payload or str(payload[col]).strip() == ""]
    if missing:
        emit({"status": "error",
              "message": "Champs manquants : " + ", ".join(missing)})

    values = []
    for col in features:
        raw = str(payload[col]).replace(",", ".")
        try:
            val = float(raw)
        except Exception:
            emit({"status": "error",
                  "message": f"Valeur invalide pour {col} : {payload[col]}"})
        if col == 'age' and cfg.get('age_in_days'):
            val = round(val * 365.25)
        values.append(val)
    return values


def compute_contributions(dataset_key, cfg, model, x_scaled):
    """Contributions signees par feature (% de l'explication totale).
    Positif = pousse vers 'malade', Negatif = protecteur.
    """
    features = cfg['features']
    invert   = cfg.get('invert_proba', False)

    if model['type'] == 'logistic_regression':
        # Contribution exacte au log-odds : coef[i] * x_scaled[i]
        contribs = [model['coef'][i] * x_scaled[i] for i in range(len(features))]
        if invert:
            contribs = [-c for c in contribs]   # flip pour direction maladie
    elif model['type'] == 'random_forest':
        imp_map = _RF_IMPORTANCES.get(dataset_key, {})
        contribs = []
        for i, f in enumerate(features):
            imp  = imp_map.get(f, 1.0 / len(features))
            dirn = 1 if x_scaled[i] > 0 else -1
            contribs.append(imp * dirn)
    else:
        contribs = [1.0 / len(features)] * len(features)

    total = sum(abs(c) for c in contribs) or 1.0
    return {f: round(c / total * 100, 1) for f, c in zip(features, contribs)}


def build_risk(prob):
    if prob < 0.35:
        return {'risk_label':     'Faible',
                'risk_label_css': 'faible',
                'message':        'Le modele estime un risque plutot faible pour le profil saisi.'}
    if prob < 0.65:
        return {'risk_label':     'Modere',
                'risk_label_css': 'modere',
                'message':        'Le modele detecte un risque intermediaire pour le profil saisi.'}
    return     {'risk_label':     'Eleve',
                'risk_label_css': 'eleve',
                'message':        'Le modele estime un risque eleve pour le profil saisi.'}


def main():
    if len(sys.argv) != 3:
        emit({'status': 'error',
              'message': 'Usage : predict.py <cardio|heart> <input_json>'})

    dataset_key     = sys.argv[1]
    input_json_path = Path(sys.argv[2])

    if dataset_key not in CONFIG:
        emit({'status': 'error', 'message': 'Type de test inconnu.'})
    if not input_json_path.exists():
        emit({'status': 'error', 'message': "Fichier d'entree introuvable."})

    with open(input_json_path, 'r', encoding='utf-8') as f:
        payload = json.load(f)

    cfg, model, meta = load_model(dataset_key)
    raw_values = build_user_values(payload, cfg)
    x_scaled   = preprocess(raw_values, model['median'], model['mean'], model['scale'])

    if model['type'] == 'logistic_regression':
        prob_model = predict_logistic(model, x_scaled)
    elif model['type'] == 'random_forest':
        prob_model = predict_random_forest(model, x_scaled)
    else:
        emit({'status': 'error',
              'message': f"Type de modele inconnu : {model['type']}"})

    # Pour Heart : le modele predit la SANTE (target=1=sain dans ce dataset).
    # On inverse pour obtenir la probabilite de MALADIE.
    if cfg.get('invert_proba'):
        prob = 1.0 - prob_model
    else:
        prob = prob_model

    pred = 1 if prob >= 0.5 else 0
    risk = build_risk(prob)
    contributions = compute_contributions(dataset_key, cfg, model, x_scaled)

    # Valeurs brutes saisies par l'utilisateur (age en annees pour cardio)
    input_display = {col: payload[col] for col in cfg['features'] if col in payload}

    emit({
        'status':              'ok',
        'dataset_name':        cfg['dataset_name'],
        'model_name':          meta.get('model_name', 'Modele'),
        'train_size':          meta.get('train_size'),
        'test_size':           meta.get('test_size'),
        'accuracy':            meta.get('accuracy'),
        'precision':           meta.get('precision'),
        'recall':              meta.get('recall'),
        'f1_score':            meta.get('f1_score'),
        'roc_auc':             meta.get('roc_auc'),
        'probability':         round(prob, 4),
        'probability_percent': round(prob * 100, 2),
        'prediction':          pred,
        'prediction_label':    cfg['positive_label'] if pred == 1
                               else cfg['negative_label'],
        'feature_order':       cfg['features'],
        'feature_contributions': contributions,
        'input_display_values':  input_display,
        **risk,
    })


if __name__ == '__main__':
    try:
        main()
    except SystemExit:
        raise
    except Exception as exc:
        emit({'status': 'error', 'message': f'Erreur : {exc}'})
