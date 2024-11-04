<?php
function isAdmin()
{
    global $currentDevice, $adminMacs;
    return ($_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) || in_array($currentDevice['mac'], $adminMacs);
}

function addActiveClass($path)
{
    echo basename($_SERVER['PHP_SELF']) === $path ? 'active' : '';
}

function getActiveDevices()
{
    global $API;
    $API->write('/ip/dhcp-server/lease/print');
    $activeDevices = [];

    foreach ($API->read() as $activeDevice) {
        $activeDevices[$activeDevice['mac-address']] = $activeDevice;
    }

    return $activeDevices;
}

function getSavedDevices($deviceId = null)
{
    global $db;
    $stmt = $db->prepare('SELECT `id`, `mac`, `filter_id`, `device_name`, `access_type`, `allow_extended_access`, `block_full_access_request_until`, `full_access_used_today` FROM `devices`');
    if ($deviceId) {
        $stmt = $db->prepare('SELECT `id`, `mac`, `filter_id`, `device_name`, `access_type`, `allow_extended_access`, `block_full_access_request_until`, `full_access_used_today` FROM `devices` WHERE `id` = ?');
        $stmt->bind_param('i', $deviceId);
    }
    $stmt->execute();
    $stmt->bind_result($id, $mac, $filterId, $deviceName, $accessType, $allowExtendedAccess, $blockFullAccessRequestUntil, $fullAccessUsedToday);
    $result = [];
    while ($stmt->fetch()) {
        $result[$mac] = [
            'id' => $id,
            'mac' => $mac,
            'filterId' => $filterId,
            'deviceName' => $deviceName,
            'accessType' => $accessType,
            'allowExtendedAccess' => $allowExtendedAccess,
            'blockFullAccessRequestUntil' => strtotime($blockFullAccessRequestUntil),
            'fullAccessUsedToday' => $fullAccessUsedToday
        ];
    }

    return $result;
}

function getDevices($deviceId = null)
{
    $activeDevices = getActiveDevices();
    $savedDevices = getSavedDevices($deviceId);
    $mergedDevices = [];

    foreach ($savedDevices as $savedDevice) {

        $savedDevice['ip'] = 'Not connected';
        $savedDevice['lastSeen'] = null;

        if (isset($activeDevices[$savedDevice['mac']])) {
            $activeDevice = $activeDevices[$savedDevice['mac']];
            $savedDevice['ip'] = $activeDevice['address'];
            $savedDevice['lastSeen'] = $activeDevice['last-seen'];
        }
        if ($deviceId != null) {
            $savedDevice = array_merge($savedDevice, getLastAccessChangeInfo($savedDevice['id']));
        }
        $mergedDevices[$savedDevice['mac']] = $savedDevice;
    }

    return $mergedDevices;
}

function getCurrentDeviceInfo()
{
    $activeDevices = getActiveDevices();
    $savedDevices = getSavedDevices();
    $currentDeviceInfo = [];
    foreach ($activeDevices as $activeDevice) {
        if ($_SERVER['REMOTE_ADDR'] === $activeDevice['address']) {
            $currentDeviceInfo = [
                'id' => null,
                'mac' => $activeDevice['mac-address'],
                'filterId' => null,
                'deviceName' => $activeDevice['host-name'] ?? 'Unknown',
                'accessType' => 'blocked',
                'allowExtendedAccess' => false,
                'fullAccessUsedToday' => false,
                'ip' => $activeDevice['address'],
                'lastSeen' => $activeDevice['last-seen'],
            ];
            break;
        }
    }

    if (isset($currentDeviceInfo['mac']) && isset($savedDevices[$currentDeviceInfo['mac']])) {
        $currentDeviceInfo = array_merge($currentDeviceInfo, $savedDevices[$currentDeviceInfo['mac']]);
    }

    return array_merge($currentDeviceInfo, getLastAccessChangeInfo($currentDeviceInfo['id']));
}

function getLastAccessChangeInfo($deviceId)
{
    global $db;
    $stmt = $db->prepare('SELECT `next_access_type`, `next_access_at`, `status`, `created_at` FROM `access_queue` WHERE `device_id` = ? ORDER BY `id` DESC LIMIT 1');
    $stmt->bind_param('i', $deviceId);
    $stmt->execute();
    $stmt->bind_result($nextAccessType, $nextAccessAt, $status, $createdAt);
    $stmt->fetch();
    $stmt->close();
    return [
        'nextAccessType' => $nextAccessType,
        'nextAccessAt' => $nextAccessAt,
        'lastAccessChangeStatus' => $status,
        'lastAccessChangeAt' => strtotime($createdAt)
    ];
}

