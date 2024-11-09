<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once('vendor/autoload.php');


$sessionData = json_decode(file_get_contents('session_data.json'), true);
$sessionId = $sessionData['sessionId'];  

$client = new \GuzzleHttp\Client();

// Get the query from the incoming request
$data = json_decode(file_get_contents('php://input'), true);
$inputUrl = $data['query'] ?? '';

$query = "$inputUrl   what is the title of the product menetioned in the above link , just tell me the title nothing else.";


$response = $client->request('POST', 'https://api.on-demand.io/chat/v1/sessions/' . $sessionId . '/query', [
    'body' => json_encode([
        'query' => $query, 
        'responseMode' => "sync",
        'endpointId' => 'predefined-openai-gpt4o', // Include the endpointId
        'pluginIds' => ['plugin-1716334779', 'plugin-1716119225'], // Include plugin IDs
        'onlyFulfillment' => true // Set onlyFulfillment to true
    ]),
    'headers' => [
        'accept' => 'application/json',
        'apikey' => 'WYmKj3xXqKTUP94G58Z9YR4FPzWRAmeU',
        'content-type' => 'application/json',
    ],
]);

// Decode response to get message data
$queryResponse = json_decode($response->getBody(), true);

// Check if the response contains message data
if (isset($queryResponse['data']['messageId'])) {
    $messageId = $queryResponse['data']['messageId']; // Get the message ID

    // Update the session data with the new message ID
    $sessionData['messageId'] = $messageId;  
    file_put_contents('session_data.json', json_encode($sessionData));  // Save updated session data

    // Create a new JSON file for the response
    file_put_contents('query_response.json', json_encode($queryResponse['data'], JSON_PRETTY_PRINT));  // Save query response

    echo "Message ID: " . $messageId . PHP_EOL;
    echo "Response: " . json_encode($queryResponse['data'], JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    echo "Error: Query submission failed. Response: " . $response->getBody() . PHP_EOL;
}
?>
