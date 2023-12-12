<?php

namespace Tests\Ddrv\Container;

use Ddrv\Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

class ContainerTest extends TestCase
{
    public function testHas()
    {
        $container = new Container();
        $container->value('key', 'value');
        $this->assertTrue($container->has('key'));
        $this->assertFalse($container->has('other'));
    }

    public function testNotFound()
    {
        $container = new Container();
        $this->expectException(NotFoundExceptionInterface::class);
        $this->assertSame('value', $container->get('key'));
    }

    public function testSet()
    {
        $container = new Container();
        $container->value('key', 'value');
        $this->assertSame('value', $container->get('key'));
    }

    public function testService()
    {
        $container = new Container();
        $container->service('key', function () {
            $test = new stdClass();
            $test->test = null;
            return $test;
        });
        $this->assertTrue($container->has('key'));
        $v1 = $container->get('key');
        $this->assertInstanceOf('stdClass', $v1);
        $v2 = $container->get('key');
        $this->assertInstanceOf('stdClass', $v2);
        $this->assertTrue($v1 === $v2);
        $v2->test = 'phpunit';
        $this->assertTrue($v1->test === $v2->test);
    }

    public function testInstance()
    {
        $container = new Container();
        $container->instance('key', function () {
            $test = new stdClass();
            $test->test = null;
            return $test;
        });
        $this->assertTrue($container->has('key'));
        $v1 = $container->get('key');
        $this->assertInstanceOf('stdClass', $v1);
        $v2 = $container->get('key');
        $this->assertInstanceOf('stdClass', $v2);
        $this->assertFalse($v1 === $v2);
        $v2->test = 'phpunit';
        $this->assertFalse($v1->test === $v2->test);
    }

    public function testDelegate()
    {
        $container = new Container();
        $delegated = new Container();
        $delegated->value('key', 'value');
        $this->assertFalse($container->has('key'));
        $this->assertTrue($delegated->has('key'));
        $container->delegate($delegated);
        $this->assertTrue($container->has('key'));
        $this->assertSame('value', $container->get('key'));
    }

    public function testBind()
    {
        $container = new Container();
        $container->service('class', function () {
            $test = new stdClass();
            $test->test = null;
            return $test;
        });
        $container->bind('alias', 'class');

        $this->assertTrue($container->has('class'));
        $this->assertTrue($container->has('alias'));

        $v1 = $container->get('class');
        $v1->test = 'phpunit';
        $v2 = $container->get('alias');
        $this->assertSame($v1, $v2);
    }

    public function testBindToNonExistentService()
    {
        $container = new Container();
        $container->bind('alias', 'class');
        $this->assertFalse($container->has('alias'));
        $container->value('class', new stdClass());
        $this->assertTrue($container->has('alias'));
    }

    public function testBindChain()
    {
        $container = new Container();
        $container->service('foo', function () {
            $test = new stdClass();
            $test->test = null;
            return $test;
        });
        $container->bind('bar', 'foo');
        $container->bind('baz', 'bar');

        $this->assertTrue($container->has('foo'));
        $this->assertTrue($container->has('bar'));
        $this->assertTrue($container->has('baz'));

        $foo = $container->get('foo');
        $foo->test = 'phpunit';
        $bar = $container->get('bar');
        $baz = $container->get('baz');
        $this->assertSame($foo, $bar);
        $this->assertSame($foo, $baz);
    }

    public function testBindRecursive()
    {
        $container = new Container();
        $this->expectException(ContainerExceptionInterface::class);
        $path = ['service-1', 'service-5', 'service-4', 'service-3', 'service-2', 'service-1'];
        $message = 'Can not bind service-1 to service-5. Recursion detected: ' . implode(' -> ', $path);
        $this->expectExceptionMessage($message);
        $container->bind('service-2', 'service-1');
        $container->bind('service-3', 'service-2');
        $container->bind('service-4', 'service-3');
        $container->bind('service-5', 'service-4');
        $container->bind('service-1', 'service-5');
    }

    public function testBindToSelf()
    {
        $container = new Container();
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Can not bind service to service. Recursion detected: service -> service');
        $container->bind('service', 'service');
    }
}
