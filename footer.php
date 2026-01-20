<?php
// footer.php
?>
<footer class="site-footer py-4 mt-5" style="background: linear-gradient(90deg,#e9f5ff 70%,#fafdff 100%); border-top: 2px solid #dbe6ff;">
    <div class="container">
        <div class="row g-4 align-items-center justify-content-between">
            <!-- Logo & School Info -->
            <div class="col-lg-4 col-md-6 col-12 text-lg-start text-center mb-3 mb-lg-0">
                <div class="d-flex align-items-center justify-content-lg-start justify-content-center gap-3 mb-2">
                    <img src="images/logo.png" alt="School Logo" style="width:56px; height:56px; border-radius:14px; box-shadow:0 2px 12px #b5cbe74d;">
                    <div>
                        <div class="school-title fw-bold" style="color:#1849a6; font-size:1.18em;">DAR-UL-HUDA PUBLIC SCHOOL</div>
                        <div class="school-address" style="color:#277a5a; font-size:0.97em;">
                            Opposite Shahnawaz Marriage Hall<br>
                            Sanghoi, Jhelum, Punjab
                        </div>
                    </div>
                </div>
            </div>

            <!-- Time Zone (centered) -->
            <div class="col-lg-3 col-md-6 col-12 text-lg-center text-center mb-3 mb-lg-0">
                <div class="timezone-title fw-semibold" style="color:#2378e3;">Time Zone:</div>
                <div class="timezone-value" style="font-family:monospace; color:#222;">
                    <?php
                        date_default_timezone_set('Asia/Karachi');
                        echo date('D M d Y H:i:s e (T)');
                    ?>
                </div>
            </div>

            <!-- Social & Contact -->
            <div class="col-lg-5 col-12 text-lg-end text-center">
                <div class="d-lg-flex justify-content-end gap-5 align-items-center">
                    <!-- Social -->
                    <div>
                        <div class="footer-social-title" style="text-transform:uppercase; letter-spacing:1px; font-size:0.97em; color:#3670a0; margin-bottom:4px;">Follow Us</div>
                        <div class="footer-icons d-flex gap-2 justify-content-lg-end justify-content-center">
                            <a href="#" target="_blank" title="Facebook" aria-label="Facebook">
                                <i class="bi bi-facebook" style="color:#1877F3; font-size:1.6em;"></i>
                            </a>
                            <a href="#" target="_blank" title="X (Twitter)" aria-label="Twitter">
                                <i class="bi bi-twitter-x" style="color:#000; font-size:1.6em;"></i>
                            </a>
                            <a href="#" target="_blank" title="Instagram" aria-label="Instagram">
                                <i class="bi bi-instagram" style="color:#e54595; font-size:1.6em;"></i>
                            </a>
                            <a href="#" target="_blank" title="LinkedIn" aria-label="LinkedIn">
                                <i class="bi bi-linkedin" style="color:#0A66C2; font-size:1.6em;"></i>
                            </a>
                        </div>
                    </div>
                    <!-- Contact -->
                    <div class="footer-contact text-lg-end text-center mt-3 mt-lg-0">
                        <div class="footer-contact-title fw-semibold" style="color:#22744c;">Contact Us</div>
                        <div class="footer-contact-info" style="color:#2e395c; font-size:0.97em;">
                            <div><strong>Phone:</strong> <a href="tel:+923435707607" class="text-decoration-none text-dark">+92 343 5707607</a></div>
                            <div><strong>Email:</strong> <a href="mailto:sarwarshaffi72@gmail.com" class="text-decoration-none text-dark">sarwarshaffi72@gmail.com</a></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer bottom bar -->
        <div class="text-center pt-3 mt-4 border-0" style="color:#1849a6; font-size:0.98em;">
            &copy; <?= date('Y'); ?> Powered by <b>Zain Ishaq</b>. All rights reserved.
        </div>
    </div>
</footer>
<!-- Bootstrap Icons CDN (if not already included) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
