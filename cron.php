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
        updateAccessType($deviceId, $nextAccessType, $nextAccessType == 'blocked' ? 1 : 0);
        $updateStmt = $db->prepare('update `access_queue` set `status` = "completed", `updated_at` = now() where `id` = ?');
        $updateStmt->bind_param('i', $id);
        $updateStmt->execute();
        $updateStmt->close();
    }
    sortFirewallRules();
}

$stmt->close();
