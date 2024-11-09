<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once('vendor/autoload.php');

// Create a new client
$client = new \GuzzleHttp\Client();

// Create a chat session
$response = $client->request('POST', 'https://api.on-demand.io/chat/v1/sessions', [
    'body' => '{"externalUserId":"user1","pluginIds":["plugin-1716334779","plugin-1716119225"]}',
    'headers' => [
        'accept' => 'application/json',
        'apikey' => 'WYmKj3xXqKTUP94G58Z9YR4FPzWRAmeU',
        'content-type' => 'application/json',
    ],
]);

// Decode the response to get the session ID
$sessionData = json_decode($response->getBody(), true);

// Check if the response contains session data
if (isset($sessionData['data']['id'])) {
    $sessionId = $sessionData['data']['id']; // Get the session ID
    $externalUserId = $sessionData['data']['externalUserId']; 

    // Save the session ID and external user ID to a JSON file
    file_put_contents('session_data.json', json_encode(['sessionId' => $sessionId, 'externalUserId' => $externalUserId]));

    echo "Session ID: " . $sessionId . PHP_EOL;
} else {
    echo "Error: Session creation failed. Response: " . $response->getBody() . PHP_EOL;
}
?>