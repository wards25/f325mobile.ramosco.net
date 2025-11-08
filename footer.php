<script>
    if ($(window).width() < 1024) { 
        $(".sidebar").addClass("toggled"); 
        $(".nav-link").addClass("collapsed"); 
        $("#collapseUtilities").removeClass("show"); 
    }
</script>

        <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <?php
                            $year = date("Y");
                        ?>
                     <span>&copy; 2023 - <?php echo $year; ?> RGC BO App | Developed by RGC IT</span>
                    </div>
                </div>
            </footer>
        <!-- End of Footer -->