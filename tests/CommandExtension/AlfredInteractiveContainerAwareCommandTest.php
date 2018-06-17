<?php

namespace Dpeuscher\AlfredSymfonyTools\Tests\CommandExtension;

use Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowHelper;
use Dpeuscher\AlfredSymfonyTools\CommandExtension\AlfredInteractiveContainerAwareCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class AlfredInteractiveContainerAwareCommandTest
 *
 * @covers \Dpeuscher\AlfredSymfonyTools\CommandExtension\AlfredInteractiveContainerAwareCommand
 * @covers \Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowHelper
 */
class AlfredInteractiveContainerAwareCommandTest extends TestCase
{
    /**
     * @var AlfredInteractiveContainerAwareCommand
     */
    protected $sut;

    /**
     * @var \Closure
     */
    protected $runProtectedInitialize;

    /**
     * @var \Closure
     */
    protected $getWorkflowHelper;

    public function setUp()
    {
        $this->sut = new class extends AlfredInteractiveContainerAwareCommand
        {
        };
        $this->runProtectedInitialize = function (InputInterface $input, OutputInterface $output) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->initialize($input, $output);
        };
        $this->getWorkflowHelper = function () {
            /** @noinspection PhpUndefinedFieldInspection */
            return $this->workflowHelper;
        };
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

    public function testWorkflowHelperGetsInitializedIfNotAService()
    {
        $container = new Container();
        $this->sut->setContainer($container);
        $this->runProtectedInitialize->call($this->sut, new ArrayInput([]), new NullOutput());
        $this->assertInstanceOf(WorkflowHelper::class, $this->getWorkflowHelper->call($this->sut));
    }

    public function testWorkflowHelperGetsInitializedFromContainer()
    {
        $container = new Container();
        $workflowHelper = new WorkflowHelper();
        $container->set(WorkflowHelper::class, $workflowHelper);
        $this->sut->setContainer($container);
        $this->runProtectedInitialize->call($this->sut, new ArrayInput([]), new NullOutput());
        $this->assertSame($workflowHelper, $this->getWorkflowHelper->call($this->sut));
    }
}
