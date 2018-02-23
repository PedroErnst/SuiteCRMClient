<?php

namespace SuiteCRMRestClient;
use SuiteCRMRestClient\Adapters\ClientCredentialsAdapter;

require __DIR__ . '/../vendor/autoload.php';

$adapter = new ClientCredentialsAdapter();

SuiteCRMRestClient::init($adapter);

$client = SuiteCRMRestClient::getInstance();

$result = $client->login() ? "Login successful" : "Login failed";

echo '<pre>';
print_r($result);
echo '</pre>';
