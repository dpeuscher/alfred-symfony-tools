<?php

namespace Dpeuscher\AlfredSymfonyTools\Tests\CommandExtension;

use Alfred\Workflows\Workflow;
use Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowHelper;
use Dpeuscher\AlfredSymfonyTools\CommandExtension\AlfredInteractiveCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @covers \Dpeuscher\AlfredSymfonyTools\CommandExtension\AlfredInteractiveCommand
 * @covers \Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowHelper
 * @covers \Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowResult
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
        $this->command->setWorkflowHelper(new WorkflowHelper('./', new Workflow()));
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
            'test'    => [
                'abc' => 'abc def',
                'ghi' => 'ghi',
                'jkl' => 'jkl mno pqr',
            ],
            'test2'   => [
                'ABC' => 'abc def',
                'DEF' => 'ghi',
                'GHI' => 'jkl mno pqr',
            ],
            'test3'   => [
                'abcdef1'   => 'abc def1',
                'abcghi'    => 'abc ghi',
                'abcdefghi' => 'abc def ghi',
                'jklmno'    => 'jkl mno pqr',
                'jklabc'    => 'jkl abc',
            ],
            'test4.5' => [
                'abc' => 'abc',
                'def' => 'def',
            ],
            'test5'   => [],
            'test6'   => [
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
            'test'    => [
                'abc' => 'abc def',
                'ghi' => 'ghi',
                'jkl' => 'jkl mno pqr',
            ],
            'test2'   => [
                'ABC' => 'abc def',
                'DEF' => 'ghi',
                'GHI' => 'jkl mno pqr',
            ],
            'test3'   => [
                'abcdef1'   => 'abc def1',
                'abcghi'    => 'abc ghi',
                'abcdefghi' => 'abc def ghi',
                'jklmno'    => 'jkl mno pqr',
                'jklabc'    => 'jkl abc',
            ],
            'test4.5' => [
                'abc' => 'abc',
                'def' => 'def',
            ],
            'test5'   => [],
            'test6'   => [
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

    public function testAddInputHandler()
    {
        $input = $this->setupArguments(['test' => 'abc', 'test2' => 'ABC']);

        $output = new BufferedOutput();

        $this->command->addInputHandler(['test', 'test2']);

        $this->command->setLogger(new NullLogger());
        $this->command->handleInputs($input, $output);

        $json = json_decode($output->fetch(), JSON_OBJECT_AS_ARRAY);

        $expected = [
            [
                'autocomplete' => 'abc ABC abcdef1',
                'title'        => 'abc def1',
                'valid'        => false,
            ],
            [
                'autocomplete' => 'abc ABC abcghi',
                'title'        => 'abc ghi',
                'valid'        => false,
            ],
            [
                'autocomplete' => 'abc ABC abcdefghi',
                'title'        => 'abc def ghi',
                'valid'        => false,
            ],
            [
                'autocomplete' => 'abc ABC jklmno',
                'title'        => 'jkl mno pqr',
                'valid'        => false,
            ],
            [
                'autocomplete' => 'abc ABC jklabc',
                'title'        => 'jkl abc',
                'valid'        => false,
            ],
        ];

        foreach ($json['items'] as $nr => $entry) {
            foreach ($expected[$nr] as $key => $value) {
                $this->assertEquals($value, $entry[$key]);
            }
        }
    }

    public function testAddInputHandlerWithNonAcParameter()
    {
        $input = $this->setupArguments(['test' => 'abc', 'test2' => 'DEF', 'test3' => 'jklmno', 'test4' => 'xxx']);

        $output = new BufferedOutput();

        $this->command->addInputHandler(['test', 'test2', 'test3', 'test4']);

        $this->command->setLogger(new NullLogger());
        $this->command->handleInputs($input, $output);

        $json = json_decode($output->fetch(), JSON_OBJECT_AS_ARRAY);

        $expected = [
            [
                'autocomplete' => 'abc DEF jklmno xxx abc',
                'title'        => 'abc',
                'valid'        => false,
            ],
            [
                'autocomplete' => 'abc DEF jklmno xxx def',
                'title'        => 'def',
                'valid'        => false,
            ],
        ];

        foreach ($json['items'] as $nr => $entry) {
            foreach ($expected[$nr] as $key => $value) {
                $this->assertEquals($value, $entry[$key]);
            }
        }
    }

    public function testAddInputHandlerOverrideExistingCallable()
    {
        $input = $this->setupArguments(['test' => 'abc']);

        $output = new BufferedOutput();

        $this->command->addInputHandler(['test']);
        $this->command->addInputHandler(['test', 'test2']);
        $this->command->addInputHandler(['test']);

        $this->command->setLogger(new NullLogger());
        $this->command->handleInputs($input, $output);

        $json = json_decode($output->fetch(), JSON_OBJECT_AS_ARRAY);

        $expected = [
            [
                'autocomplete' => 'abc ABC',
                'title'        => 'abc def',
                'valid'        => false,
            ],
            [
                'autocomplete' => 'abc DEF',
                'title'        => 'ghi',
                'valid'        => false,
            ],
            [
                'autocomplete' => 'abc GHI',
                'title'        => 'jkl mno pqr',
                'valid'        => false,
            ],
        ];

        foreach ($json['items'] as $nr => $entry) {
            foreach ($expected[$nr] as $key => $value) {
                $this->assertEquals($value, $entry[$key]);
            }
        }
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
        $this->command->addArgument('test4.5', InputArgument::OPTIONAL, '', null, [
            'abc',
            'def',
        ]);
        $this->command->addArgument('test5', InputArgument::OPTIONAL, '', null, []);
        $this->command->addArgument('test6', InputArgument::OPTIONAL, '', null);
        $input = new ArrayInput($values, new InputDefinition([
            new InputArgument('test'),
            new InputArgument('test2'),
            new InputArgument('test3'),
            new InputArgument('test4'),
            new InputArgument('test4.5'),
            new InputArgument('test6'),
        ]));
        $this->command->addArgumentsAllowedValues('test6', ['abc', 'def']);
        return $input;
    }
}
