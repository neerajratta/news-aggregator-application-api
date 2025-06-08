<?php

/**
 * User Preferences API Test Script
 * 
 * This script helps test the User Preferences endpoints.
 * First, it logs in a user to get a token.
 * Then uses that token to test the user preferences endpoints.
 */

// Configuration
$base_url = 'http://localhost:8000';
$login_endpoint = '/api/v1/user/login';
$preferences_endpoint = '/api/v1/user/preferences';
$feed_endpoint = '/api/v1/user/feed';

// User credentials - change these to match an existing user in your database
$email = 'test@example.com';
$password = 'password';

// Colors for terminal output
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$reset = "\033[0m";

echo "{$yellow}Starting User Preferences API Test{$reset}\n\n";

// Step 1: Login to get a token
echo "Step 1: Getting auth token...\n";
$login_data = [
    'email' => $email,
    'password' => $password,
];

$login_response = makeRequest('POST', $login_endpoint, $login_data);

if (!isset($login_response['access_token'])) {
    echo "{$red}Failed to login. Check your credentials.{$reset}\n";
    exit(1);
}

$token = $login_response['access_token'];
echo "{$green}Successfully obtained auth token{$reset}\n\n";

// Step 2: Get current preferences
echo "Step 2: Getting current preferences...\n";
$get_preferences_response = makeRequest('GET', $preferences_endpoint, null, $token);
echo "Current preferences: " . json_encode($get_preferences_response, JSON_PRETTY_PRINT) . "\n\n";

// Step 3: Update preferences
echo "Step 3: Updating preferences...\n";
$update_data = [
    'sources' => ['CNN', 'BBC', 'The New York Times'],
    'categories' => ['Politics', 'Technology', 'Science'],
    'authors' => ['John Smith', 'Jane Doe']
];

$update_response = makeRequest('PUT', $preferences_endpoint, $update_data, $token);
echo "Update response: " . json_encode($update_response, JSON_PRETTY_PRINT) . "\n\n";

// Step 4: Get updated preferences to verify
echo "Step 4: Getting updated preferences...\n";
$updated_preferences = makeRequest('GET', $preferences_endpoint, null, $token);
echo "Updated preferences: " . json_encode($updated_preferences, JSON_PRETTY_PRINT) . "\n\n";

// Step 5: Test the personalized feed
echo "Step 5: Getting personalized feed...\n";
$feed_response = makeRequest('GET', $feed_endpoint, null, $token);

if (isset($feed_response['articles'])) {
    $article_count = count($feed_response['articles']);
    echo "{$green}Received $article_count personalized articles{$reset}\n";
    
    // Show first article title if available
    if ($article_count > 0) {
        echo "First article title: {$feed_response['articles'][0]['title']}\n\n";
    }
} else {
    echo "{$yellow}No articles found or feed format unexpected{$reset}\n\n";
}

// Step 6: Reset preferences
echo "Step 6: Resetting preferences...\n";
$reset_response = makeRequest('DELETE', $preferences_endpoint, null, $token);
echo "Reset response: " . json_encode($reset_response, JSON_PRETTY_PRINT) . "\n\n";

// Step 7: Verify reset preferences
echo "Step 7: Verifying reset preferences...\n";
$reset_preferences = makeRequest('GET', $preferences_endpoint, null, $token);
echo "Preferences after reset: " . json_encode($reset_preferences, JSON_PRETTY_PRINT) . "\n\n";

echo "{$green}Test completed!{$reset}\n";

/**
 * Helper function to make API requests
 */
function makeRequest($method, $endpoint, $data = null, $token = null) {
    global $base_url, $red;
    
    $url = $base_url . $endpoint;
    $ch = curl_init($url);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data && in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo "{$red}cURL Error: " . curl_error($ch) . "{$reset}\n";
        curl_close($ch);
        exit(1);
    }
    
    curl_close($ch);
    return json_decode($response, true);
}
