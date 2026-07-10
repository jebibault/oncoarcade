<?php
/* =====================================================================
   ONCO ARCADE — GET /ratings.php   (optionnel: ?game=slug)
   Réponse : { ok, ratings: { slug: { avg, count }, ... } }
   ===================================================================== */
declare(strict_types=1);
require __DIR__ . '/db.php';

oa_cors();

$game = strtolower(trim((string)($_GET['game'] ?? '')));
$pdo  = oa_db();

if ($game !== '') {
  $stmt = $pdo->prepare('SELECT AVG(stars) a, COUNT(*) c FROM ratings WHERE game = ?');
  $stmt->execute([$game]);
  $r = $stmt->fetch() ?: ['a' => 0, 'c' => 0];
  oa_json(['ok' => true, 'ratings' => [$game => ['avg' => round((float)$r['a'], 2), 'count' => (int)$r['c']]]]);
}

$stmt = $pdo->query('SELECT game, AVG(stars) a, COUNT(*) c FROM ratings GROUP BY game');
$out  = [];
foreach ($stmt as $row) {
  $out[$row['game']] = ['avg' => round((float)$row['a'], 2), 'count' => (int)$row['c']];
}
oa_json(['ok' => true, 'ratings' => $out]);
