<?php
session_name('user_session');

header('Content-Type: application/json');


if (isset($_SESSION['token'])) {
    echo json_encode(['token' => $_SESSION['token']]);
} else {
    http_response_code(401); 
    echo json_encode(['error' => 'Unauthorized']);
}
?>