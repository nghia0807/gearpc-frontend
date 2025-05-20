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
    <title>Latest Tech News</title>
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #eef2f5;
            margin: 0;
        }

        .rss-widget {
            background: #fff;
            border-radius: 10px;
            max-width: 1000px;
            margin: 30px auto;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .rss-widget h2 {
            font-size: 28px;
            color: #0077cc;
            margin-bottom: 25px;
            border-bottom: 2px solid #0077cc;
            padding-bottom: 10px;
        }

        .rss-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 15px;
        }

        .rss-item:last-child {
            border-bottom: none;
        }

        .rss-item img {
            width: 150px;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
            background-color: #ddd;
        }

        .rss-item-content {
            flex: 1;
        }

        .rss-item-content a {
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            color: #333;
            display: inline-block;
        }

        .rss-item-content a:hover {
            color: #0077cc;
        }

        .rss-item-content small {
            color: #777;
            margin-bottom: 5px;
            font-size: 13px;
        }

        @media (max-width: 992px) {
            .rss-widget {
                padding: 25px;
            }

            .rss-widget h2 {
                font-size: 24px;
                margin-bottom: 20px;
            }
        }

        @media (max-width: 768px) {
            .rss-widget {
                padding: 20px;
            }

            .rss-item-content a {
                font-size: 16px;
            }

            .rss-item-content p {
                font-size: 14px;
            }
        }

        @media (max-width: 576px) {
            .rss-item {
                flex-direction: column;
                gap: 10px;
            }

            .rss-item img {
                width: 100%;
                height: 180px;
            }

            .rss-widget h2 {
                font-size: 22px;
            }
        }

        .rss-slider-container {
            position: relative;
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .rss-slider-container h2 {
            font-size: 28px;
            color: #0077cc;
            margin-bottom: 25px;
            border-bottom: 2px solid #0077cc;
            padding-bottom: 10px;
        }

        .rss-slider {
            display: flex;
            overflow-x: hidden;
            scroll-behavior: smooth;
            gap: 20px;
            padding-bottom: 10px;
            -webkit-overflow-scrolling: touch;
            /* Smooth scrolling on iOS */
        }

        .rss-slide {
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
            .rss-slide {
                flex: 0 0 calc(33.33% - 20px);
            }
        }

        @media (max-width: 768px) {
            .rss-slide {
                flex: 0 0 calc(50% - 20px);
                width: calc(50% - 20px);
            }

            .rss-slider-container h2 {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .rss-slide {
                flex: 0 0 calc(100% - 20px);
                width: calc(100% - 20px);
            }

            .rss-slider-container {
                padding: 15px;
            }

            .rss-slider-container h2 {
                font-size: 22px;
                margin-bottom: 20px;
            }
        }

        .rss-slide img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 8px;
        }

        .rss-slide-content {
            margin-top: 10px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .rss-slide-content a {
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

        .rss-slide-content a:hover {
            color: #e91e63;
        }

        .rss-slide-content small {
            font-size: 12px;
            color: #888;
            margin-top: 6px;
            display: block;
        }

        @media (max-width: 768px) {
            .rss-slide img {
                height: 140px;
            }
        }

        @media (max-width: 480px) {
            .rss-slide {
                padding: 8px;
            }

            .rss-slide img {
                height: 180px;
            }

            .rss-slide-content a {
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

        .rss-grid-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
        }

        .rss-grid-container h2 {
            font-size: 28px;
            color: #0077cc;
            margin-bottom: 25px;
            border-bottom: 2px solid #0077cc;
            padding-bottom: 10px;
        }

        .rss-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .rss-grid-item {
            background: #f8fafc;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s;
        }

        @media (max-width: 992px) {
            .rss-grid-container {
                padding: 25px;
            }

            .rss-grid-container h2 {
                font-size: 24px;
                margin-bottom: 20px;
            }

            .rss-grid {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .rss-grid-container {
                padding: 20px;
            }

            .rss-grid {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .rss-grid-container {
                padding: 15px;
            }

            .rss-grid-container h2 {
                font-size: 22px;
            }

            .rss-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        .rss-grid-item:hover {
            transform: translateY(-5px);
        }

        .rss-grid-item img {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }

        .rss-grid-content {
            padding: 15px;
        }

        .rss-grid-content a {
            font-size: 18px;
            font-weight: bold;
            color: #0077cc;
            text-decoration: none;
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.4;
        }

        .rss-grid-content a:hover {
            color: #e91e63;
        }

        .rss-grid-content small {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #777;
        }

        .rss-grid-content p {
            margin-top: 10px;
            font-size: 14px;
            color: #333;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        @media (max-width: 992px) {
            .rss-grid-content {
                padding: 12px;
            }

            .rss-grid-content a {
                font-size: 16px;
            }
        }

        @media (max-width: 768px) {
            .rss-grid-item img {
                height: 140px;
            }

            .rss-grid-content p {
                font-size: 13px;
                margin-top: 8px;
            }
        }

        @media (max-width: 576px) {
            .rss-grid-item img {
                height: 180px;
            }

            .rss-grid-content {
                padding: 15px;
            }

            .rss-grid-content a {
                font-size: 17px;
            }
        }
    </style>
</head>

<body>
    <div class="rss-grid-container">
        <h2>Technology news</h2>
        <div class="rss-grid">
            <?php
            if ($rss) {
                $count = 0;
                $index = 0;
                $startFrom = 5;

                foreach ($rss->channel->item as $item) {
                    if ($index++ < $startFrom)
                        continue;
                    if (++$count > 6)
                        break;

                    $title = $item->title;
                    $link = $item->link;
                    $pubDate = date('d/m/Y', strtotime($item->pubDate));
                    $description = $item->description;

                    $imgSrc = '';
                    if (isset($item->children('media', true)->thumbnail)) {
                        $imgSrc = (string) $item->children('media', true)->thumbnail->attributes()->url;
                    } elseif (isset($item->enclosure)) {
                        $imgSrc = (string) $item->enclosure->attributes()->url;
                    } else {
                        $imgSrc = 'https://via.placeholder.com/300x180?text=No+Image';
                    }

                    echo "<div class='rss-grid-item'>";
                    echo "<img src='$imgSrc' alt='Thumbnail'>";
                    echo "<div class='rss-grid-content'>";
                    echo "<a href='$link' target='_blank'>$title</a>";
                    echo "<small>$pubDate</small>";
                    echo "<p>" . mb_substr(strip_tags($description), 0, 100) . "...</p>";
                    echo "</div></div>";
                }
            }
            ?>
        </div>
    </div>

    <div class="rss-widget">
        <?php
        if ($rss) {
            echo "<h2>{$rss->channel->title}</h2>";
            $count = 0;

            foreach ($rss->channel->item as $item) {
                if ($count++ >= 5)
                    break;

                $title = $item->title;
                $link = $item->link;
                $pubDate = date('d/m/Y H:i', strtotime($item->pubDate));
                $description = $item->description;

                $imgSrc = '';
                if (isset($item->children('media', true)->thumbnail)) {
                    $imgSrc = (string) $item->children('media', true)->thumbnail->attributes()->url;
                } elseif (isset($item->enclosure)) {
                    $imgSrc = (string) $item->enclosure->attributes()->url;
                } else {
                    $imgSrc = 'https://via.placeholder.com/120x80?text=No+Image';
                }

                echo "<div class='rss-item'>";
                echo "<img src='$imgSrc' alt='Thumbnail'>";
                echo "<div class='rss-item-content'>";
                echo "<a href='$link' target='_blank'>$title</a>";
                echo "<br>";
                echo "<small>$pubDate</small>";
                echo "<p>" . strip_tags($description) . "</p>";
                echo "</div></div>";
            }
        } else {
            echo "Unable to load RSS feed.";
        }
        ?>
    </div>
    <div class="rss-slider-container">
        <h2>More news</h2>
        <div class="rss-slider" id="rssSlider">
            <?php
            if ($rss) {
                $count = 0;
                $index = 0;
                $startFrom = 11;

                foreach ($rss->channel->item as $item) {
                    if ($index++ < $startFrom)
                        continue;
                    if (++$count > 20)
                        break;

                    $title = $item->title;
                    $link = $item->link;
                    $pubDate = date('d/m/Y', strtotime($item->pubDate));
                    $description = $item->description;

                    $imgSrc = '';
                    if (isset($item->children('media', true)->thumbnail)) {
                        $imgSrc = (string) $item->children('media', true)->thumbnail->attributes()->url;
                    } elseif (isset($item->enclosure)) {
                        $imgSrc = (string) $item->enclosure->attributes()->url;
                    } else {
                        $imgSrc = 'https://via.placeholder.com/300x180?text=No+Image';
                    }

                    echo "<div class='rss-slide'>";
                    echo "<img src='$imgSrc' alt='Thumbnail'>";
                    echo "<div class='rss-slide-content'>";
                    echo "<a href='$link' target='_blank'>" . mb_substr(strip_tags($title), 0, 40) . "</a>";
                    echo "<small>$pubDate</small>";
                    echo "</div></div>";
                }
            }
            ?>
        </div>
        <button class="slide-btn left" onclick="slideLeft()">&#10094;</button>
        <button class="slide-btn right" onclick="slideRight()">&#10095;</button>
    </div>
    <script>
        const slider = document.getElementById('rssSlider');
        let position = 0;
        let slideWidth;
        let autoSlideInterval;
        let touchStartX = 0;
        let touchEndX = 0;

        // Calculate slide width based on screen size
        function calculateSlideWidth() {
            const viewportWidth = window.innerWidth;
            if (viewportWidth <= 480) {
                // For mobile, one slide at a time (full width)
                return slider.querySelector('.rss-slide').offsetWidth + 20; // width + gap
            } else if (viewportWidth <= 768) {
                // For tablets, two slides at a time
                return (slider.offsetWidth / 2);
            } else if (viewportWidth <= 992) {
                // For small desktops, three slides at a time
                return (slider.offsetWidth / 3);
            } else {
                // For large desktops, four slides at a time
                return (slider.offsetWidth / 4);
            }
        }

        function slideRight() {
            slideWidth = calculateSlideWidth();
            if (position + slider.offsetWidth >= slider.scrollWidth - slideWidth) {
                slider.scrollTo({ left: 0, behavior: 'smooth' });
                position = 0;
            } else {
                position += slideWidth;
                slider.scrollBy({ left: slideWidth, behavior: 'smooth' });
            }
        }

        function slideLeft() {
            slideWidth = calculateSlideWidth();
            if (position <= 0) {
                position = slider.scrollWidth - slider.offsetWidth;
                slider.scrollTo({ left: position, behavior: 'smooth' });
            } else {
                position -= slideWidth;
                slider.scrollBy({ left: -slideWidth, behavior: 'smooth' });
            }
        }

        // Touch events for mobile swipe support
        slider.addEventListener('touchstart', function (e) {
            clearInterval(autoSlideInterval); // Stop auto sliding on touch
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        slider.addEventListener('touchend', function (e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
            startAutoSlide(); // Resume auto sliding after touch
        }, { passive: true });

        // Handle swipe direction
        function handleSwipe() {
            if (touchEndX < touchStartX - 50) {
                // Swipe left to go right
                slideRight();
            } else if (touchEndX > touchStartX + 50) {
                // Swipe right to go left
                slideLeft();
            }
        }

        // Start auto sliding with pause on hover
        function startAutoSlide() {
            autoSlideInterval = setInterval(slideRight, 3000);
        }

        // Pause auto slide when hovering over the slider
        slider.addEventListener('mouseenter', function () {
            clearInterval(autoSlideInterval);
        });

        slider.addEventListener('mouseleave', function () {
            startAutoSlide();
        });

        // Handle window resize to adjust slide width
        window.addEventListener('resize', function () {
            slideWidth = calculateSlideWidth();
        });

        // Initialize
        slideWidth = calculateSlideWidth();
        startAutoSlide();
    </script>

</body>

</html>