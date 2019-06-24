<?php

declare(strict_types=1);

namespace LosTest\Cerberus;

use Los\Cerberus\Cerberus;
use Los\Cerberus\CerberusFactory;
use Los\Cerberus\CerberusInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

use function sleep;

class CerberusTest extends TestCase
{
    /** @var Cerberus */
    private $cerberus;

    /** @var array */
    private $cache = [];

    protected function setUp() : void
    {
        $storage = $this->createMock(CacheInterface::class);
        $storage->method('get')->will($this->returnCallback([$this, 'getCache']));
        $storage->method('set')->will($this->returnCallback([$this, 'setCache']));

        $this->cerberus = new Cerberus($storage, 2, 2);
    }

    /**
     * @param null|mixed $default
     * @return null|mixed
     */
    public function getCache(string $key, $default = null)
    {
        return $this->cache[$key] ?? $default;
    }

    /**
     * @param mixed $value
     */
    public function setCache(string $key, $value) : void
    {
        $this->cache[$key] = $value;
    }

    public function testFactory()
    {
        $storage = $this->createMock(CacheInterface::class);

        $sm = $this->createMock(ContainerInterface::class);
        $sm->method('get')->will($this->returnValueMap([
            [CacheInterface::class, $storage],
        ]));

        $this->assertInstanceOf(Cerberus::class, (new CerberusFactory())->__invoke($sm));
    }

    public function testCreatedClosed()
    {
        $this->assertSame(CerberusInterface::CLOSED, $this->cerberus->getStatus());
        $this->assertTrue($this->cerberus->isAvailable());
    }

    public function testFailure()
    {
        $this->cerberus->reportFailure();
        $this->assertSame(CerberusInterface::CLOSED, $this->cerberus->getStatus());
        $this->cerberus->reportFailure();
        $this->cerberus->reportFailure();
        $this->assertSame(CerberusInterface::OPEN, $this->cerberus->getStatus());
        $this->assertSame(CerberusInterface::OPEN, $this->cerberus->getStatus());
    }

    public function testSuccess()
    {
        $this->cerberus->reportFailure();
        $this->assertSame(CerberusInterface::CLOSED, $this->cerberus->getStatus());
        $this->cerberus->reportSuccess();
        $this->cerberus->reportFailure();
        $this->assertSame(CerberusInterface::CLOSED, $this->cerberus->getStatus());
    }

    public function testHalfOpen()
    {
        $this->cerberus->reportFailure();
        $this->assertSame(CerberusInterface::CLOSED, $this->cerberus->getStatus());
        $this->cerberus->reportFailure();
        $this->cerberus->reportFailure();
        $this->assertSame(CerberusInterface::OPEN, $this->cerberus->getStatus());
        sleep(3);
        $this->assertSame(CerberusInterface::HALF_OPEN, $this->cerberus->getStatus());
    }

    public function testHandleMoreServices()
    {
        $this->cerberus->reportFailure('service1');
        $this->assertSame(CerberusInterface::CLOSED, $this->cerberus->getStatus('service1'));
        $this->cerberus->reportFailure('service2');
        $this->assertSame(CerberusInterface::CLOSED, $this->cerberus->getStatus('service2'));
        $this->cerberus->reportFailure('service1');
        $this->cerberus->reportFailure('service1');
        $this->assertSame(CerberusInterface::OPEN, $this->cerberus->getStatus('service1'));
        $this->assertSame(CerberusInterface::CLOSED, $this->cerberus->getStatus('service2'));
    }
}
