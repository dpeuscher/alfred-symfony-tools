<?php

namespace Dpeuscher\AlfredSymfonyTools\Tests\CommandExtension;

use Dpeuscher\AlfredSymfonyTools\CommandExtension\AlfredInteractiveContainerAwareCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class AlfredInteractiveContainerAwareCommandTest
 *
 * @covers \Dpeuscher\AlfredSymfonyTools\CommandExtension\AlfredInteractiveContainerAwareCommand
 */
class AlfredInteractiveContainerAwareCommandTest extends TestCase
{
    /**
     * @var AlfredInteractiveContainerAwareCommand
     */
    protected $sut;

    public function setUp()
    {
        $this->sut = new AlfredInteractiveContainerAwareCommand();
    }

    public function testSetContainer()
    {
        $container = new Container();
        $this->sut->setContainer($container);
        $closure = function () {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getContainer();
        };
        $this->assertSame($container, $closure->call($this->sut));
    }
}
