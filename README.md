# OpenTracing Bundle

Add this bundle in `AppKernel.php`

```php
new Corley\OpenTracingBundle\CorleyOpenTracingBundle()
```

Prepare your `config_prod.yml`

```yml
corley_open_tracing:
    app_name: "your.application.name"
    zipkin: "http://192.168.0.5:9411"
```

## Log Doctrine Queries

In your `config_prod` just add:

```yml
doctrine:
    dbal:
        logging: true
```

## Symfony DiC

Tag your `HandlerStack` as a `corley.auth.guzzle_handler_stack` in other to
trace down your services calls.

```yml
http.handler:
    class: GuzzleHttp\Handler\CurlHandler

http.stack:
    class: GuzzleHttp\HandlerStack
    calls:
        - ["setHandler", ["@http.handler"]]
    tags:
        - { name: corley.auth.guzzle_handler_stack }


http.client:
    class: GuzzleHttp\Client
    arguments:
        - {"handler": "@http.stack"}
```

