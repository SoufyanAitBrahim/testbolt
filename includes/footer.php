    </div>

    <footer class="bg-dark text-white mt-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-4">
                    <h5>Sushi Master</h5>
                    <p>Authentic Japanese sushi experience with the freshest ingredients.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="../index.php" class="text-white">Home</a></li>
                        <li><a href="../index.php#menu" class="text-white">Menu</a></li>
                        <li><a href="../index.php#reservation" class="text-white">Reservation</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact</h5>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Sushi Street, Tokyo</p>
                    <p><i class="fas fa-phone"></i> +123 456 7890</p>
                    <p><i class="fas fa-envelope"></i> info@sushimaster.com</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JavaScript (Local) -->
    <script src="assets/lib/bootstrap/js/bootstrap.js"></script>

    <!-- Global Frontend JavaScript -->
    <script src="assets/frontend/js/global.js"></script>

    <!-- Page-specific scripts will be loaded by individual pages -->
    <?php
    // Auto-load page-specific JS based on current page
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    $page_js = "assets/frontend/js/{$current_page}.js";
    if (file_exists($page_js)) {
        echo "<script src=\"{$page_js}\"></script>\n";
    }
    ?></script>
</body>
</html>