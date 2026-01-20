<?php
// Test 2Factor.in API

$apiKey = "e038e973-af07-11e9-ade6-0200cd936042"; // Your API key
$mobile = "91" . $_GET['mob']; // Mobile number with country code
$otp = rand(100000, 999999); // Random OTP

echo "<h2>2Factor.in API Test</h2>";
echo "<p><strong>Mobile:</strong> $mobile</p>";
echo "<p><strong>OTP:</strong> $otp</p>";
echo "<hr>";

// API parameters
$data = array(
    'module' => 'TRANS_SMS',
    'apikey' => $apiKey,
    'to' => $mobile,
    'from' => 'RIDBYK',
    'msg' => $otp . ' is your One Time Password to login. Do not share this with anyone.'
);

echo "<h3>Sending SMS...</h3>";
echo "<p>Parameters:</p>";
echo "<pre>";
print_r($data);
echo "</pre>";

// Send request
$ch = curl_init('https://2factor.in/API/R1/');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<h3>API Response:</h3>";
echo "<p><strong>HTTP Code:</strong> $http_code</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>";
var_dump($response);
echo "</pre>";

if($curl_error) {
    echo "<p><strong style='color:red'>cURL Error:</strong> $curl_error</p>";
} else {
    echo "<p><strong style='color:green'>✅ No cURL errors</strong></p>";
}

if($http_code == 200) {
    echo "<p><strong style='color:green'>✅ API working!</strong></p>";
} else {
    echo "<p><strong style='color:red'>❌ API issue - Check API key or parameters</strong></p>";
}
?>
