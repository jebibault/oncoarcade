<?php
/* =====================================================================
   ONCO ARCADE — POST /submit.php
   Body JSON : { game, name, score, ts, sig?, meta? }
   Réponse   : { ok, rank, best, total }
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
$name  = oa_clean_name((string)($in['name'] ?? ''));
$team  = trim((string)($in['team'] ?? ''));
$team  = preg_replace('/[^\p{L}\p{N} _.\-]/u', '', $team) ?? '';
$team  = preg_replace('/\s+/u', ' ', $team) ?? '';
$team  = mb_substr($team, 0, 24);
$score = (int)($in['score'] ?? -1);
$ts    = (int)($in['ts'] ?? 0);
$sig   = (string)($in['sig'] ?? '');
$meta  = isset($in['meta']) ? mb_substr((string)$in['meta'], 0, 255) : null;
$board = isset($in['board']) ? mb_substr(trim((string)$in['board']), 0, 64) : null;
if ($board === '') $board = null;

// 1. Jeu connu ?
$games = $cfg['games'] ?? [];
if (!isset($games[$game])) oa_json(['ok' => false, 'error' => 'unknown_game'], 400);

// 2. Score plausible ?
$max = (int)$games[$game];
if ($score < 0 || $score > $max) oa_json(['ok' => false, 'error' => 'score_out_of_range'], 422);

// 3. Signature optionnelle (obfuscation, cf. leaderboard.js)
$secret = (string)($cfg['shared_secret'] ?? '');
if ($secret !== '') {
  $expected = hash_hmac('sha256', "$game|$name|$score|$ts", $secret);
  if (!hash_equals($expected, $sig)) oa_json(['ok' => false, 'error' => 'bad_signature'], 403);
  // Rejette un horodatage trop ancien / futur (fenêtre 10 min)
  if (abs(time() * 1000 - $ts) > 600000) oa_json(['ok' => false, 'error' => 'stale_ts'], 403);
}

$pdo     = oa_db();
$ip      = oa_client_ip();
$ip_hash = oa_ip_hash($ip);

// 4. Limitation de débit par IP
$rate = $cfg['rate'] ?? ['max' => 20, 'window_secs' => 60];
$stmt = $pdo->prepare(
  'SELECT COUNT(*) c FROM scores WHERE ip_hash = ? AND created_at > (NOW() - INTERVAL ? SECOND)'
);
$stmt->execute([$ip_hash, (int)$rate['window_secs']]);
if ((int)$stmt->fetchColumn() >= (int)$rate['max']) {
  oa_json(['ok' => false, 'error' => 'rate_limited'], 429);
}

// 5. Insertion
$ins = $pdo->prepare(
  'INSERT INTO scores (game, name, team, score, board, meta, ip_hash, created_at) VALUES (?,?,?,?,?,?,?, NOW())'
);
$ins->execute([$game, $name, ($team !== '' ? $team : null), $score, $board, $meta, $ip_hash]);

// 6. Meilleur score du joueur (sur ce jeu) + rang all-time
$bestStmt = $pdo->prepare('SELECT MAX(score) FROM scores WHERE game = ? AND name = ?');
$bestStmt->execute([$game, $name]);
$best = (int)$bestStmt->fetchColumn();

$rankStmt = $pdo->prepare(
  'SELECT COUNT(*) + 1 FROM (SELECT name, MAX(score) m FROM scores WHERE game = ? GROUP BY name) t WHERE t.m > ?'
);
$rankStmt->execute([$game, $best]);
$rank = (int)$rankStmt->fetchColumn();

$totStmt = $pdo->prepare('SELECT COUNT(DISTINCT name) FROM scores WHERE game = ?');
$totStmt->execute([$game]);
$total = (int)$totStmt->fetchColumn();

oa_json(['ok' => true, 'rank' => $rank, 'best' => $best, 'total' => $total]);
