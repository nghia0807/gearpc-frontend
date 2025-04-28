<?php
/**
 * GearPC Website - Database Connection
 * 
 * This file handles database connections for the GearPC website.
 * Note: Since the application appears to be using a REST API for all data operations,
 * this file may not be needed, but is included to maintain compatibility with 
 * existing code references.
 */

// Define constants for API base URL
define('API_BASE_URL', 'http://localhost:5000/api');

/**
 * Performs an API request using cURL
 * 
 * @param string $method HTTP method (GET, POST, PUT, DELETE)
 * @param string $endpoint API endpoint (without base URL)
 * @param string|null $token Bearer token for authentication
 * @param array|null $data Data to send with the request
 * @return array Response data as associative array
 */
function apiRequest($method, $endpoint, $token = null, $data = null) {
    $url = API_BASE_URL . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Set request headers
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Configure request based on method
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    // Execute the request
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        return ['success' => false, 'message' => $err];
    }
    
    return json_decode($response, true);
}
?>