<?php
/* ONCO ARCADE — GET /teams.php  ->  { ok, teams:[ "Alpha", "Béta", ... ] } */
declare(strict_types=1);
require __DIR__ . '/db.php';
oa_cors();
$pdo  = oa_db();
$stmt = $pdo->query("SELECT team, COUNT(*) c FROM scores WHERE team IS NOT NULL AND team <> '' GROUP BY team ORDER BY c DESC, team ASC LIMIT 200");
$teams = [];
foreach ($stmt as $row) { $teams[] = (string)$row['team']; }
oa_json(['ok' => true, 'teams' => $teams]);
