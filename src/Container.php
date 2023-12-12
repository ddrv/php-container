<?php

namespace Ddrv\Container;

use Ddrv\Container\Exception\ContainerException;
use Ddrv\Container\Exception\NotFoundException;
use Exception;
use Psr\Container\ContainerInterface;

/**
 * @psalm-type ServiceFactory = callable(ContainerInterface):mixed
 */
final class Container implements ContainerInterface
{
    /**
     * @var array<string, ServiceFactory>
     */
    private array $factories = [];

    /**
     * @var array<string, mixed>
     */
    private array $services = [];

    /**
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * @var array<string, bool>
     */
    private array $instances = [];

    /**
     * @var array<int, ContainerInterface>
     */
    private array $containers = [];

    /**
     * @inheritDoc
     * @return mixed
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException($id);
        }
        if (array_key_exists($id, $this->aliases)) {
            return $this->get($this->aliases[$id]);
        }
        if (array_key_exists($id, $this->services)) {
            return $this->services[$id];
        }
        if (array_key_exists($id, $this->factories)) {
            $factory = $this->factories[$id];
            try {
                /** @var mixed $service */
                $service = $factory($this);
            } catch (Exception $exception) {
                throw ContainerException::factoryError($id, $exception);
            }
            if (!array_key_exists($id, $this->instances)) {
                $this->services[$id] = $service;
            }
            return $service;
        }
        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return $container->get($id);
            }
        }

        return $this->services[$id] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        if (array_key_exists($id, $this->aliases)) {
            return $this->has($this->aliases[$id]);
        }
        if (array_key_exists($id, $this->services)) {
            return true;
        }
        if (array_key_exists($id, $this->factories)) {
            return true;
        }
        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $id
     * @param mixed $value
     * @return void
     */
    public function value(string $id, $value): void
    {
        $this->resetId($id);
        $this->services[$id] = $value;
    }

    /**
     * @param string $id
     * @param ServiceFactory $factory
     * @return void
     */
    public function service(string $id, callable $factory): void
    {
        $this->resetId($id);
        $this->factories[$id] = $factory;
    }

    /**
     * @param string $id
     * @param ServiceFactory $factory
     * @return void
     */
    public function instance(string $id, callable $factory): void
    {
        $this->resetId($id);
        $this->factories[$id] = $factory;
        $this->instances[$id] = true;
    }

    /**
     * @param string $alias
     * @param string $service
     * @return void
     */
    public function bind(string $alias, string $service): void
    {
        $path = [$alias];
        $to = $service;
        do {
            $path[] = $to;
            if ($alias === $to) {
                throw ContainerException::recursiveBinding($alias, $service, implode(' -> ', $path));
            }
            $to = array_key_exists($to, $this->aliases) ? $this->aliases[$to] : null;
        } while ($to);
        $this->resetId($alias);
        $this->aliases[$alias] = $service;
    }

    /**
     * @param ServiceProviderInterface $serviceProvider
     * @return void
     */
    public function register(ServiceProviderInterface $serviceProvider): void
    {
        $serviceProvider->register($this);
    }

    /**
     * @param ContainerInterface $container
     * @return void
     */
    public function delegate(ContainerInterface $container): void
    {
        if ($container === $this) {
            return;
        }
        if (in_array($this, $this->containers, true)) {
            return;
        }
        $this->containers[] = $container;
    }

    /**
     * @param string $id
     * @return void
     */
    private function resetId(string $id): void
    {
        if (array_key_exists($id, $this->services)) {
            unset($this->services[$id]);
        }
        if (array_key_exists($id, $this->instances)) {
            unset($this->instances[$id]);
        }
        if (array_key_exists($id, $this->aliases)) {
            unset($this->aliases[$id]);
        }
        if (array_key_exists($id, $this->factories)) {
            unset($this->factories[$id]);
        }
    }
}
