<?php require_once('header.php'); ?>

<div class="card">
    <div class="card-header">Active Devices</div>
    <div class="card-body">

        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>IP</th>
                    <th>MAC</th>
                    <th>Access</th>
                    <th>Extend</th>
                    <th>Last Active</th>
                    <th>Change Access</th>
                </tr>
            </thead>
            <tbody>

                <?php foreach (getDevices() as $device): ?>
                    <tr>
                        <td><?php echo $device['deviceName'] ?></td>
                        <td><?php echo $device['ip']; ?></td>
                        <td><?php echo $device['mac']; ?></td>
                        <td>
                            <div class="badge bg-<?php echo getBootstrapBadgeTypeFromAccessType($device['accessType']); ?>">
                                <?php echo $device['accessType']; ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <input
                                type="checkbox"
                                onchange="window.location = 'index.php?action=toggleAllowExtendedAccess&deviceId=<?php echo $device['id']; ?>'"
                                <?php echo $device['allowExtendedAccess'] ? 'checked' : ''; ?> />
                        </td>
                        <td><?php echo $device['lastSeen']; ?></td>
                        <td>
                            <?php if ($device['accessType'] != 'full'): ?>
                                <div class="btn-group">
                                    <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Full
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="index.php?action=allowFullAccess&accessLimit=permanent&deviceId=<?php echo $device['id']; ?>">Permanent</a>
                                            <a class="dropdown-item" href="index.php?action=allowFullAccess&accessLimit=1hour&deviceId=<?php echo $device['id']; ?>">1 Hour</a>
                                            <a class="dropdown-item" href="index.php?action=allowFullAccess&accessLimit=2hour&deviceId=<?php echo $device['id']; ?>">2 Hour</a>
                                        </li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <?php if ($device['accessType'] != 'limited'): ?>
                                <div class="btn-group">
                                    <button class="btn btn-warning btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Limited
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="index.php?action=allowLimitedAccess&accessLimit=permanent&deviceId=<?php echo $device['id']; ?>">Permanent</a>
                                            <a class="dropdown-item" href="index.php?action=allowLimitedAccess&accessLimit=1hour&deviceId=<?php echo $device['id']; ?>">1 Hour</a>
                                            <a class="dropdown-item" href="index.php?action=allowLimitedAccess&accessLimit=2hour&deviceId=<?php echo $device['id']; ?>">2 Hour</a>
                                        </li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <?php if ($device['accessType'] != 'blocked'): ?>
                                <div class="btn-group">
                                    <button class="btn btn-danger btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Blocked
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="index.php?action=blockedAccess&accessLimit=permanent&deviceId=<?php echo $device['id']; ?>">Permanent</a>
                                            <a class="dropdown-item" href="index.php?action=blockedAccess&accessLimit=1hour&deviceId=<?php echo $device['id']; ?>">1 Hour</a>
                                            <a class="dropdown-item" href="index.php?action=blockedAccess&accessLimit=2hour&deviceId=<?php echo $device['id']; ?>">2 Hour</a>
                                        </li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

            </tbody>
        </table>

    </div>
</div>

<?php require_once('footer.php'); ?>