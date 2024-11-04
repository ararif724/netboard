    </div> <!-- container -->
    <div class="toast-container top-0 end-0 p-3">
        <?php
            if( isset($_SESSION['message']) && is_array($_SESSION['message'])):
            foreach($_SESSION['message'] as $message):
        ?>
            <div class="toast show align-items-center text-bg-<?php echo $message['type'] ?> border-0">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo $message['content']; ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    </body>

</html>
<?php $_SESSION['message'] = []; $API->disconnect(); ?>