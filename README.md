# OpenTracing Bundle

Add this bundle

```
new Corley\OpenTracingBundle\CorleyOpenTracingBundle()
```

Prepare your `config_prod.yml`

```
corley_open_tracing:
    app_name: "your.application.name"
    zipkin: "http://192.168.0.5:9411"
```

### Symfony DiC

```
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

