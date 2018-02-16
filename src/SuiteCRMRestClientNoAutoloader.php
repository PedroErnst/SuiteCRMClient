<?php

// Include this file if your project doesn't use autoloader

include_once __DIR__ . '/SuiteCRMRestClient.php';
include_once __DIR__ . '/AdapterLoader.php';
include_once __DIR__ . '/Adapters/ConfigurationAdapter.php';

$activeAdapter = \SuiteCRMRestClient\AdapterLoader::getAdapterClassFile();

include_once __DIR__ . '/' . $activeAdapter ;