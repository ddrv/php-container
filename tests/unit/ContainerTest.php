<?php

namespace TestUnits\Ddrv\Container;

use Ddrv\Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;
use TestTools\Ddrv\Container\TestServiceProvider;

final class ContainerTest extends TestCase
{
    /**
     * @return void
     */
    public function testHas(): void
    {
        $container = new Container();
        $container->value('key', 'value');
        $this->assertTrue($container->has('key'));
        $this->assertFalse($container->has('other'));
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testNotFound(): void
    {
        $container = new Container();
        $this->expectException(NotFoundExceptionInterface::class);
        $this->assertSame('value', $container->get('key'));
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testSet(): void
    {
        $container = new Container();
        $container->value('key', 'value');
        $this->assertSame('value', $container->get('key'));
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testService(): void
    {
        $container = new Container();
        $container->service('key', function () {
            $test = new stdClass();
            $test->test = null;
            return $test;
        });
        $this->assertTrue($container->has('key'));
        $v1 = $container->get('key');
        $this->assertInstanceOf(stdClass::class, $v1);
        $v2 = $container->get('key');
        $this->assertInstanceOf(stdClass::class, $v2);
        $this->assertTrue($v1 === $v2);
        $v2->test = 'phpunit';
        $this->assertTrue($v1->test === $v2->test);
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testInstance(): void
    {
        $container = new Container();
        $container->instance('key', function () {
            $test = new stdClass();
            $test->test = null;
            return $test;
        });
        $this->assertTrue($container->has('key'));
        /** @var object{test:string} $v1 */
        $v1 = $container->get('key');
        $this->assertInstanceOf(stdClass::class, $v1);
        /** @var object{test:string} $v2 */
        $v2 = $container->get('key');
        $this->assertInstanceOf(stdClass::class, $v2);
        $this->assertFalse($v1 === $v2);
        $v2->test = 'phpunit';
        $this->assertFalse($v1->test === $v2->test);
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testDelegate(): void
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

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testBind(): void
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

        /** @var object{test:string} $v1 */
        $v1 = $container->get('class');
        $v1->test = 'phpunit';
        /** @var object{test:string} $v2 */
        $v2 = $container->get('alias');
        $this->assertSame($v1, $v2);
    }

    /**
     * @return void
     */
    public function testBindToNonExistentService(): void
    {
        $container = new Container();
        $container->bind('alias', 'class');
        $this->assertFalse($container->has('alias'));
        $container->value('class', new stdClass());
        $this->assertTrue($container->has('alias'));
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testBindChain(): void
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

        /** @var object{test:string} $foo */
        $foo = $container->get('foo');
        $foo->test = 'phpunit';
        /** @var object{test:string} $bar */
        $bar = $container->get('bar');
        /** @var object{test:string} $baz */
        $baz = $container->get('baz');
        $this->assertSame($foo, $bar);
        $this->assertSame($foo, $baz);
    }

    /**
     * @return void
     */
    public function testBindRecursive(): void
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

    /**
     * @return void
     */
    public function testBindToSelf(): void
    {
        $container = new Container();
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Can not bind service to service. Recursion detected: service -> service');
        $container->bind('service', 'service');
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testRegister(): void
    {
        $values = [
            'k1' => 'v1',
            'k2' => 'v3',
            'k3' => 'v3',
        ];
        $container = new Container();
        $serviceProvider = new TestServiceProvider($values);
        $container->register($serviceProvider);

        foreach ($values as $id => $value) {
            $this->assertTrue($container->has($id));
            $this->assertSame($value, $container->get($id));
        }
    }
}
