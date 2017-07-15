<?php
namespace Corley\OpenTracingBundle\Tests\Guzzle;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Client;
use Corley\Zipkin\Span;
use Corley\OpenTracingBundle\Guzzle\TracerMiddleware;
use GuzzleHttp\TransferStats;

class TracerMiddlewareTest extends TestCase
{
    public function testCreateSpanAndAppendTraceInformation()
    {
        $mock = new MockHandler([
			new Response(200, ['X-Foo' => 'Bar'], "OK"),
		]);

        $handler = HandlerStack::create($mock);

        $tracer = $this->getMockBuilder("Corley\\Zipkin\\Tracer")
            ->disableOriginalConstructor()
            ->setMethods(["addSpan", "findOneBy"])
            ->getMock();
        $tracer
            ->expects($this->once())
            ->method('addSpan')
            ->with($this->anything())
            ->willReturn(null);
        $tracer->setIsSampled(true);

        $span = new Span("test");
        $tracer
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($span);

        $handler->push(new TracerMiddleware($tracer, "test"));

        $client = new Client(['handler' => $handler]);

        $statsCheck = false;
        $response = $client->request('GET', '/', [
            'on_stats' => function(TransferStats $stats) use (&$statsCheck) {
                $request = $stats->getRequest();

                $this->assertNotNull($request->getHeaderLine('X-B3-TraceId'));
                $this->assertNotNull($request->getHeaderLine('X-B3-SpanId'));
                $this->assertNotNull($request->getHeaderLine('X-B3-ParentSpanId'));
                $this->assertSame("1", $request->getHeaderLine('X-B3-Sampled'));

                $statsCheck = true;
            },
        ]);

        $this->assertTrue($statsCheck);
    }

    public function testCreateSpanWithErrorsAndAppendTraceInformation()
    {
        $mock = new MockHandler([
			new Response(500, ['X-Foo' => 'Bar'], "FAIL"),
		]);

        $handler = HandlerStack::create($mock);

        $tracer = $this->getMockBuilder("Corley\\Zipkin\\Tracer")
            ->disableOriginalConstructor()
            ->setMethods(["findOneBy"])
            ->getMock();
        $tracer->setIsSampled(true);

        $span = new Span("test");
        $tracer
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($span);

        $handler->push(new TracerMiddleware($tracer, "test"));

        $client = new Client(['handler' => $handler]);

        $statsCheck = false;

        try {
            $response = $client->request('GET', '/', [
                'on_stats' => function(TransferStats $stats) use (&$statsCheck, $span) {
                    $request = $stats->getRequest();

                    $this->assertNotNull($request->getHeaderLine('X-B3-TraceId'));
                    $this->assertNotNull($request->getHeaderLine('X-B3-SpanId'));
                    $this->assertNotNull($request->getHeaderLine('X-B3-ParentSpanId'));
                    $this->assertSame("1", $request->getHeaderLine('X-B3-Sampled'));

                    $statsCheck = true;
                },
            ]);
        } catch (\Exception $e) {} // mark error

        $spans = $tracer->getSpans();

        $latestSpan = $spans[count($spans)-1];
        $this->assertTrue($latestSpan->getBinaryAnnotations()->get("error"));
        $this->assertSame(500, $latestSpan->getBinaryAnnotations()->get("http.status_code"));

        $this->assertTrue($statsCheck);
    }
}
