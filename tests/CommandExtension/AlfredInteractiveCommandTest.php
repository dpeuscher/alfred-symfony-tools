<?php

namespace Dpeuscher\AlfredSymfonyTools\Tests\CommandExtension;

use Dpeuscher\AlfredSymfonyTools\CommandExtension\AlfredInteractiveCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;

/**
 * @covers \Dpeuscher\AlfredSymfonyTools\CommandExtension\AlfredInteractiveCommand
 */
class AlfredInteractiveCommandTest extends TestCase
{
    /**
     * @var AlfredInteractiveCommand
     */
    protected $command;

    /**
     * @var \Closure
     */
    protected $getPrivateFields;

    public function setup()
    {
        $this->command = new AlfredInteractiveCommand();
        $this->getPrivateFields = function ($field) {
            return $this->$field;
        };
    }

    public function testGetSelectedArgument()
    {
        $input = $this->setupArguments(['test' => 'abcd']);

        $this->assertEquals('abc def', $this->command->getSelectedArgument($input, 'test'));
    }

    public function testGetSelectedArgumentOfNotAcArgumentReturnsNull()
    {
        $input = $this->setupArguments(['test' => 'abcd']);

        $this->assertEquals(null, $this->command->getSelectedArgument($input, 'test4'));
    }

    public function testGetSelectedArgumentOfEmptyWhitelistReturnsNull()
    {
        $input = $this->setupArguments(['test' => 'abcd']);

        $this->command->setLogger(new NullLogger());

        $this->assertEquals(null, $this->command->getSelectedArgument($input, 'test5'));
    }

    public function testGetSelectedArgumentOfEmptyWhitelistLogs()
    {
        $input = $this->setupArguments(['test' => 'abcd']);

        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->once())->method('log');
        $this->command->setLogger($logger);

        $this->assertEquals(null, $this->command->getSelectedArgument($input, 'test5'));
    }

    public function testUpdateBestMatchKeys()
    {
        $this->setupArguments([]);

        $this->command->updateBestMatchKeys();

        $expected = [
            'test'  => [
                'abc' => 'abc def',
                'ghi' => 'ghi',
                'jkl' => 'jkl mno pqr',
            ],
            'test2' => [
                'ABC' => 'abc def',
                'DEF' => 'ghi',
                'GHI' => 'jkl mno pqr',
            ],
            'test3' => [
                'abcdef1'   => 'abc def1',
                'abcghi'    => 'abc ghi',
                'abcdefghi' => 'abc def ghi',
                'jklmno'    => 'jkl mno pqr',
                'jklabc'    => 'jkl abc',
            ],
            'test5' => [],
            'test6' => [
                'abc' => 'abc',
                'def' => 'def',
            ],
        ];

        $this->assertEquals($expected, $this->getPrivateFields->call($this->command, 'acFieldsList'));
    }

    public function testUpdateBestMatchKeysCanRunTwice()
    {
        $this->setupArguments([]);

        $this->command->updateBestMatchKeys();
        $this->command->updateBestMatchKeys();

        $expected = [
            'test'  => [
                'abc' => 'abc def',
                'ghi' => 'ghi',
                'jkl' => 'jkl mno pqr',
            ],
            'test2' => [
                'ABC' => 'abc def',
                'DEF' => 'ghi',
                'GHI' => 'jkl mno pqr',
            ],
            'test3' => [
                'abcdef1'   => 'abc def1',
                'abcghi'    => 'abc ghi',
                'abcdefghi' => 'abc def ghi',
                'jklmno'    => 'jkl mno pqr',
                'jklabc'    => 'jkl abc',
            ],
            'test5' => [],
            'test6' => [
                'abc' => 'abc',
                'def' => 'def',
            ],
        ];

        $this->assertEquals($expected, $this->getPrivateFields->call($this->command, 'acFieldsList'));
    }

    public function testGetArgumentMatches()
    {
        $input = $this->setupArguments(['test3' => 'abc']);

        $this->assertEquals([
            'abcdef1'   => 'abc def1',
            'abcghi'    => 'abc ghi',
            'abcdefghi' => 'abc def ghi',
            'jklabc'    => 'jkl abc',
        ], $this->command->getArgumentMatches($input, 'test3'));
    }

    public function testGetArgumentMatchesOfNotAcArgumentReturnsNull()
    {
        $input = $this->setupArguments(['test' => 'abcd']);

        $this->assertEquals([], $this->command->getArgumentMatches($input, 'test4'));
    }

    public function testGetArgumentMatchesOfEmptyWhitelistReturnsNull()
    {
        $input = $this->setupArguments(['test' => 'abcd']);

        $this->command->setLogger(new NullLogger());

        $this->assertEquals([], $this->command->getArgumentMatches($input, 'test5'));
    }

    public function testGetArgumentIdentifier()
    {
        $input = $this->setupArguments(['test' => 'pqr']);

        $this->assertEquals('jkl', $this->command->getArgumentIdentifier($input, 'test'));
    }

    public function testGetArgumentIdentifierWithMoreMatchesReturnsNull()
    {
        $input = $this->setupArguments(['test3' => 'abc']);

        $this->assertEquals(null, $this->command->getArgumentIdentifier($input, 'test'));
    }

    public function testAddArgument()
    {
        $this->command->addArgument('test', InputArgument::OPTIONAL, '', null, [
            'abc def',
            'ghi',
            'jkl mno pqr',
        ]);

        $this->assertNotEmpty($this->getPrivateFields->call($this->command, 'acFieldsList'),
            'acFieldsList was not updated');
    }

    private function setupArguments($values): ArrayInput
    {
        $this->command->addArgument('test', InputArgument::OPTIONAL, '', null, [
            'abc def',
            'ghi',
            'jkl mno pqr',
        ]);
        $this->command->addArgument('test2', InputArgument::OPTIONAL, '', null, [
            'ABC' => 'abc def',
            'DEF' => 'ghi',
            'GHI' => 'jkl mno pqr',
        ]);
        $this->command->addArgument('test3', InputArgument::OPTIONAL, '', null, [
            'abc def1',
            'abc ghi',
            'abc def ghi',
            'jkl mno pqr',
            'jkl abc',
        ]);
        $this->command->addArgument('test4', InputArgument::OPTIONAL, '', null);
        $this->command->addArgument('test5', InputArgument::OPTIONAL, '', null, []);
        $this->command->addArgument('test6', InputArgument::OPTIONAL, '', null);
        $input = new ArrayInput($values, new InputDefinition([
            new InputArgument('test'),
            new InputArgument('test2'),
            new InputArgument('test3'),
            new InputArgument('test4'),
            new InputArgument('test6'),
        ]));
        $this->command->addArgumentsAllowedValues('test6', ['abc', 'def']);
        return $input;
    }
}
