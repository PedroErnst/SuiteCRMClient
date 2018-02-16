<?php

namespace SuiteCRMRestClient;
use SuiteCRMRestClient\Interfaces\DummyAdapter;

require __DIR__ . '/../vendor/autoload.php';

$adapter = new DummyAdapter();

$client = SuiteCRMRestClient::getInstance($adapter);

$result = $client->login() ? "Login successful" : "Login failed";

echo '<pre>';
print_r($result);
echo '</pre>';
