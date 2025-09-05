<?php

namespace App\Tests;

use App\Helpers\Testing;
use App\Interfaces\SingletonInterface;
use App\MChefCLI;
use App\StaticVars;

class MchefTestCase extends \PHPUnit\Framework\TestCase {
    protected function setUp(): void {
        new MChefCLI();
        StaticVars::$cli = $this->createMock(\App\MChefCLI::class);
    }

    protected function setRestrictedProperty(object $object, string $propertyName, mixed $value): void {
        Testing::setRestrictedProperty($object, $propertyName, $value);
    }

    protected function applyMockedServices(array $services, SingletonInterface $object): void {
        foreach ($services as $propName => $service) {
            $this->setRestrictedProperty($object, $propName, $service);
        }
    }
}
