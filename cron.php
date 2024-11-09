<?php

# */5 * * * * /usr/bin/php /htdocs/netboard/cron.php >/dev/null 2>&1

define('CRON', true);

require_once('config.php');

$stmt = $db->prepare('select `id`, `device_id`, `next_access_type` from `access_queue` where `status` = "pending" and `next_access_at` < now() order by `next_access_at`');
$stmt->execute();
$stmt->bind_result($id, $deviceId, $nextAccessType);
$stmt->store_result();

if ($stmt->num_rows > 0) {
    while ($stmt->fetch()) {
        $updateStmt = $db->prepare('update `devices` set `access_type` = ? where `id` = ?');
        $updateStmt->bind_param('si', $nextAccessType, $deviceId);
        $updateStmt->execute();
        $updateStmt->close();

        $updateStmt = $db->prepare('update `access_queue` set `status` = "completed", `updated_at` = now() where `id` = ?');
        $updateStmt->bind_param('i', $id);
        $updateStmt->execute();
        $updateStmt->close();
    }
    sortFirewallRules();
}

$stmt->close();

if (date('H') == 8 && date('i') == 0) {
    $stmt = $db->prepare('update `devices` set `full_access_used_today` = 0');
    $stmt->execute();
    $stmt->close();
}
