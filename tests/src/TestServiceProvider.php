<?php

declare(strict_types=1);

namespace TestTools\Ddrv\Container;

use Ddrv\Container\Container;
use Ddrv\Container\ServiceProviderInterface;

final class TestServiceProvider implements ServiceProviderInterface
{
    /**
     * @var non-empty-array<string, string> $values
     */
    private array $values;

    /**
     * @param non-empty-array<string, string> $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function register(Container $container): void
    {
        foreach ($this->values as $id => $value) {
            $container->value($id, $value);
        }
    }
}
