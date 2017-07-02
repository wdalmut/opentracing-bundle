<?php
namespace Corley\OpenTracingBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Corley\OpenTracingBundle\DependencyInjection\CorleyOpenTracingExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CorleyOpenTracingBundleExtensionTest extends TestCase
{
    private $container;

    public function setUp()
    {
        $this->container = new ContainerBuilder();
    }

    public function testDatabaseEndpoint()
    {
        $this->container->setParameter("database_host", "127.0.0.2");
        $this->container->setParameter("database_name", "test");
        $this->container->setParameter("database_port", "3309");
        $this->container->setParameter("database_user", "testname");

        $extension = new CorleyOpenTracingExtension();
        $extension->load([

        ], $this->container);

        $dbEndpoint = $this->container->get('opentracing.db_endpoint');

        $this->assertEquals([
			"serviceName" => "database.test",
			"ipv4" => "127.0.0.2",
            "port" => "3309",
        ], $dbEndpoint->toArray());
    }

    public function testDatabaseEndpointWithHostname()
    {
        $this->container->setParameter("database_host", "test.hostname.local");
        $this->container->setParameter("database_name", "test");
        $this->container->setParameter("database_port", "3309");
        $this->container->setParameter("database_user", "testname");

        $extension = new CorleyOpenTracingExtension();
        $extension->load([], $this->container);

        $mock = $this->getMockBuilder("Corley\OpenTracingBundle\Endpoint\HostnameResolver")
            ->setMethods(['resolve'])
            ->getMock();

        $mock->method('resolve')->with('test.hostname.local')->willReturn('54.54.54.54');

        $this->container->set("opentracing.hostname_resolver", $mock);

        $dbEndpoint = $this->container->get('opentracing.db_endpoint');

        $this->assertEquals([
			"serviceName" => "database.test",
			"ipv4" => "54.54.54.54",
            "port" => "3309",
        ], $dbEndpoint->toArray());
    }
}
