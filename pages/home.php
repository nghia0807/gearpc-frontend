<?php
require_once __DIR__ . '/../includes/session_init.php';
$rss_url = "https://www.androidcentral.com/rss.xml";
$rss = simplexml_load_file($rss_url);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Zone - Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #eef2f5;
            margin: 0;
        }

        .news-slider-container {
            position: relative;
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .news-slider-container h2 {
            font-size: 28px;
            color: #0077cc;
            margin-bottom: 25px;
            border-bottom: 2px solid #0077cc;
            padding-bottom: 10px;
        }

        .news-slider {
            display: flex;
            overflow-x: hidden;
            scroll-behavior: smooth;
            gap: 20px;
            padding-bottom: 10px;
            -webkit-overflow-scrolling: touch;
            /* Smooth scrolling on iOS */
        }

        .news-slide {
            flex: 0 0 calc(25% - 20px);
            width: 300px;
            background: #f5f8fb;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 10px;
            display: flex;
            flex-direction: column;
        }

        @media (max-width: 992px) {
            .news-slide {
                flex: 0 0 calc(33.33% - 20px);
            }
        }

        @media (max-width: 768px) {
            .news-slide {
                flex: 0 0 calc(50% - 20px);
                width: calc(50% - 20px);
            }

            .news-slider-container h2 {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .news-slide {
                flex: 0 0 calc(100% - 20px);
                width: calc(100% - 20px);
            }

            .news-slider-container {
                padding: 15px;
            }

            .news-slider-container h2 {
                font-size: 22px;
                margin-bottom: 20px;
            }
        }

        .news-slide img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 8px;
        }

        .news-slide-content {
            margin-top: 10px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .news-slide-content a {
            font-size: 14px;
            font-weight: bold;
            color: #0077cc;
            text-decoration: none;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.3;
            height: 2.6em;
        }

        .news-slide-content a:hover {
            color: #e91e63;
        }

        .news-slide-content small {
            font-size: 12px;
            color: #888;
            margin-top: 6px;
            display: block;
        }

        @media (max-width: 768px) {
            .news-slide img {
                height: 140px;
            }
        }

        @media (max-width: 480px) {
            .news-slide {
                padding: 8px;
            }

            .news-slide img {
                height: 180px;
            }

            .news-slide-content a {
                font-size: 15px;
            }
        }

        .slide-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.8);
            border: none;
            color: rgb(14, 14, 14);
            font-size: 24px;
            padding: 12px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 10;
            transition: transform 0.3s, background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .slide-btn:hover {
            transform: translateY(-50%) scale(1.1);
            background: rgba(255, 255, 255, 0.9);
        }

        .slide-btn.left {
            left: 5px;
        }

        .slide-btn.right {
            right: 5px;
        }

        @media (max-width: 768px) {
            .slide-btn {
                width: 36px;
                height: 36px;
                font-size: 18px;
                padding: 8px;
            }
        }

        @media (max-width: 480px) {
            .slide-btn {
                width: 32px;
                height: 32px;
                font-size: 16px;
                padding: 6px;
            }
        }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            background: #333;
            color: #fff;
            padding: 12px 24px;
            border-radius: 4px;
            min-width: 200px;
            max-width: 300px;
            opacity: 0;
            transform: translateY(100px);
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast.success {
            background: #4CAF50;
        }

        .toast.error {
            background: #F44336;
        }

        .toast.info {
            background: #2196F3;
        }

        /* Banner Grid Layout */
        .banner-grid {
            max-width: 1200px;
            margin: 30px auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 0 20px;
        }

        .banner-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s ease;
            /* Thêm aspect-ratio để cố định tỷ lệ */
            aspect-ratio: 5/4;
        }

        .banner-item:hover {
            transform: translateY(-5px);
        }

        .banner-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* Đảm bảo ảnh không bị méo */
            object-position: center;
        }

        .banner-item .banner-content {
            position: absolute;
            bottom: 20px;
            left: 20px;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            z-index: 1;
        }

        /* Main banner spans 2 columns and 2 rows */
        .banner-item.main {
            grid-column: span 2;
            grid-row: span 2;
            /* Loại bỏ height cố định, sử dụng aspect-ratio */
        }

        /* Sub banners - không cần height cố định nữa */
        .banner-item.sub {
            /* height đã được controlled bởi aspect-ratio */
        }

        @media (max-width: 992px) {
            .banner-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .banner-item.main {
                /* Loại bỏ height cố định */
            }

            .banner-item.sub {
                /* Loại bỏ height cố định */
            }
        }

        @media (max-width: 576px) {
            .banner-grid {
                grid-template-columns: 1fr;
                padding: 0 15px;
            }

            .banner-item.main {
                grid-column: 1;
                grid-row: 1;
                /* Loại bỏ height cố định */
            }

            .banner-item.sub {
                /* Loại bỏ height cố định */
            }

            .banner-item .banner-content h3 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>

<body>
    <!-- Main content -->
    <main>
        <!-- Banner Grid -->
        <div class="banner-grid">
            <!-- Main Banner -->
            <div class="banner-item main">
                <img src="/PHP/gearpc-frontend/assets/img/banners/gpu-nvidia.jpg">
                <div class="banner-content">
                    <h3>GeForce RTX 5060 Out Now</h3>
                    <a href="index.php?page=products&brand=nvidia&category=gpu" class="btn btn-light">Shop Now</a>
                </div>
            </div>

            <!-- Sub Banners -->
            <a href="index.php?page=products&brand=intel&category=cpu" class="banner-item sub">
                <img src="/PHP/gearpc-frontend/assets/img/banners/cpu-intel.jpg">
            </a>

            <a href="index.php?page=products&category=laptop" class="banner-item sub">
                <img src="/PHP/gearpc-frontend/assets/img/banners/laptop.jpg">
            </a>

            <a href="index.php?page=products&category=keyboard" class="banner-item sub">
                <img src="/PHP/gearpc-frontend/assets/img/banners/keyboard.jpg">
            </a>

            <a href="index.php?page=products&brand=razer&category=mouse" class="banner-item sub">
                <img src="/PHP/gearpc-frontend/assets/img/banners/mouse-razer.jpg">
            </a>
        </div>

        <!-- Featured Products Slider -->
        <?php
        $title = "Keyboard Best Deals";
        $categoryCode = "cpu";
        $sortBy = "discountPercentage";
        $sortOrder = "desc";
        include(__DIR__ . '/../components/slider-products/slider-products.php');
        ?>

        <?php
        $title = "Mouse Best Deals";
        $categoryCode = "mouse";
        $sortBy = "discountPercentage";
        $sortOrder = "desc";
        include(__DIR__ . '/../components/slider-products/slider-products.php');
        ?>

        <?php
        $title = "Laptop Best Seller";
        $categoryCode = "laptop";
        $sortBy = "discountPercentage";
        $sortOrder = "desc";
        include(__DIR__ . '/../components/slider-products/slider-products.php');
        ?>
        <!-- Latest News Slider -->
        <div class="news-slider-container">
            <h2>Latest News</h2>
            <div class="news-slider" id="newsSlider">
                <?php
                if ($rss) {
                    $count = 0;

                    foreach ($rss->channel->item as $item) {
                        if (++$count > 10)
                            break;

                        $title = $item->title;
                        $link = $item->link;
                        $pubDate = date('d/m/Y', strtotime($item->pubDate));

                        $imgSrc = '';
                        if (isset($item->children('media', true)->thumbnail)) {
                            $imgSrc = (string) $item->children('media', true)->thumbnail->attributes()->url;
                        } elseif (isset($item->enclosure)) {
                            $imgSrc = (string) $item->enclosure->attributes()->url;
                        } else {
                            $imgSrc = 'https://via.placeholder.com/300x180?text=No+Image';
                        }

                        echo "<div class='news-slide'>";
                        echo "<img src='$imgSrc' alt='Thumbnail'>";
                        echo "<div class='news-slide-content'>";
                        echo "<a href='$link' target='_blank'>" . mb_substr(strip_tags($title), 0, 40) . "</a>";
                        echo "<small>$pubDate</small>";
                        echo "</div></div>";
                    }
                }
                ?>
            </div>
            <button class="slide-btn left" onclick="slideLeft('newsSlider')">&#10094;</button>
            <button class="slide-btn right" onclick="slideRight('newsSlider')">&#10095;</button>
        </div>
    </main>

    <script>
        // Chức năng điều hướng cho slider tin tức
        function slideRight(sliderId) {
            const slider = document.getElementById(sliderId);
            const slideWidth = calculateSlideWidth(slider);

            if (slider.scrollLeft + slider.offsetWidth >= slider.scrollWidth - slideWidth) {
                slider.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
                slider.scrollBy({ left: slideWidth, behavior: 'smooth' });
            }
        }

        function slideLeft(sliderId) {
            const slider = document.getElementById(sliderId);
            const slideWidth = calculateSlideWidth(slider);

            if (slider.scrollLeft <= 0) {
                slider.scrollTo({ left: slider.scrollWidth - slider.offsetWidth, behavior: 'smooth' });
            } else {
                slider.scrollBy({ left: -slideWidth, behavior: 'smooth' });
            }
        }

        // Calculate slide width based on screen size
        function calculateSlideWidth(slider) {
            const slideElements = slider.querySelectorAll('.news-slide, .product-slide');
            if (slideElements.length === 0) return 0;

            const slideElement = slideElements[0];
            const slideWidth = slideElement.offsetWidth;
            const slideMargin = parseInt(window.getComputedStyle(slideElement).marginRight);

            return slideWidth + (isNaN(slideMargin) ? 20 : slideMargin); // default gap is 20px
        }

        document.addEventListener('DOMContentLoaded', function() {
            const newsSlider = document.getElementById('newsSlider');
            let autoSlideInterval;
            let touchStartX = 0;
            let touchEndX = 0;

            // Touch events for mobile swipe support
            newsSlider.addEventListener('touchstart', function(e) {
                clearInterval(autoSlideInterval); // Stop auto sliding on touch
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });

            newsSlider.addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;

                if (touchEndX < touchStartX - 50) {
                    // Swipe left to go right
                    slideRight('newsSlider');
                } else if (touchEndX > touchStartX + 50) {
                    // Swipe right to go left
                    slideLeft('newsSlider');
                }

                startAutoSlide(); // Resume auto sliding after touch
            }, { passive: true });

            // Start auto sliding with pause on hover
            function startAutoSlide() {
                autoSlideInterval = setInterval(() => slideRight('newsSlider'), 3000);
            }

            // Pause auto slide when hovering over the slider
            newsSlider.addEventListener('mouseenter', function() {
                clearInterval(autoSlideInterval);
            });

            newsSlider.addEventListener('mouseleave', function() {
                startAutoSlide();
            });

            // Initialize auto slide for news slider
            startAutoSlide();
        });
    </script>
</body>

</html>