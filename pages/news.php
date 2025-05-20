<?php
require_once __DIR__ . '/../includes/session_init.php';
$rss_url = "https://www.androidcentral.com/rss.xml";
$rss = simplexml_load_file($rss_url);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
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

        .rss-slider-container {
            position: relative;
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
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
        }

        .rss-slide-content a:hover {
            color: #e91e63;
        }

        .rss-slide-content small {
            font-size: 12px;
            color: #888;
        }

        .slide-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: rgb(14, 14, 14);
            font-size: 24px;
            padding: 12px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 10;
            transition: transform 0.3s, background 0.3s;
        }

        .slide-btn:hover {
            transform: translateY(-50%) scale(1.2);
        }

        .slide-btn.left {
            left: -15px;
        }

        .slide-btn.right {
            right: -15px;
        }

        .rss-grid-container {
            max-width: 1000px;
            margin: 60px auto;
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
        const slideWidth = 320;
        let position = 0;

        function slideRight() {
            if (position + slider.offsetWidth >= slider.scrollWidth - slideWidth) {
                slider.scrollTo({ left: 0, behavior: 'smooth' });
                position = 0;
            } else {
                position += slideWidth;
                slider.scrollBy({ left: slideWidth, behavior: 'smooth' });
            }
        }

        function slideLeft() {
            if (position <= 0) {
                position = slider.scrollWidth - slider.offsetWidth;
                slider.scrollTo({ left: position, behavior: 'smooth' });
            } else {
                position -= slideWidth;
                slider.scrollBy({ left: -slideWidth, behavior: 'smooth' });
            }
        }

        setInterval(slideRight, 3000);
    </script>

</body>

</html>