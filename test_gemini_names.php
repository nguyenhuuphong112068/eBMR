<?php
$apiKey = 'AIzaSyCmi_EvnyY1078N2M0HQJO4AUN8CLmzlhk';
$url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$data = json_decode($response, true);
foreach($data['models'] as $m) {
    echo $m['name'] . "\n";
}
