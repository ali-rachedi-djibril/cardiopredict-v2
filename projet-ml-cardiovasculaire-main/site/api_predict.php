<?php
/**
 * api_predict.php — Endpoint AJAX pour le simulateur What-if
 * Accepte : POST JSON { "mode": "cardio|heart", "values": { ... } }
 * Retourne : JSON identique à predict.py
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

// ── Sécurité minimale ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Méthode non autorisée']); exit;
}

$raw     = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload) || !isset($payload['mode'], $payload['values'])) {
    echo json_encode(['status'=>'error','message'=>'Paramètres manquants']); exit;
}

$mode = $payload['mode'];
if (!in_array($mode, ['cardio','heart'], true)) {
    echo json_encode(['status'=>'error','message'=>'Mode invalide']); exit;
}

$values = $payload['values'];
if (!is_array($values) || empty($values)) {
    echo json_encode(['status'=>'error','message'=>'Valeurs manquantes']); exit;
}

// ── Écriture du fichier temporaire ───────────────────────────────────────────
$tmpDir = __DIR__ . DIRECTORY_SEPARATOR . 'tmp';
if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0777, true); }

$uid       = bin2hex(random_bytes(8));
$inputFile = $tmpDir . DIRECTORY_SEPARATOR . 'api_' . $mode . '_' . $uid . '.json';
file_put_contents($inputFile, json_encode($values, JSON_UNESCAPED_UNICODE));

// ── Appel predict.py ─────────────────────────────────────────────────────────
$scriptPath    = __DIR__ . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR . 'predict.py';
$escapedScript = escapeshellarg($scriptPath);
$escapedInput  = escapeshellarg($inputFile);
$escapedMode   = escapeshellarg($mode);

if (PHP_OS_FAMILY === 'Windows') {
    $pythonCmd = '"C:\\Windows\\py.exe" -3.11';
    $cmd = 'chcp 65001 > nul && set PYTHONIOENCODING=utf-8 && '
         . $pythonCmd . ' ' . $escapedScript . ' ' . $escapedMode . ' ' . $escapedInput . ' 2>&1';
} else {
    $cmd = 'python3 ' . $escapedScript . ' ' . $escapedMode . ' ' . $escapedInput . ' 2>&1';
}

$output = shell_exec($cmd);
@unlink($inputFile);

if ($output === null || trim($output) === '') {
    echo json_encode(['status'=>'error','message'=>'Python non accessible (shell_exec désactivé ?)']);
    exit;
}

$clean = trim($output);
if (!mb_check_encoding($clean, 'UTF-8')) {
    $clean = mb_convert_encoding($clean, 'UTF-8', 'Windows-1252');
}

$decoded = json_decode($clean, true);
if (is_array($decoded)) {
    echo json_encode($decoded);
} else {
    echo json_encode(['status'=>'error','message'=>'Sortie Python invalide: '.htmlspecialchars(substr($clean,0,200))]);
}
