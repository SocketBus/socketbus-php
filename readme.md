# SocketBus PHP Library
The SocketBus PHP Library 

#### Getting Started
1.  **Create an account** - 
1.  **Minimum requirements** -
1.  **Install the library** -
    ```bash
    composer require socketbus/socketbus-php
    ```
1.  **Using the library** -
 
## Create a SocketBus instance

```php
require 'vendor/autoload.php';

use SocketBus\SocketBus;

$config = [
    'app_id' => 's-1-J2PCu8g8sAejZeXx',
    'secret' => 'cdKBpcruwYQ96kvIaYiorbTFxRDCbVfj'
];

$socketBus = new SocketBus($config);
```

###### End-to-End Encryption
To ensure that your data is secure, you can enable End-to-end encryption under Application > Settings. This setting in combination with the option `custom_encryption_key` encrypts the payload and decrypts in the client when an authenticated user receives a encrypted payload.
```php
$config = [
    'app_id' => 's-1-J2PCu8g8sAejZeXx',
    'secret' => 'cdKBpcruwYQ96kvIaYiorbTFxRDCbVfj',
    'custom_encryption_key' => 'my-unique-key'
];
```


## Authentication

```php
$socketId = $request->socket_id;
$channelName = $request->channel_name;

if (/** verifies if user can access the request channel */) {
    // returns the token to the client
    return [
        'auth' => $socketbus->auth($socketId, $channelName)
    ];
}

```

###### Presence Authentication

```php
$socketId = $request->socket_id;
$channelName = $request->channel_name;
$userId = /** gets the current user id */;

if (/** verifies if user can access the request channel */) {
    // returns the data
    return $socketbus->authPresence($socketId, $channelName, $userId);
}
```

## Broadcasting

```php

$payload = [
    'type' => 'cupcake',
    'flavor' => 'sweet'
];

$sockebus->broadcast('food-observer', 'new-food', $payload);
```

## Resources
[API Docs](https://socketbus.com/docs) - Check-out the full documentation

## Related Projects
[SocketBus Laravel Library](https://github.com/SocketBus/socketbus-laravel) - Laravel library