<?php

namespace Dpeuscher\AlfredSymfonyTools\CommandExtension;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @category  alfred-symfony-tools
 * @copyright Copyright (c) 2018 Dominik Peuscher
 */
class AlfredInteractiveContainerAwareCommand extends AlfredInteractiveCommand
{
    /**
     * @var ContainerInterface|null
     */
    private $container;

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
                throw new \LogicException('The container cannot be retrieved as the application instance is not yet set.');
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
