<?php
namespace Corley\OpenTracingBundle\Tests\Logger;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Corley\OpenTracingBundle\Logger\Doctrine;
use Corley\Zipkin\Span;

class DoctrineTest extends TestCase
{
    public function testSendTraces()
    {
        $mock = $this->getMockBuilder("Corley\\Zipkin\\Tracer")
            ->disableOriginalConstructor()
            ->setMethods(["addSpan", "findOneBy"])
            ->getMock();
        $mock
            ->expects($this->once())
            ->method('addSpan')
            ->with($this->anything())
            ->willReturn(null);

        $span = new Span("test");
        $mock
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($span);

        $logger = new Doctrine($mock, "test", []);

        $logger->startQuery("SELECT * FROM user");
        $logger->stopQuery();
    }
}
