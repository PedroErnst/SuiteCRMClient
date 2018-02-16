<?php

namespace SuiteCRMRestClient;
use SuiteCRMRestClient\Interfaces\DummyAdapter;

require __DIR__ . '/../vendor/autoload.php';

$adapter = new DummyAdapter();

$client = SuiteCRMRestClient::getInstance($adapter);

$client->login();

$result = $client->getEntry('Users', $adapter->getUserID());

echo '<pre>';
print_r($result);
echo '</pre>';
