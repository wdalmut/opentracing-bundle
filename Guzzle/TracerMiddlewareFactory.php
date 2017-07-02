<?php
namespace Corley\OpenTracingBundle\Guzzle;

use GuzzleHttp\Exception\RequestException;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Corley\Zipkin\Tracer;
use Corley\Zipkin\ClientSend;

class TracerMiddlewareFactory
{
    private $tracer;
    private $endpoint;

    public function __construct(Tracer $tracer, $endpoint)
    {
        $this->tracer = $tracer;
        $this->endpoint = $endpoint;
    }

    public function __invoke(callable $handler)
    {
        $tracer = $this->tracer;
        $endpoint = $this->endpoint;

        $rootSpan = $tracer->findOneBy("kind", "root");

        return function (RequestInterface $request, array $options) use ($handler, $rootSpan, $tracer, $endpoint) {
            $name = strtoupper($request->getMethod()) . ' ' . $request->getUri()->getPath();

            $span = new ClientSend($name, $endpoint);
            $span->setChildOf($rootSpan);
            $tracer->addSpan($span);

            $span->getBinaryAnnotations()->set("http.host",  $request->getUri()->getHost());
            $span->getBinaryAnnotations()->set("http.port",  $request->getUri()->getPort());
            $span->getBinaryAnnotations()->set("http.method", $request->getMethod());
            $span->getBinaryAnnotations()->set("http.path", $request->getUri()->getPath());

            $request = $request
                ->withHeader('X-B3-TraceId', (string) $span::getTraceId())
                ->withHeader('X-B3-SpanId', (string) $span->getId())
                ->withHeader('X-B3-Sampled', "1")
            ;

            return $handler($request, $options)->then(function (ResponseInterface $response) use ($span) {
                $span->getBinaryAnnotations()->set("http.status_code", $response->getStatusCode());

                if ($response->getStatusCode() >= 500) {
                    $span->getBinaryAnnotations()->set("error", true);
                }

                $span->receive();

                return $response;
            }, function ($reason) use ($span) {
                if ($reason instanceof RequestException) {
                    $span->getBinaryAnnotations()->set("error", true);
                    $span->getBinaryAnnotations()->set("http.body", (string)$response->getBody());

                    $span->receive();
                }
                throw $reason;
            });
        };
    }
}
