<?php

namespace SuiteCRMRestClient;
use SuiteCRMRestClient\Interfaces\DummyAdapter;

require __DIR__ . '/../vendor/autoload.php';

$adapter = new DummyAdapter();

$client = SuiteCRMRestClient::getInstance($adapter);

$client->login();

$data = [
    'first_name' => 'John',
    'last_name' => 'Doe',
];

$result = $client->setEntry('Contacts', $data);

echo '<pre>';
print_r($result);
echo '</pre>';