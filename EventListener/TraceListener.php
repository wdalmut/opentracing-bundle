<?php
namespace Corley\OpenTracingBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Corley\Zipkin\ServerReceive;

class TraceListener
{
    private $tracer;
    private $span;

    public function __construct($tracer, $serviceName)
    {
        $this->tracer = $tracer;
        $this->serviceName = $serviceName;

        $hostname = (array_key_exists("SERVER_ADDR", $_SERVER) ? $_SERVER["SERVER_ADDR"] : null);
        $port = (array_key_exists("SERVER_PORT", $_SERVER) ? $_SERVER["SERVER_PORT"] : "80");

        $span = new ServerReceive("app", $this->serviceName, ($hostname) ? "{$hostname}:${port}" : null);

        $traceId = null;
        if (!empty($_SERVER['HTTP_X_B3_TRACEID'])) {
            $traceId = $_SERVER['HTTP_X_B3_TRACEID'];
        }

        $spanId = null;
        if (!empty($_SERVER['HTTP_X_B3_SPANID'])) {
            $spanId = $_SERVER['HTTP_X_B3_SPANID'];
        }

        $parentSpanId = null;
        if (!empty($_SERVER['HTTP_X_B3_PARENTSPANID'])) {
            $parentSpanId = $_SERVER['HTTP_X_B3_PARENTSPANID'];
        }

        $span->restoreContext($traceId, $spanId, $parentSpanId);

        $span->getBinaryAnnotations()->set("kind", "root");
        $this->tracer->addSpan($span);

        $this->span = $span;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->span->add('kernel.request');
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $this->span->add('exception');

        $this->span->getBinaryAnnotations()->set("error", true);
        $this->span->getBinaryAnnotations()->set("error.message", $event->getException()->getMessage());
        $this->span->getBinaryAnnotations()->set("error.trace", $event->getException()->getTraceAsString());
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $statusCode = $response->getStatusCode();

        if ($request->get("_route")) {
            $this->span->setName($request->get("_route"));
        }

        $this->span->getBinaryAnnotations()->set("http.status_code", $statusCode);
        $this->span->getBinaryAnnotations()->set("http.method", $request->getMethod());
        $this->span->getBinaryAnnotations()->set("http.url", $request->getUri());
        $this->span->getBinaryAnnotations()->set("http.host", $request->getHost());
        $this->span->getBinaryAnnotations()->set("http.path", $request->getPathInfo());

        if ($statusCode >= 500) {
            $this->span->getBinaryAnnotations()->set("error", true);
        }

        $this->span->sent();

        $this->tracer->send();
    }
}