function getBootstrapBadgeTypeFromAccessType($accessType)
{
    switch ($accessType) {
        case 'full':
            return 'success';
        case 'limited':
            return 'warning';
        case 'blocked':
            return 'danger';
    }
}

function sortFirewallRules()
{
    global $API, $db, $controllerFirewallIds;
    $stmt = $db->prepare('SELECT `filter_id`, `access_type` FROM `devices`');
    $stmt->execute();
    $stmt->bind_result($filterId, $accessType);
    $device = [
        'blocked' => [],
        'limited' => [],
        'full' => []
    ];
    while ($stmt->fetch()) {
        $device[$accessType][] = $filterId;
    }
    $stmt->close();

    /**
     * always deny rule
     * always allow rule
     * full access devices
     * limited rule
     * limited access devices
     * block all rule
     * blocked access devices
     */

    $destination = null;

    //blocked access devices
    foreach ($device['blocked'] as $filterId) {
        $API->comm("/ip/firewall/filter/move", array(
            ".id" => $filterId,
            "destination" => $destination
        ));
        $destination = $filterId;
    }

    //block all rule
    $API->comm("/ip/firewall/filter/move", array(
        ".id" => $controllerFirewallIds['blockedAccessFirewallId'],
        "destination" => $destination
    ));
    $destination = $controllerFirewallIds['blockedAccessFirewallId'];

    //limited access devices
    foreach ($device['limited'] as $filterId) {
        $API->comm("/ip/firewall/filter/move", array(
            ".id" => $filterId,
            "destination" => $destination
        ));
        $destination = $filterId;
    }

    //limited rule
    $API->comm("/ip/firewall/filter/move", array(
        ".id" => $controllerFirewallIds['limitedAccessFirewallId'],
        "destination" => $destination
    ));
    $destination = $controllerFirewallIds['limitedAccessFirewallId'];

    //full access devices
    foreach ($device['full'] as $filterId) {
        $API->comm("/ip/firewall/filter/move", array(
            ".id" => $filterId,
            "destination" => $destination
        ));
        $destination = $filterId;
    }

    //always allow rule
    if ($controllerFirewallIds['alwaysAllowFirewallId'] !== '') {
        $API->comm("/ip/firewall/filter/move", array(
            ".id" => $controllerFirewallIds['alwaysAllowFirewallId'],
            "destination" => $destination
        ));
        $destination = $controllerFirewallIds['alwaysAllowFirewallId'];
    }

    //always deny rule
    if ($controllerFirewallIds['alwaysDenyFirewallId'] !== '') {
        $API->comm("/ip/firewall/filter/move", array(
            ".id" => $controllerFirewallIds['alwaysDenyFirewallId'],
            "destination" => $destination
        ));
        $destination = $controllerFirewallIds['alwaysDenyFirewallId'];
    }
}

function updateAccessType($deviceId, $accessType, $requestDailyQuota, $blockFullAccessRequestUntil = null)
{
    global $db;

    if ($blockFullAccessRequestUntil) {
        $stmt = $db->prepare('UPDATE `devices` SET `access_type` = ?, `block_full_access_request_until` = ?, `full_access_used_today` = ?, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = ?');
        $stmt->bind_param('ssii', $accessType, $blockFullAccessRequestUntil, $requestDailyQuota, $deviceId);
    } else {
        $stmt = $db->prepare('UPDATE `devices` SET `access_type` = ?, `full_access_used_today` = ?, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = ?');
        $stmt->bind_param('sii', $accessType, $requestDailyQuota, $deviceId);
    }

    $stmt->execute();
    $stmt->close();
}

/**
 * Actions
 */

