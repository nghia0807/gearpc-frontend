<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Card</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CDN for star icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        .product-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-color: #fff;
            max-width: 250px;
            margin: 15px;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .product-card-body {
            padding: 15px;
            text-align: center;
        }
        .product-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 10px 0;
            color: #333;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .product-card-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #e44d26;
            margin-bottom: 10px;
        }
        .product-card-rating {
            color: #f1c40f;
            margin-bottom: 10px;
        }
        .product-card-rating .fa-star-half-alt {
            color: #f1c40f;
        }
        .product-card-rating .fa-star:not(.fas) {
            color: #ccc;
        }
    </style>
</head>
<body>
    <!-- Product Card Component -->
    <div class="product-card">
        <img src="https://via.placeholder.com/250x200" alt="Product Image">
        <div class="product-card-body">
            <div class="product-card-rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
                <span>(4.5)</span>
            </div>
            <h5 class="product-card-title">Gaming Laptop ASUS ROG</h5>
            <div class="product-card-price">$1,299.99</div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>