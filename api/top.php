<?php
/* =====================================================================
   ONCO ARCADE — GET /top.php?game=&period=&limit=
   game   : slug d'un jeu, ou "global" (somme des meilleurs scores par jeu,
            hors jeux listés dans config > exclude_global)
   period : week (défaut) | today | all
   limit  : 1..50 (défaut 10)
   Réponse: { ok, scope, period, rows:[{rank,name,score,games,meta}] }
   ===================================================================== */
declare(strict_types=1);
require __DIR__ . '/db.php';

oa_cors();

$cfg    = oa_config();
$game   = strtolower(trim((string)($_GET['game'] ?? 'global')));
$period = strtolower(trim((string)($_GET['period'] ?? 'week')));
$limit  = max(1, min(50, (int)($_GET['limit'] ?? 10)));

// Filtre de période -> borne created_at (fragments SQL contrôlés, sans paramètre)
switch ($period) {
  case 'today': $since = "CURDATE()"; break;
  case 'all':   $since = null; break;
  case 'week':
  default:
    $period = 'week';
    $since = "(CURDATE() - INTERVAL WEEKDAY(NOW()) DAY)"; // début de semaine (lundi)
    break;
}
$where = $since ? "WHERE created_at >= $since" : "";

$pdo = oa_db();

if ($game === 'teams') {
  // Classement par équipe : points cumulés, nb de joueurs, nb de parties (hors exclude_global).
  $excl = $cfg['exclude_global'] ?? [];
  $exclSql = ''; $params = [];
  if ($excl) {
    $ph = implode(',', array_fill(0, count($excl), '?'));
    $exclSql = ' AND game NOT IN (' . $ph . ')';
    $params = array_values($excl);
  }
  $cond = ($where ? $where . ' AND ' : 'WHERE ') . "team IS NOT NULL AND team <> ''" . $exclSql;
  $sql = "SELECT team, SUM(score) AS score, COUNT(DISTINCT name) AS players, COUNT(*) AS plays
          FROM scores $cond GROUP BY team ORDER BY score DESC, plays DESC LIMIT $limit";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();
} elseif ($game === 'global') {
  // Meilleur score par (jeu, joueur), puis somme par joueur — hors exclude_global.
  $excl    = $cfg['exclude_global'] ?? [];
  $exclSql = '';
  $params  = [];
  if ($excl) {
    $ph      = implode(',', array_fill(0, count($excl), '?'));
    $exclSql = ($where ? ' AND ' : ' WHERE ') . "game NOT IN ($ph)";
    $params  = array_values($excl);
  }
  $sql = "
    SELECT name, team, game, score, created_at
    FROM scores
    $where$exclSql
    ORDER BY created_at DESC
    LIMIT $limit";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();
} else {
  if (!isset(($cfg['games'] ?? [])[$game])) oa_json(['ok' => false, 'error' => 'unknown_game'], 400);

  // Sous-classement optionnel (ex: cas clinique d'Arc Racer).
  $board = isset($_GET['board']) ? mb_substr(trim((string)$_GET['board']), 0, 64) : '';
  $innerBoard = $board !== '' ? ' AND board = ?' : '';
  $outerBoard = $board !== '' ? ' AND s.board = ?' : '';

  // Meilleur score par joueur + méta de la meilleure ligne (pour la précision).
  $innerCond = ($where ? "$where AND game = ?" : "WHERE game = ?") . $innerBoard;
  $outerCond = "WHERE s.game = ?" . ($since ? " AND s.created_at >= $since" : "") . $outerBoard;
  $sql = "
    SELECT s.name, s.team, s.score, s.meta, 1 AS games
    FROM scores s
    JOIN (
      SELECT name, MAX(score) AS m
      FROM scores
      $innerCond
      GROUP BY name
    ) b ON b.name = s.name AND b.m = s.score
    $outerCond
    GROUP BY s.name
    ORDER BY s.score DESC, s.name ASC
    LIMIT $limit";
  $stmt = $pdo->prepare($sql);
  $args = [$game];
  if ($board !== '') $args[] = $board;   // inner
  $args[] = $game;
  if ($board !== '') $args[] = $board;   // outer
  $stmt->execute($args);
  $rows = $stmt->fetchAll();
}

$out  = [];
$rank = 1;
foreach ($rows as $r) {
  $entry = ['rank' => $rank++];
  if (array_key_exists('name', $r))       $entry['name']    = (string)$r['name'];
  if (array_key_exists('team', $r))       $entry['team']    = ($r['team'] !== null) ? (string)$r['team'] : '';
  $entry['score'] = (int)$r['score'];
  if (array_key_exists('games', $r))      $entry['games']   = (int)$r['games'];
  if (array_key_exists('players', $r))    $entry['players'] = (int)$r['players'];
  if (array_key_exists('plays', $r))      $entry['plays']   = (int)$r['plays'];
  if (array_key_exists('meta', $r))       $entry['meta']    = $r['meta'];
  if (array_key_exists('game', $r))       $entry['game']    = (string)$r['game'];
  if (array_key_exists('created_at', $r)) $entry['ts']      = (string)$r['created_at'];
  $out[] = $entry;
}

oa_json(['ok' => true, 'scope' => $game, 'period' => $period, 'rows' => $out]);
