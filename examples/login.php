<?php

namespace SuiteCRMRestClient;
use SuiteCRMRestClient\Adapters\DummyAdapter;

require __DIR__ . '/../vendor/autoload.php';

$adapter = new DummyAdapter();

SuiteCRMRestClient::init($adapter);

$client = SuiteCRMRestClient::getInstance();

$result = $client->login() ? "Login successful" : "Login failed";

echo '<pre>';
print_r($result);
echo '</pre>';
