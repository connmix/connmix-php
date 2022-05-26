<?php

require __DIR__ . '/../vendor/autoload.php';

$client = \Connmix\ClientBuilder::create()
    ->setHost('127.0.0.1:6787')
    ->build();
$onConnect = function (\Connmix\AsyncNodeInterface $node) {
    // 消费内存队列
    $node->pop('foo');
};
$onMessage = function (\Connmix\AsyncNodeInterface $node) {
    $message = $node->message();
    switch ($message->type()) {
        case "event":
            $clientID = $message->clientID();
            $data = $message->data();
            // 回复消息
            $node->meshSend($clientID, sprintf("received: %s", $data['frame']['data'] ?? ''));
            break;
        case "result":
            $success = $message->success();
            $fail = $message->fail();
            $total = $message->total();
            break;
        case "error":
            $error = $message->error();
            break;
        default:
            $payload = $message->payload();
    }
};
$onError = function (\Throwable $e) {
    // handle error
};
$client->on($onConnect, $onMessage, $onError);
$client->run();
