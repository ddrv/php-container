<?php

namespace Ddrv\Container;

interface ServiceProviderInterface
{
    /**
     * @param Container $container
     * @return void
     */
    public function register(Container $container): void;
}
