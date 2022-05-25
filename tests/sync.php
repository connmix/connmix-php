<?php

require __DIR__ . '/../vendor/autoload.php';

$client = \Connmix\ClientBuilder::create()
    ->setHost('127.0.0.1:6787')
    ->build();
$node = $client->node();
$msg = $node->meshSend(1000, "test");
var_dump($msg->error());
$msg = $node->meshSend(1000, "test");
var_dump($msg->error());
$node->close();
$client->close();
