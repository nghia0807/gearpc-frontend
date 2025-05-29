<?php
/**
 * Cart Badge Component
 * Displays the number of items in the user's cart as a badge.
 * 
 * @param string $token The user's authentication token
 * @return array With 'count' for the number of items and 'success' boolean
 */

// Ajax endpoint to get cart count
if (isset($_GET['action']) && $_GET['action'] === 'getCartCount') {
    session_name('user_session');
    session_start();
    
    $token = $_SESSION['token'] ?? null;
    $result = getCartItemCount($token);
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
function getCartItemCount($token): array
{
    if (!$token) {
        return ['success' => false, 'count' => 0];
    }
    
    $apiUrl = 'http://tamcutephomaique.ddns.net:5001/api/carts/get';
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ],
        CURLOPT_TIMEOUT => 5 // Short timeout since this is a UI element
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['success' => false, 'count' => 0];
    }

    $data = json_decode($response, true);
    
    if (!$data || !isset($data['success']) || !$data['success']) {
        return ['success' => false, 'count' => 0];
    }

    // Get items array and count its elements
    $cartItems = $data['data']['items'] ?? [];
    $itemCount = count($cartItems);
    
    return [
        'success' => true,
        'count' => $itemCount
    ];
}

/**
 * Renders the cart badge HTML
 * 
 * @param int $count The number of items in the cart
 * @return string The HTML for the cart badge
 */
function renderCartBadge($count): string
{
    if ($count <= 0) {
        return '';
    }
    
    return '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge">' . 
           $count . 
           '<span class="visually-hidden">items in cart</span></span>';
}