function actionFullAccessRequest()
{
    global $API, $db, $currentDevice, $fullAccessLimit, $minimumTimeGapBetweenNewFullAccessRequest;

    if ($currentDevice['accessType'] == 'full') {
        $_SESSION['message'][] = [
            'type' => 'danger',
            'content' => 'You already have full access'
        ];
        return true;
    }

    $requestDailyQuota = isset($_GET['type']) && $_GET['type'] == 'requestDailyQuota';
    $fullAccessGrantedFor = $fullAccessLimit['shortQuota'];

    if ($requestDailyQuota) {
        if ($currentDevice['fullAccessUsedToday']) {
            $_SESSION['message'][] = [
                'type' => 'danger',
                'content' => "You have already used your daily {$fullAccessLimit['dailyQuota']} minutes full access quota"
            ];
            return true;
        }

        $fullAccessGrantedFor = $fullAccessLimit['dailyQuota'];
        $currentDevice['fullAccessUsedToday'] = true;
    }

    $currentTime = time();
    $blockedUntil = $currentDevice['blockFullAccessRequestUntil'];

    if ($currentDevice['blockFullAccessRequestUntil'] > $currentTime) {
        $nextAccessAllowedIn = $currentDevice['blockFullAccessRequestUntil'] - $currentTime;
    } else {
        $nextAccessAllowedIn = $minimumTimeGapBetweenNewFullAccessRequest - ($currentTime - $currentDevice['lastAccessChangeAt']);
        $blockedUntil = $currentTime + $nextAccessAllowedIn;
    }

    if ($nextAccessAllowedIn > 0) {
        $_SESSION['message'][] = [
            'type' => 'danger',
            'content' => 'You have been blocked from full access until ' . date('M d, Y, h:i A', $blockedUntil)
        ];
        return true;
    }

    if ($currentDevice['id'] == null) {
        $filterId = $API->comm("/ip/firewall/filter/add", array(
            "chain" => "forward",
            "src-mac-address" => $currentDevice['mac'],
            "action" => "accept",
            "comment" => $currentDevice['deviceName']
        ));
        $stmt = $db->prepare("INSERT INTO `devices` (`mac`, `filter_id`, `device_name`, `access_type`, `full_access_used_today`) VALUES (?, ?, ?, 'full', ?);");
        $stmt->bind_param('sssi', $currentDevice['mac'], $filterId, $currentDevice['deviceName'], $currentDevice['fullAccessUsedToday']);
        $stmt->execute();
        $stmt->close();

        $currentDevice['id'] = $db->insert_id;
    } else {
        updateAccessType($currentDevice['id'], 'full', $currentDevice['fullAccessUsedToday']);
    }

    $stmt = $db->prepare("INSERT INTO `access_queue` (`device_id`, `next_access_type`, `next_access_at`) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $currentDevice['id'], $currentDevice['accessType'], $nextAccessAt);

    $nextAccessAt = date('Y-m-d H:i:s', strtotime("+$fullAccessGrantedFor minutes"));

    $stmt->execute();
    $stmt->close();

    $currentDevice['accessType'] = 'full';
    $currentDevice['lastAccessChangeAt'] = date('Y-m-d H:i:s');

    $_SESSION['message'][] = [
        'type' => 'success',
        'content' => "Full access granted for $fullAccessGrantedFor minutes"
    ];

    return true;
}

function actionAllowFullAccess()
{
    global $db;

    if (isset($_GET['accessLimit']) && isset($_GET['deviceId'])) {
        $accessLimit = $_GET['accessLimit'];
        $deviceId = $_GET['deviceId'];

        $deviceInfo = getDevices($deviceId);
        $deviceInfo = reset($deviceInfo);

        if ($deviceInfo && $deviceInfo['accessType'] != 'full') {

            $blockFullAccessRequestUntil = null;

            if ($accessLimit == 'permanent') {
                $blockFullAccessRequestUntil = date('Y-m-d H:i:s');
            }

            updateAccessType($deviceInfo['id'], 'full', $deviceInfo['fullAccessUsedToday'], $blockFullAccessRequestUntil);

            $stmt = $db->prepare("UPDATE `access_queue` SET `status` = 'cancelled', `comment` = 'cancelled by system', `updated_at` = now() WHERE `device_id` = ? AND `status` = 'pending'");
            $stmt->bind_param('i', $deviceInfo['id']);
            $stmt->execute();
            $stmt->close();

            if ($_GET['accessLimit'] != 'permanent') {
                $stmt = $db->prepare("INSERT INTO `access_queue` (`device_id`, `next_access_type`, `next_access_at`) VALUES (?, ?, ?)");
                $stmt->bind_param('iss', $deviceInfo['id'], $accessType, $nextAccessAt);

                $accessType = $deviceInfo['lastAccessChangeStatus'] == 'pending' ? $deviceInfo['nextAccessType'] : $deviceInfo['accessType'];
                $nextAccessAt = date('Y-m-d H:i:s', strtotime("+$accessLimit"));

                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

function actionAllowLimitedAccess()
{
    global $db;

    if (isset($_GET['accessLimit']) && isset($_GET['deviceId'])) {
        $accessLimit = $_GET['accessLimit'];
        $deviceId = $_GET['deviceId'];

        $deviceInfo = getDevices($deviceId);
        $deviceInfo = reset($deviceInfo);

        if ($deviceInfo && $deviceInfo['accessType'] != 'limited') {

            $blockFullAccessRequestUntil = null;

            if ($accessLimit == 'permanent') {
                $blockFullAccessRequestUntil = date('Y-m-d H:i:s');
            }

            updateAccessType($deviceInfo['id'], 'limited', $deviceInfo['fullAccessUsedToday'], $blockFullAccessRequestUntil);

            $stmt = $db->prepare("UPDATE `access_queue` SET `status` = 'cancelled', `comment` = 'cancelled by system', `updated_at` = now() WHERE `device_id` = ? AND `status` = 'pending'");
            $stmt->bind_param('i', $deviceInfo['id']);
            $stmt->execute();
            $stmt->close();

            if ($_GET['accessLimit'] != 'permanent') {
                $stmt = $db->prepare("INSERT INTO `access_queue` (`device_id`, `next_access_type`, `next_access_at`) VALUES (?, ?, ?)");
                $stmt->bind_param('iss', $deviceInfo['id'], $accessType, $nextAccessAt);

                $accessType = $deviceInfo['lastAccessChangeStatus'] == 'pending' ? $deviceInfo['nextAccessType'] : $deviceInfo['accessType'];
                $nextAccessAt = date('Y-m-d H:i:s', strtotime("+$accessLimit"));

                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

function actionBlockedAccess()
{
    global $db;

    if (isset($_GET['accessLimit']) && isset($_GET['deviceId'])) {
        $accessLimit = $_GET['accessLimit'];
        $deviceId = $_GET['deviceId'];

        $deviceInfo = getDevices($deviceId);
        $deviceInfo = reset($deviceInfo);

        if ($deviceInfo && $deviceInfo['accessType'] != 'blocked') {

            if ($accessLimit == 'permanent') {
                $blockFullAccessRequestUntil = date('Y-m-d H:i:s', strtotime("+100 years"));
            } else {
                $blockFullAccessRequestUntil = date('Y-m-d H:i:s', strtotime("+$accessLimit"));
            }

            updateAccessType($deviceInfo['id'], 'blocked', $deviceInfo['fullAccessUsedToday'],  $blockFullAccessRequestUntil);

            $stmt = $db->prepare("UPDATE `access_queue` SET `status` = 'cancelled', `comment` = 'cancelled by system', `updated_at` = now() WHERE `device_id` = ? AND `status` = 'pending'");
            $stmt->bind_param('i', $deviceInfo['id']);
            $stmt->execute();
            $stmt->close();

            if ($_GET['accessLimit'] != 'permanent' && $deviceInfo['lastAccessChangeStatus'] != 'pending' && $deviceInfo['nextAccessType'] != 'blocked') {
                $stmt = $db->prepare("INSERT INTO `access_queue` (`device_id`, `next_access_type`, `next_access_at`) VALUES (?, ?, ?)");
                $stmt->bind_param('iss', $deviceInfo['id'], $accessType, $nextAccessAt);

                $accessType = $deviceInfo['lastAccessChangeStatus'] == 'pending' ? $deviceInfo['nextAccessType'] : $deviceInfo['accessType'];
                $nextAccessAt = date('Y-m-d H:i:s', strtotime("+$accessLimit"));

                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

function actionToggleAllowExtendedAccess()
{
    global $db;

    if (isset($_GET['deviceId'])) {
        $deviceId = $_GET['deviceId'];

        $stmt = $db->prepare("UPDATE `devices` SET `allow_extended_access` = !`allow_extended_access` WHERE `id` = ?");
        $stmt->bind_param('i', $deviceId);
        $stmt->execute();
        $stmt->close();
    }
}
