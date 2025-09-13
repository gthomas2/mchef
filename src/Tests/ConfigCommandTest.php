<?php

namespace App\Tests;

use App\StaticVars;
use App\Command\Config;
use App\MChefCLI;
use App\Service\Configurator;
use splitbrain\phpcli\Options;
use App\Traits\CallRestrictedMethodTrait;

class ConfigCommandTest extends MchefTestCase {
    use CallRestrictedMethodTrait;
    private MChefCLI $cli;
    private Config $configCommand;
    private Configurator $configurator;
    private Options $options;

    protected function setUp(): void {
        parent::setUp();
        $this->cli = StaticVars::$cli;

        $this->configurator = $this->getMockBuilder(Configurator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setMainConfigField'])
            ->getMock();
        
        $this->configCommand = Config::instance();
        $this->applyMockedServices(['configuratorService' => $this->configurator], $this->configCommand);

        // Create options mock with more specific mocking
        $this->options = $this->getMockBuilder(Options::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOpt'])
            ->getMock();
    }

    public function testSetDbClient(): void {
        // Mock user input to select 'dbeaver' (option 1)
        $this->cli->expects($this->once())
            ->method('promptInput')
            ->with("Enter number (1-5): ")
            ->willReturn('1');

        // Mock the options with return map
        $this->options->method('getOpt')
            ->willReturnCallback(function($opt) {
                return match($opt) {
                    'dbclient' => true,
                    'dbclient-mysql' => false,
                    'dbclient-pgsql' => false,
                    'lang' => false,
                    'proxy' => false,
                    'password' => false,
                    default => null
                };
            });

        // Expect configurator call
        $this->configurator->expects($this->once())
            ->method('setMainConfigField')
            ->with('dbClient', 'dbeaver');

        // Expect log messages for menu and confirmation
        $this->cli->expects($this->exactly(6))
            ->method('info')
            ->withConsecutive(
                ['Select your preferred database client:', []],
                ['1) dbeaver', []],
                ['2) pgadmin', []],
                ['3) mysql workbench', []],
                ['4) psql (cli)', []],
                ['5) mysql (cli)', []]
            );

        $this->cli->expects($this->once())
            ->method('notice')
            ->with('Default database client has been set.', []);

        $this->configCommand->execute($this->options);
    }

    public function testSetDbClientPgsql(): void {
        // Mock user input to select 'pgadmin' (option 2)
        $this->cli->expects($this->once())
            ->method('promptInput')
            ->with("Enter number (1-3): ")
            ->willReturn('2');

        // Mock the options with return map
        $this->options->method('getOpt')
            ->willReturnCallback(function($opt) {
                return match($opt) {
                    'dbclient-pgsql' => true,
                    'dbclient' => false,
                    'dbclient-mysql' => false,
                    'lang' => false,
                    'proxy' => false,
                    'password' => false,
                    default => null
                };
            });

        // Expect configurator to be called
        $this->configurator->expects($this->once())
            ->method('setMainConfigField')
            ->with('dbClientPgsql', 'pgadmin');

        // Expect log messages for menu and confirmation
        $this->cli->expects($this->exactly(4))
            ->method('info')
            ->withConsecutive(
                ['Select your preferred PostgreSQL client:', []],
                ['1) dbeaver', []],
                ['2) pgadmin', []],
                ['3) psql (cli)', []]
            );

        $this->cli->expects($this->once())
            ->method('notice')
            ->with('Default PostgreSQL client has been set.', []);

        $this->configCommand->execute($this->options);
    }

    public function testSetDbClientMysql(): void {
        // Mock user input to select 'mysql workbench' (option 2) 
        $this->cli->expects($this->once())
            ->method('promptInput')
            ->with("Enter number (1-3): ")
            ->willReturn('2');

        // Mock the options with return map
        $this->options->method('getOpt')
            ->willReturnCallback(function($opt) {
                return match($opt) {
                    'dbclient-mysql' => true,
                    'dbclient' => false,
                    'dbclient-pgsql' => false,
                    'lang' => false,
                    'proxy' => false,
                    'password' => false,
                    default => null
                };
            });

        // Expect configurator to be called
        $this->configurator->expects($this->once())
            ->method('setMainConfigField')
            ->with('dbClientMysql', 'mysql workbench');

        // Expect log messages for menu and confirmation
        $this->cli->expects($this->exactly(4))
            ->method('info')
            ->withConsecutive(
                ['Select your preferred MySQL client:', []],
                ['1) dbeaver', []],
                ['2) mysql workbench', []],
                ['3) mysql (cli)', []]
            );

        $this->cli->expects($this->once())
            ->method('notice')
            ->with('Default MySQL client has been set.', []);

        $this->configCommand->execute($this->options);
    }

        $this->configCommand->execute($this->options);
    }

    public function testInvalidMysqlClientSelection(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid MySQL client option');

        $this->cli->method('promptInput')->willReturn('2');
        $this->options->method('getOpt')
            ->willReturnMap([
                ['dbclient', false],
                ['dbclient-mysql', true],
                ['dbclient-pgsql', false],
            ]);

        // Try to set 'pgadmin' as MySQL client (which is invalid)
        $this->callRestricted($this->configCommand, 'setDbClientMysql', ['pgadmin']);
    }

    public function testInvalidPgsqlClientSelection(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid PostgreSQL client option');

        $this->cli->method('promptInput')->willReturn('2');
        $this->options->method('getOpt')
            ->willReturnMap([
                ['dbclient', false],
                ['dbclient-mysql', false],
                ['dbclient-pgsql', true],
            ]);

        // Try to set 'mysql workbench' as PostgreSQL client (which is invalid)
        $this->callRestricted($this->configCommand, 'setDbClientPgsql', ['mysql workbench']);
    }

    public function testInvalidOptionNumber(): void {
        // Set up input sequence: first "99" (invalid), then "1" (valid)
        $this->cli->expects($this->exactly(2))
            ->method('promptInput')
            ->withConsecutive(
                ["Enter number (1-5): "],
                ["Enter number (1-5): "]
            )
            ->willReturnOnConsecutiveCalls('99', '1');

        // Mock the options
        $this->options->method('getOpt')
            ->willReturnCallback(function($opt) {
                return match($opt) {
                    'dbclient' => true,
                    'dbclient-mysql' => false,
                    'dbclient-pgsql' => false,
                    'lang' => false,
                    'proxy' => false,
                    'password' => false,
                    default => null
                };
            });

        // Expect configurator to be called with the first option
        $this->configurator->expects($this->once())
            ->method('setMainConfigField')
            ->with('dbClient', 'dbeaver');

        // Expect log messages for menu, error, and second menu display
        $this->cli->expects($this->exactly(11))
            ->method('info')
            ->withConsecutive(
                ['Select your preferred database client:', []],
                ['1) dbeaver', []],
                ['2) pgadmin', []],
                ['3) mysql workbench', []],
                ['4) psql (cli)', []],
                ['5) mysql (cli)', []],
                ['Select your preferred database client:', []],
                ['1) dbeaver', []],
                ['2) pgadmin', []],
                ['3) mysql workbench', []],
                ['4) psql (cli)', []],
                ['5) mysql (cli)', []]
            );

        $this->cli->expects($this->once())
            ->method('error')
            ->with('Invalid selection. Please try again.', []);

        $this->cli->expects($this->once())
            ->method('notice')
            ->with('Default database client has been set.', []);

        $this->configCommand->execute($this->options);
    }
}
