<?php

require __DIR__ . '/../vendor/autoload.php';

$client = \Connmix\ClientBuilder::create()
    ->setHost('127.0.0.1:6787')
    ->build();
$onFulfilled = function (\Connmix\Context $ctx) {
    $message = $ctx->message();
    switch ($message->type()) {
        case "pop":
            $clientID = $message->clientID();
            $data = $message->data();
            $ctx->meshSend($clientID, sprintf("received: %s", $data['frame']['data'] ?? ''));
            break;
        case "result":
            $success = $message->success();
            $fail = $message->fail();
            $total = $message->total();
            break;
        case "error":
            $error = $message->error();
            break;
        case "unknown":
            $payload = $message->rawMessage()->getPayload();
            break;
    }
};
$onRejected = function (\Exception $e) {
    // handle error
};
$client->consume('foo')->then($onFulfilled, $onRejected);