<?php

namespace SuiteCRMRestClient;
use SuiteCRMRestClient\Adapters\DummyAdapter;

require __DIR__ . '/../vendor/autoload.php';

$adapter = new DummyAdapter();


SuiteCRMRestClient::init($adapter);

$client = SuiteCRMRestClient::getInstance();

$client->login();

$result = $client->getApplicationLanguage();

echo '<pre>';
print_r($result);
echo '</pre>';
