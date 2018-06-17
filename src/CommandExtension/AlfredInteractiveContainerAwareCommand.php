<?php

namespace Dpeuscher\AlfredSymfonyTools\CommandExtension;

use Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowHelper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @category  alfred-symfony-tools
 * @copyright Copyright (c) 2018 Dominik Peuscher
 */
abstract class AlfredInteractiveContainerAwareCommand extends AlfredInteractiveCommand
{
    /**
     * @var ContainerInterface|null
     */
    private $container;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($this->getContainer()->has(WorkflowHelper::class)) {
            $this->workflowHelper = $this->getContainer()->get(WorkflowHelper::class);
        }
        if (!isset($this->workflowHelper)) {
            $this->workflowHelper = new WorkflowHelper();
        }
    }

    /**
     * @return ContainerInterface
     * @throws \LogicException
     */
    protected function getContainer()
    {
        if (null === $this->container) {
            //@codeCoverageIgnoreStart
            $application = $this->getApplication();
            if (null === $application) {
                $application = $GLOBALS['application'] ?? null;
                if (null === $application && !$application instanceof Application) {
                    throw new \LogicException('The container cannot be retrieved as the application instance is not yet set.');
                }
            }

            /** @noinspection PhpUndefinedMethodInspection This is copy/paste from symfony code */
            $this->container = $application->getKernel()->getContainer();
            //@codeCoverageIgnoreEnd
        }

        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
