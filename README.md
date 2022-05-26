# CONNMIX PHP client

通过该客户端可以使用 PHP 来开发分布式长连接服务，客户端可以消费 connmix 指定内存队列的用户消息，执行业务逻辑后响应到对应的用户端。

## 快速上手

### 安装

```
composer require connmix/connmix
```

### 创建客户端

该客户端消费消息为异步模式，主动发送为同步模式。

- `$onConnect` 闭包内处理初始化逻辑，`$onMessage` 闭包内处理业务逻辑，`$onError` 闭包内处理网络异常。
- `$node->consume('foo')` 消费内存队列，可以同时消费多个；队列中的数据是由 entry.lua 的 `mix.queue.push()` 方法推入。
- 可以在 `Laravel`、`ThinkPHP` 等任意框架中使用。
- 使用 `meshSend()`、`meshPublish()` 方法给客户端响应数据。
- 无需处理重连，断线后客户端会自动重连。
- connmix 集群增加节点后客户端无需重启，客户端会自动获取新增的节点，自动移除下线的节点。

> 异步模式只支持在 PHP CLI 模式下运行

```php
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
```

## 设置上下文

我们可以给每个连接设置上下文，在整个连接内有效，通过这个功能我们可以实现用户鉴权登录。

- 首先我们在 lua 协议 `on_message` 方法中增加以下代码，只有在接收到的消息为 `userauth` 才会触发，`wait_context_value` 会使该连接的消息处理暂停，直到 `user_id`被设置值后才会继续执行。

```lua
if data["op"] == "userauth" then
    conn:wait_context_value("user_id")
end
```

- 通过客户端执行 `set_context_value` 来完成鉴权。

```php
$onMessage = function (\Connmix\AsyncNodeInterface $node) {
    $message = $node->message();
    switch ($message->type()) {
        case "event":
            $clientID = $message->clientID();
            $data = $message->data();
            $op = $data['frame']['data']['op'] ?? '';
            $args = $data['frame']['data']['args'] ?? [];
            if ($op == 'userauth') {
                // 通过 $args 到数据库查询用户权限
                // ...
                
                $node->setContextValue($clientID, 'user_id', 1000);
            }
            break;
    }
};
```

## 订阅频道

通过给某个连接订阅频道，我们可以给这些连接分组，比如：我有手机、电脑的2个连接，在通过授权验证后，我们可以都订阅 `user_10001` 频道，这样我们给该频道发送消息时就可以达到多个设备都可以收到消息的效果。

```php
$onMessage = function (\Connmix\AsyncNodeInterface $node) {
    $message = $node->message();
    switch ($message->type()) {
        case "event":
            $clientID = $message->clientID();
            $node->subscribe($clientID, "user_10001");
            break;
    }
};
```

## 推送频道

网格推送会在服务网格内自动寻址，可以在任何节点发起。

- 接收消息时被动推送

```php
$onMessage = function (\Connmix\AsyncNodeInterface $node) {
    $message = $node->message();
    switch ($message->type()) {
        case "event":
            $node->meshPublish("user_10001", '{"broadcast":"ok"}');
            break;
    }
};
```

- 主动推送 (同步模式)

```php
$message = $client->node()->meshPublish("user_10001", '{"broadcast":"ok"}');
```

## 发送到客户端

网格发送会在服务网格内自动寻址，可以在任何节点发起。

- 接收消息时被动发送

```php
$onMessage = function (\Connmix\AsyncNodeInterface $node) {
    $message = $node->message();
    switch ($message->type()) {
        case "event":
            $clientID = $message->clientID();
            $node->meshSend($clientID, '{"result":"ok"}');
            break;
    }
};
```

- 主动发送 (同步模式)

```php
$message = $client->node()->meshSend($clientID, '{"result":"ok"}');
```

## License

Apache License Version 2.0, http://www.apache.org/licenses/
