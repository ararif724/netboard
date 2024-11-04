<?php

require_once(__DIR__ . '/lib/functions.php');
require_once(__DIR__ . '/lib/routeros-api.class.php');

session_start();
date_default_timezone_set('Asia/Dhaka');

$adminMacs = [];

$minimumTimeGapBetweenNewFullAccessRequest = 3600; // in second
$fullAccessLimit = [
    'dailyQuota' => 60, // in minute
    'shortQuota' => 10 // in minute
];

$controllerFirewallIds = [
    'limitedAccessFirewallId' => '*C', // block limited access list firewall id
    'blockedAccessFirewallId' => '*6', // block all traffic firewall id
    'alwaysAllowFirewallId' => '*10', // always allow access list firewall id
    'alwaysDenyFirewallId' => '*F' // always deny access list firewall id
];

$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPass = '';
$dbName = 'netboard';

$db = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($db->connect_error) {
    die('Failed to connect to MySQL: ' . $db->connect_error);
}
$db->query("SET time_zone='" . date('P') . "';");

$mikroTikIp = '192.168.0.1';
$mikroTikUserName = 'admin';
$mikroTikPassword = '12354';

$API = new RouterosAPI();

if (!$API->connect($mikroTikIp, $mikroTikUserName, $mikroTikPassword)) {
    die('Failed to connect to MikroTik API');
}

if (!defined('CRON')) {
    $currentDevice = getCurrentDeviceInfo();

    if (isset($_GET['action'])) {
        $action = 'action' . ucfirst($_GET['action']);
        if (function_exists($action)) {
            if ($action != 'actionFullAccessRequest' && !isAdmin()) {
                $_SESSION['message'][] = [
                    'type' => 'danger',
                    'content' => 'You do not have permission to perform this action'
                ];
            } else {
                $action();
                sortFirewallRules();
            }
        } else {
            $_SESSION['message'][] = [
                'type' => 'danger',
                'content' => 'Action not found'
            ];
        }

        $location = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';

        header('Location: ' . $location);
        exit;
    }

    if (!isAdmin()) {
        require_once(__DIR__ . '/device-dashboard.php');
        exit;
    }
}
