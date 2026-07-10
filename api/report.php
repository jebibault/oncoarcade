<?php
/* =====================================================================
   ONCO ARCADE — POST /report.php
   Body JSON : { game, message, page }
   Envoie un e-mail au destinataire défini dans la config (report_email),
   jamais exposé au navigateur. Réponse : { ok }
   ===================================================================== */
declare(strict_types=1);
require __DIR__ . '/db.php';

oa_cors();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  oa_json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$cfg = oa_config();
$to  = trim((string)($cfg['report_email'] ?? ''));
if ($to === '') oa_json(['ok' => false, 'error' => 'not_configured'], 500);

$raw = file_get_contents('php://input') ?: '';
$in  = json_decode($raw, true);
if (!is_array($in)) oa_json(['ok' => false, 'error' => 'bad_json'], 400);

$game    = strtolower(trim((string)($in['game'] ?? '')));
$game    = preg_replace('/[^a-z0-9-]/', '', $game) ?? '';
$message = trim((string)($in['message'] ?? ''));
$message = mb_substr($message, 0, 4000);
$page    = mb_substr(trim((string)($in['page'] ?? '')), 0, 300);
if ($message === '') oa_json(['ok' => false, 'error' => 'empty_message'], 422);

$ip      = oa_client_ip();
$ip_hash = oa_ip_hash($ip);

// Anti-spam léger (fichier temporaire) : max 5 signalements / 10 min / IP.
$rl  = sys_get_temp_dir() . '/oa_report_' . substr($ip_hash, 0, 16);
$now = time(); $win = 600; $max = 5;
$hits = [];
if (is_file($rl)) {
  $hits = array_values(array_filter(
    array_map('intval', array_filter(explode(',', (string)@file_get_contents($rl)))),
    static fn($t) => $t > $now - $win
  ));
}
if (count($hits) >= $max) oa_json(['ok' => false, 'error' => 'rate_limited'], 429);
$hits[] = $now;
@file_put_contents($rl, implode(',', $hits));

$games     = $cfg['games'] ?? [];
$gameLabel = $game !== '' ? $game : 'non précisé';

$subject = 'Onco Arcade — signalement (' . $gameLabel . ')';
$body    = "Nouveau signalement Onco Arcade\n"
         . "-----------------------------\n"
         . "Jeu     : " . $gameLabel . "\n"
         . "Date    : " . date('Y-m-d H:i:s') . "\n"
         . "Page    : " . ($page !== '' ? $page : '—') . "\n"
         . "IP hash : " . substr($ip_hash, 0, 12) . "\n"
         . "-----------------------------\n\n"
         . $message . "\n";

$from = trim((string)($cfg['report_from'] ?? ('noreply@' . (string)($_SERVER['HTTP_HOST'] ?? 'localhost'))));
$headers = "From: Onco Arcade <$from>\r\n"
         . "MIME-Version: 1.0\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n"
         . "Content-Transfer-Encoding: 8bit\r\n";

$encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

// OVH mutualisé : préciser l'enveloppe expéditeur (-f) fiabilise fortement mail().
$ok = @mail($to, $encSubject, $body, $headers, '-f' . $from);
if (!$ok) { $ok = @mail($to, $encSubject, $body, $headers); }

oa_json(['ok' => (bool)$ok, 'sent' => (bool)$ok]);
