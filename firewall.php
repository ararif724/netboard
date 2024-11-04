<?php require_once('header.php'); ?>

<div class="card">
    <div class="card-header">Device List</div>
    <div class="card-body">

        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Chain</th>
                    <th>Action</th>
                    <th>Src Mac Address</th>
                    <th>Src Address</th>
                    <th>Dst Address List</th>
                    <th>comment</th>
                </tr>
            </thead>
            <tbody>

                <?php
                $API->write('/ip/firewall/filter/print');
                $firewallRules = $API->read();
                foreach ($firewallRules as $firewallRule):
                ?>
                    <tr>
                        <td><?php echo $firewallRule['.id']; ?></td>
                        <td><?php echo $firewallRule['chain'] ?></td>
                        <td><?php echo $firewallRule['action'] ?></td>
                        <td><?php echo $firewallRule['src-mac-address'] ?? ""; ?></td>
                        <td><?php echo $firewallRule['src-address'] ?? ""; ?></td>
                        <td><?php echo $firewallRule['dst-address-list'] ?? ""; ?></td>
                        <td><?php echo $firewallRule['comment'] ?? ""; ?></td>
                    </tr>

                <?php endforeach; ?>

            </tbody>
        </table>

    </div>
</div>

<?php require_once('footer.php'); ?>