# IronMQ Laravel Queue Driver

This package provides a IronMQ (~4.0 SDK) driver for the Laravel queue system.

## History
This repository is a fork of [LaravelCollective IronMQ driver repo](https://github.com/LaravelCollective/iron-queue).

I've decided to fork and maintain IronMQ driver repository due to lack of support from original authors/maintainers.

## Installation
- `composer require hydreflab/iron-queue` (currently for Laravel 5.6)
- since Laravel 5.5, no service provider registration is needed
- prior to Laravel 5.5, register service provider in your `config/app.php`
    ```
    'providers' => [
        // ...
    
        Collective\IronQueue\IronQueueServiceProvider::class,
    ]
    ```
- configure your `iron` queue driver in your `config/queue.php`.
- set `iron` as your queue driver in your `.env`.

To install driver for Laravel 5.x, run `composer require hydreflab/iron-queue:5.x.*` and replace `x` with the desired version.

Sample Configuration:

```php
'iron' => [
    'driver'  => 'iron',
    'host'    => 'mq-aws-us-east-1-1.iron.io',
    'token'   => 'your-token',
    'project' => 'your-project-id',
    'queue'   => 'your-queue-name',
    'encrypt' => true,
    'timeout' => 60
],
```
