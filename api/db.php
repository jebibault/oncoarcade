<?php
/* =====================================================================
   ONCO ARCADE — Connexion + helpers partagés
   ===================================================================== */
declare(strict_types=1);

function oa_config(): array {
  static $cfg = null;
  if ($cfg === null) {
    // On cherche la config du plus sûr (hors du dossier web) au plus simple (dans api/).
    $candidates = [];
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
      $candidates[] = dirname($_SERVER['DOCUMENT_ROOT']) . '/oa-config.php'; // au-dessus de www/ (recommandé)
    }
    $candidates[] = __DIR__ . '/config.php';                                 // repli : dans api/
    $path = null;
    foreach ($candidates as $cand) { if (is_file($cand)) { $path = $cand; break; } }
    if ($path === null) {
      http_response_code(500);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok' => false, 'error' => 'config_missing']);
      exit;
    }
    $cfg = require $path;
    date_default_timezone_set($cfg['timezone'] ?? 'Europe/Paris');
  }
  return $cfg;
}

function oa_db(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $c = oa_config()['db'];
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $c['host'], $c['name'], $c['charset'] ?? 'utf8mb4');
    try {
      $pdo = new PDO($dsn, $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
      ]);
    } catch (Throwable $e) {
      oa_json(['ok' => false, 'error' => 'db_unavailable'], 500);
    }
  }
  return $pdo;
}

function oa_cors(): void {
  $origins = oa_config()['allowed_origins'] ?? [];
  $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
  if ($origin && in_array($origin, $origins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
  }
  if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
  }
}

function oa_json($data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function oa_client_ip(): string {
  return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function oa_ip_hash(string $ip): string {
  $salt = oa_config()['ip_salt'] ?? '';
  return hash('sha256', $ip . '|' . $salt);
}

/* Nettoie un pseudo : lettres/chiffres/espaces/tirets, longueur bornée. */
function oa_clean_name(string $name): string {
  $name = trim($name);
  $name = preg_replace('/[^\p{L}\p{N} _.\-]/u', '', $name) ?? '';
  $name = preg_replace('/\s+/u', ' ', $name) ?? '';
  $name = mb_substr($name, 0, 24);
  return $name !== '' ? $name : 'ANON';
}
