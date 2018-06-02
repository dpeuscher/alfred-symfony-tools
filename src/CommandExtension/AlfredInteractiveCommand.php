<?php

namespace Dpeuscher\AlfredSymfonyTools\CommandExtension;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @category  alfred-symfony-tools
 * @copyright Copyright (c) 2018 Dominik Peuscher
 */
class AlfredInteractiveCommand extends ContainerAwareCommand implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string[]
     */
    private $acFields = [];

    /**
     * @var string[][]
     */
    private $acFieldsList = [];

    /**
     * @var bool
     */
    private $updatedBestMatches = false;

    public function addArgument($name, $mode = null, $description = '', $default = null, array $allowedValues = null)
    {
        if (isset($allowedValues)) {
            $this->addArgumentsAllowedValues($name,$allowedValues);
        }
        return parent::addArgument($name, $mode, $description, $default);
    }

    public function addArgumentsAllowedValues($name, array $allowedValues)
    {
        $this->acFields[] = $name;
        $this->acFieldsList[$name] = $allowedValues;
    }

    public function getArgumentIdentifier(InputInterface $input, string $name)
    {
        if (!in_array($name, $this->acFields)) {
            return $input->getArgument($name);
        }
        if (empty($this->acFieldsList[$name])) {
            $this->log(LogLevel::NOTICE, 'There are no possible values for argument ' . $name . ' configured');
            return null;
        }
        $matches = $this->getArgumentMatches($input, $name);
        if (count($matches) == 1) {
            return key($matches);
        }

        return null;
    }

    public function updateBestMatchKeys()
    {
        if ($this->updatedBestMatches) {
            return;
        }
        foreach ($this->acFieldsList as $argumentName => $fields) {
            $bestMatchKeyList = [];
            foreach ($fields as $key => $value) {
                if (!is_int($key)) {
                    continue 2;
                }
                $words = explode(' ', $value);
                $keyCandidate = $key;

                for ($i = 0; $i < count($words); $i++) {
                    $keyCandidate = implode(array_slice($words, 0, $i + 1));
                    foreach ($fields as $searchString) {
                        if ($searchString === $value) {
                            continue;
                        }
                        if (stristr(str_replace(' ', '', $searchString),$keyCandidate) !== false) {
                            continue 2;
                        }
                    }
                    break;
                }
                $bestMatchKeyList[$keyCandidate] = $value;
            }
            $this->acFieldsList[$argumentName] = $bestMatchKeyList;
        }
        $this->updatedBestMatches = true;
    }

    public function getArgumentMatches(InputInterface $input, string $name)
    {
        $this->updateBestMatchKeys();
        if (!in_array($name, $this->acFields)) {
            return [];
        }
        if (empty($this->acFieldsList[$name])) {
            $this->log(LogLevel::NOTICE, 'There are no possible values for argument ' . $name . ' configured');
            return [];
        }
        $argument = $input->getArgument($name);
        if (empty($argument)) {
            return $this->acFieldsList[$name];
        }
        $matches = [];
        foreach ($this->acFieldsList[$name] as $key => $value) {
            if (stristr(str_replace(' ', '', $value), $argument) !== false) {
                $matches[$key] = $value;
            }
        }
        return $matches;
    }

    public function getSelectedArgument(InputInterface $input, string $name)
    {
        $key = $this->getArgumentIdentifier($input, $name);
        if (isset($key)) {
            return $this->acFieldsList[$name][$key];
        }
        return null;
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     */
    protected function log(string $level, string $message, array $context = [])
    {
        if (isset($this->logger)) {
            $this->logger->log($level, $message, $context);
        } else {
            //@codeCoverageIgnoreStart
            trigger_error($message, [
                LogLevel::EMERGENCY => E_USER_ERROR,
                LogLevel::ALERT     => E_USER_ERROR,
                LogLevel::CRITICAL  => E_USER_ERROR,
                LogLevel::ERROR     => E_USER_ERROR,
                LogLevel::WARNING   => E_USER_WARNING,
                LogLevel::NOTICE    => E_USER_NOTICE,
                LogLevel::INFO      => E_USER_NOTICE,
                LogLevel::DEBUG     => E_USER_NOTICE,
            ][$level]);
            //@codeCoverageIgnoreEnd
        }
    }
}
