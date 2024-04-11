<?php
// proxy.php
// Read JSON payload from the request body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['coordinates'])) {
    echo json_encode(['error' => 'No coordinates provided']);
    exit;
}

$url = 'https://api.openrouteservice.org/v2/directions/driving-car';
$apiKey = '5b3ce3597851110001cf62489161ba1d575b489d9797570815527731'; // Replace this with your actual ORS API key
$postData = [
    'coordinates' => $data['coordinates']
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: ' . $apiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
$response = curl_exec($ch);
curl_close($ch);

echo $response; // Output the response from ORS API
?>
