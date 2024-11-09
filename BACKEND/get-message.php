<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

$queryResponseFile = 'query_response.json';

if (!file_exists($queryResponseFile) || !is_readable($queryResponseFile)) {
    die(json_encode(['status' => 'error', 'message' => 'query_response.json file not found or is unreadable.']));
}

$jsonData = file_get_contents($queryResponseFile);
$data = json_decode($jsonData, true);

if (!isset($data['answer']) || empty($data['answer'])) {
    die(json_encode(['status' => 'error', 'message' => 'Title is missing from the query_response.json file.']));
}

try {
    $title = $data['answer'];
    $client = new Client();
    
    $serperHeaders = [
        'X-API-KEY' => '1b7c9da5560504ba5d07c7c655b9fe71ef141b48',
        'Content-Type' => 'application/json',
    ];

    $query = $title . " PRICE FROM ALL SELLERS";
    $serperBody = json_encode([
        "q" => $query,
        "location" => "India",
        "gl" => "in",
    ]);

    $serperRequest = new Request('POST', 'https://google.serper.dev/search', $serperHeaders, $serperBody);
    $serperResponse = $client->sendAsync($serperRequest)->wait();
    $serperData = json_decode($serperResponse->getBody(), true);

    file_put_contents('raw_response.json', json_encode($serperData, JSON_PRETTY_PRINT));

    $sessionData = json_decode(file_get_contents('session_data.json'), true);
    $sessionId = $sessionData['sessionId'];
    $rawResponse = file_get_contents('raw_response.json');

    $onDemandQuery = "$rawResponse list all the sellers their price and the respective link mentioned in the above json, response format should be: sellername : respective sellername , price : respective price , link : respective link";

    $onDemandResponse = $client->request('POST', 'https://api.on-demand.io/chat/v1/sessions/' . $sessionId . '/query', [
        'body' => json_encode([
            'query' => $onDemandQuery,
            'responseMode' => "sync",
            'endpointId' => 'predefined-openai-gpt4o',
            'pluginIds' => ['plugin-1716334779', 'plugin-1716119225'],
            'onlyFulfillment' => true
        ]),
        'headers' => [
            'accept' => 'application/json',
            'apikey' => 'WYmKj3xXqKTUP94G58Z9YR4FPzWRAmeU',
            'content-type' => 'application/json',
        ],
    ]);

    $onDemandData = json_decode($onDemandResponse->getBody(), true);

    if (isset($onDemandData['data']['messageId'])) {
        $messageId = $onDemandData['data']['messageId'];

        $sessionData['messageId'] = $messageId;
        file_put_contents('session_data.json', json_encode($sessionData));
        file_put_contents('product_details_response.json', json_encode($onDemandData['data'], JSON_PRETTY_PRINT));

        // Read the saved response from product_details_response.json
        $finalResponseData = json_decode(file_get_contents('product_details_response.json'), true);

        // Output results, including the `answer` field if available
        echo json_encode([
            'status' => 'success',
            'messageId' => $messageId,
            'serperStatus' => 'Saved to raw_response.json',
            'finalResponse' => 'Saved to product_details_response.json',
            'answer' => $finalResponseData['answer'] ?? 'No answer available',
        ]);
    } else {
        throw new Exception("Failed to get message ID from on-demand API");
    }

} catch (RequestException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Request failed: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
