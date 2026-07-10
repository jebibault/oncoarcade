<?php
/* =====================================================================
   ONCO ARCADE — POST /rate.php
   Body JSON : { game, stars (1..5), voter }
   Un vote par appareil (voter) et par jeu ; re-voter met à jour la note.
   Réponse   : { ok, game, avg, count, yours }
   ===================================================================== */
declare(strict_types=1);
require __DIR__ . '/db.php';

oa_cors();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  oa_json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$cfg = oa_config();
$raw = file_get_contents('php://input') ?: '';
$in  = json_decode($raw, true);
if (!is_array($in)) oa_json(['ok' => false, 'error' => 'bad_json'], 400);

$game  = strtolower(trim((string)($in['game'] ?? '')));
$stars = (int)($in['stars'] ?? 0);
$voter = substr(preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($in['voter'] ?? '')) ?? '', 0, 64);

$games = $cfg['games'] ?? [];
if (!isset($games[$game]))       oa_json(['ok' => false, 'error' => 'unknown_game'], 400);
if ($stars < 1 || $stars > 5)    oa_json(['ok' => false, 'error' => 'stars_out_of_range'], 422);
if ($voter === '')               oa_json(['ok' => false, 'error' => 'no_voter'], 400);

$pdo     = oa_db();
$ip      = oa_client_ip();
$ip_hash = oa_ip_hash($ip);

// Limitation de débit par IP (réutilise la config du classement)
$rate = $cfg['rate'] ?? ['max' => 20, 'window_secs' => 60];
$stmt = $pdo->prepare('SELECT COUNT(*) FROM ratings WHERE ip_hash = ? AND updated_at > (NOW() - INTERVAL ? SECOND)');
$stmt->execute([$ip_hash, (int)$rate['window_secs']]);
if ((int)$stmt->fetchColumn() >= (int)$rate['max']) {
  oa_json(['ok' => false, 'error' => 'rate_limited'], 429);
}

$voter_hash = hash('sha256', $voter . '|' . ($cfg['ip_salt'] ?? ''));
$now = date('Y-m-d H:i:s');

$stmt = $pdo->prepare(
  'INSERT INTO ratings (game, stars, voter_hash, ip_hash, created_at, updated_at)
   VALUES (?, ?, ?, ?, ?, ?)
   ON DUPLICATE KEY UPDATE stars = VALUES(stars), ip_hash = VALUES(ip_hash), updated_at = VALUES(updated_at)'
);
$stmt->execute([$game, $stars, $voter_hash, $ip_hash, $now, $now]);

$agg = $pdo->prepare('SELECT AVG(stars) a, COUNT(*) c FROM ratings WHERE game = ?');
$agg->execute([$game]);
$r = $agg->fetch() ?: ['a' => $stars, 'c' => 1];

oa_json(['ok' => true, 'game' => $game, 'avg' => round((float)$r['a'], 2), 'count' => (int)$r['c'], 'yours' => $stars]);
