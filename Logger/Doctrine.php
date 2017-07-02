<?php
namespace Corley\OpenTracingBundle\Logger;

use Doctrine\DBAL\Logging\SQLLogger;

use Corley\Zipkin\Tracer;
use Corley\Zipkin\ClientSend;

class Doctrine implements SQLLogger
{
    private $tracer;
    private $serviceName;
    private $params;

    private $span;
    private $rootSpan;

    public function __construct(Tracer $tracer, $serviceName, $params)
    {
        $this->tracer = $tracer;
        $this->serviceName = $serviceName;
        $this->params = $params;
    }

    public function startQuery($sql, array $params = null, array $types = null)
    {
        $rootSpan = $this->tracer->findOneBy("kind", "root");

        $span = new ClientSend("Query", $this->serviceName);
        $span->setChildOf($rootSpan);

        $this->tracer->addSpan($span);

        foreach ($this->params as $key => $value) {
            $span->getBinaryAnnotations()->set($key, $value);
        }

        $span->getBinaryAnnotations()->set("db.sql", $sql);
        $span->getBinaryAnnotations()->set("db.params", json_encode($params));

        $this->span = $span;
    }

    public function stopQuery()
    {
        $this->span->receive();
    }
}
