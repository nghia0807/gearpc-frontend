<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Footer</title>
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons (optional) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* Scoped styles for the footer */
        .tech-footer a {
            color: #adb5bd;
            text-decoration: none;
        }

        .tech-footer a:hover {
            color: #fff;
            text-decoration: underline;
        }

        @media (max-width: 991.98px) {
            .tech-footer .row {
                flex-direction: row !important;
            }
        }

        .tech-footer .row {
            flex-direction: row !important;
        }

        .tech-footer .col-md-4 {
            margin-bottom: 0 !important;
        }
    </style>
</head>

<body>

    <!-- Footer Component -->
    <footer class="tech-footer bg-black text-light py-4 mt-5">
        <div class="container">
            <div class="row" style="flex-direction: row !important;">
                <!-- About Us -->
                <div class="col-md-4 mb-3">
                    <h5 class="text-uppercase">About Us</h5>
                    <p class="mb-2">
                        We provide top-notch PCs, laptops, and gaming gear to tech enthusiasts.
                    </p>
                    <a href="../about.php">Learn More</a>
                </div>

                <!-- Customer Support -->
                <div class="col-md-4 mb-3">
                    <h5 class="text-uppercase">Customer Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="../contact.php">Contact Us</a></li>
                        <li><a href="../faqs.php">FAQs</a></li>
                        <li><a href="../return-policy.php">Return Policy</a></li>
                        <li><a href="../shipping-info.php">Shipping Info</a></li>
                    </ul>
                </div>

                <!-- Social Media -->
                <div class="col-md-4 mb-3">
                    <h5 class="text-uppercase">Follow Us</h5>
                    <a href="https://facebook.com" target="_blank" class="me-3"><i class="bi bi-facebook fs-4"></i></a>
                    <a href="https://twitter.com" target="_blank" class="me-3"><i class="bi bi-twitter fs-4"></i></a>
                    <a href="https://instagram.com" target="_blank"><i class="bi bi-instagram fs-4"></i></a>
                </div>
            </div>

            <!-- Copyright -->
            <div class="text-center pt-3">
                &copy; <?php echo date("Y"); ?> TechTrend. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>