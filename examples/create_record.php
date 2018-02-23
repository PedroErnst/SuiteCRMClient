<?php

namespace SuiteCRMRestClient;
use SuiteCRMRestClient\Adapters\DummyAdapter;

require __DIR__ . '/../vendor/autoload.php';

$adapter = new DummyAdapter();


SuiteCRMRestClient::init($adapter);

$client = SuiteCRMRestClient::getInstance();

$client->login();

$data = [
    'first_name' => 'John',
    'last_name' => 'Doe',
];

$result = $client->setEntry('Contacts', $data);

echo '<pre>';
print_r($result);
echo '</pre>';