<?php

namespace App\Tests;

use App\Model\RegistryInstance;
use App\Service\Configurator;
use App\Service\ProxyService;
use PHPUnit\Framework\TestCase;

class ProxyModeTest extends TestCase {

    public function testRegistryInstanceWithProxyPort(): void {
        $instance = new RegistryInstance('test-uuid', '/test/path', 'test-prefix', 8100);
        
        $this->assertEquals('test-uuid', $instance->uuid);
        $this->assertEquals('/test/path', $instance->recipePath);
        $this->assertEquals('test-prefix', $instance->containerPrefix);
        $this->assertEquals(8100, $instance->proxyModePort);
    }

    public function testRegistryInstanceWithoutProxyPort(): void {
        $instance = new RegistryInstance('test-uuid', '/test/path', 'test-prefix');
        
        $this->assertEquals('test-uuid', $instance->uuid);
        $this->assertEquals('/test/path', $instance->recipePath);
        $this->assertEquals('test-prefix', $instance->containerPrefix);
        $this->assertNull($instance->proxyModePort);
    }

    public function testProxyServiceIsProxyModeEnabled(): void {
        $proxyService = ProxyService::instance();
        
        // This test depends on the current global config
        // We're just testing that the method doesn't throw an error
        $result = $proxyService->isProxyModeEnabled();
        $this->assertIsBool($result);
    }

    public function testProxyServiceConfigPath(): void {
        $proxyService = ProxyService::instance();
        $configPath = $proxyService->getProxyConfigPath();
        
        $this->assertIsString($configPath);
        $this->assertStringContainsString('proxy.conf', $configPath);
    }

    public function testProxyContainerConstants(): void {
        $this->assertEquals('mchef-proxy', ProxyService::PROXY_CONTAINER_NAME);
        $this->assertEquals(80, ProxyService::PROXY_PORT);
    }
}
