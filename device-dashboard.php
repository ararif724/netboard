<?php
require_once('config.php');

if (empty($currentDevice)) {
    echo "Device not found";
    die();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>

<body>
    <div class="container p-3">
        <div class="card">
            <div class="card-header">Welcome <?php echo $currentDevice['deviceName']; ?></div>
            <div class="card-body">

                <?php if ($currentDevice['accessType'] == 'blocked'): ?>
                    <div class="alert alert-danger text-center">
                        Your device has no internet access.
                    </div>

                    <div class="alert alert-info text-center">
                        <div class="fw-bold mb-3">Request For Full Access</div>
                        <?php if ($currentDevice['id']): ?>
                            <a href="device-dashboard.php?action=fullAccessRequest" class="btn btn-primary">
                                <?php echo $fullAccessLimit['shortQuota']; ?> Min Short Quota
                            </a>
                        <?php endif; ?>
                        <?php if (!$currentDevice['fullAccessUsedToday']): ?>
                            <a href="device-dashboard.php?action=fullAccessRequest&type=requestDailyQuota" class="btn btn-primary">
                                <?php echo $fullAccessLimit['dailyQuota']; ?> Min Daily Quota
                            </a>
                        <?php endif; ?>
                    </div>
                <?php elseif ($currentDevice['accessType'] == 'limited'): ?>
                    <div class="alert alert-warning text-center">
                        Your device has limited internet access.
                    </div>

                    <div class="alert alert-info text-center">
                        <div class="fw-bold mb-3">Request For Full Access</div>
                        <a href="device-dashboard.php?action=fullAccessRequest" class="btn btn-primary">
                            <?php echo $fullAccessLimit['shortQuota']; ?> Min Short Quota
                        </a>
                        <?php if (!$currentDevice['fullAccessUsedToday']): ?>
                            <a href="device-dashboard.php?action=fullAccessRequest&type=requestDailyQuota" class="btn btn-primary">
                                <?php echo $fullAccessLimit['dailyQuota']; ?> Min Daily Quota
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success text-center">
                        Your device has full internet access.
                    </div>
                <?php endif; ?>

                <?php if ($currentDevice['lastAccessChangeStatus'] == 'pending'): ?>

                    <div class="alert alert-<?php echo getBootstrapBadgeTypeFromAccessType($currentDevice['nextAccessType']); ?>">
                        Your device access will be <strong><?php echo $currentDevice['nextAccessType']; ?></strong> at <?php echo date('Y-m-d h:i A', strtotime($currentDevice['nextAccessAt'])); ?>
                    </div>

                <?php endif; ?>

            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <?php echo $currentDevice['ip']; ?>
                    </div>
                    <div>
                        <?php echo $currentDevice['mac']; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php require_once('footer.php'); ?>