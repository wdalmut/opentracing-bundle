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

## Guzzle integration

Integrate Guzzle middleware

```php
use Corley\OpenTracingBundle\Guzzle\Psr7SpanFactory;
use Corley\OpenTracingBundle\Guzzle\TracerMiddlewareFactory;

$psr7Factory = new Psr7SpanFactory();
$openTracingMiddleware = new TracerMiddlewareFactory($tracer, $psr7Factory);

$handler = new CurlHandler();

$stack = new HandlerStack();
$stack->setHandler($handler);
$stack->push($openTracingMiddleware);

$client = new Client(["handler" => $stack]);
```

### Symfony DiC

```
http.handler:
class: GuzzleHttp\Handler\CurlHandler

http.stack:
class: GuzzleHttp\HandlerStack
calls:
    - ["setHandler", ["@http.handler"]]
    - ["push", ["@opentracing.guzzle.middleware"]]

http.client:
class: GuzzleHttp\Client
arguments:
    - {"handler": "@http.stack"}
```

