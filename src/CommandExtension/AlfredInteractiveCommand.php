<?php

namespace Dpeuscher\AlfredSymfonyTools\CommandExtension;

use Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowHelper;
use Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowResult;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @category  alfred-symfony-tools
 * @copyright Copyright (c) 2018 Dominik Peuscher
 */
class AlfredInteractiveCommand extends ContainerAwareCommand implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var WorkflowHelper
     */
    protected $workflowHelper;

    /**
     * @var string[]
     */
    private $acFields = [];

    /**
     * @var string[]
     */
    private $arguments = [];

    /**
     * @var callable[]
     */
    private $argumentFunctions = [];

    /**
     * @var string[]
     */
    private $argumentFunctionsCombinations = [];

    /**
     * @var string[][]
     */
    private $acFieldsList = [];

    /**
     * @var bool
     */
    private $updatedBestMatches = false;

    /**
     * @param WorkflowHelper $workflowHelper
     */
    public function setWorkflowHelper(WorkflowHelper $workflowHelper): void
    {
        $this->workflowHelper = $workflowHelper;
    }

    public function addArgument($name, $mode = null, $description = '', $default = null, array $allowedValues = null)
    {
        $this->arguments[] = $name;
        if (isset($allowedValues)) {
            $this->addArgumentsAllowedValues($name, $allowedValues);
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
            return is_string($input->getArgument($name)) ? trim($input->getArgument($name),
                "'") : $input->getArgument($name);
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
                        if (stristr(str_replace(' ', '', $searchString), $keyCandidate) !== false) {
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
        $argument = is_string($input->getArgument($name)) ? trim($input->getArgument($name),
            "'") : $input->getArgument($name);
        if (empty($argument)) {
            return $this->acFieldsList[$name];
        }
        $matches = [];
        foreach ($this->acFieldsList[$name] as $key => $value) {
            if ($key == $argument) {
                $matches[$key] = $value;
            }
            if (strstr(str_replace(' ', '', $value), $argument) !== false) {
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

    public function addInputHandler(array $setParameters, ?callable $callable = null)
    {
        if (!isset($callable)) {
            $callable = [$this, 'genericParameterHandler'];
        }
        if (in_array($setParameters, $this->argumentFunctionsCombinations)) {
            $key = array_search($setParameters, $this->argumentFunctionsCombinations);
            $this->argumentFunctionsCombinations[$key] = $setParameters;
            $this->argumentFunctions[$key] = $callable;
        } else {
            $count = count($this->argumentFunctionsCombinations);
            $this->argumentFunctionsCombinations[$count] = $setParameters;
            $this->argumentFunctions[$count] = $callable;
        }
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

    protected function genericParameterHandler($arguments)
    {
        $dynamicArguments = null;
        $command = [];
        foreach ($arguments as $argument => $value) {
            if (substr($argument, -4, 4) == '.key') {
                continue;
            }
            if (is_array($value)) {
                if (in_array($argument, $this->acFields)) {
                    $dynamicArguments = $argument;
                }
                break;
            }
            $command[] = isset($arguments[$argument . '.key']) ? $arguments[$argument . '.key'] : $value;
        }
        $results = [];
        if (isset($argument) && isset($dynamicArguments)) {
            foreach ($arguments[$dynamicArguments] as $key => $dynamicArgument) {
                $result = new WorkflowResult();
                $result->setValid(false);
                $result->setAutocomplete(trim(implode(' ',
                        $command) . ' ' . $key));
                $result->setTitle($dynamicArgument);
                $results[] = $result;
            }
        }
        return $results;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!isset($this->workflowHelper)) {
            $this->workflowHelper = new WorkflowHelper();
        }
        $setParameters = [];
        $arguments = [];
        foreach ($this->arguments as $argument) {
            if (in_array($argument, $this->acFields)) {
                $selectedArgument = $this->getArgumentIdentifier($input, $argument);
                if ($selectedArgument) {
                    $setParameters[] = $argument;
                    $arguments[$argument . '.key'] = $selectedArgument;
                    $arguments[$argument] = current($this->getArgumentMatches($input, $argument));
                } else {
                    $arguments[$argument] = $this->getArgumentMatches($input, $argument);
                }
            } else {
                $selectedArgument = is_string($input->getArgument($argument)) ? trim($input->getArgument($argument),
                    "'") : $input->getArgument($argument);
                if ($selectedArgument) {
                    $setParameters[] = $argument;
                    $arguments[$argument] = $selectedArgument;
                }
            }
        }
        $key = array_search($setParameters, $this->argumentFunctionsCombinations);
        $callable = [$this, 'genericParameterHandler'];
        $genericResults = $callable($arguments);
        $return = $genericResults;
        if ($key !== false && isset($this->argumentFunctions[$key])) {
            $callable = $this->argumentFunctions[$key];
            $arguments['genericResults'] = $genericResults;
            $return = $callable($arguments);
        }
        if (is_array($return)) {
            foreach ($return as $result) {
                if (!$result instanceof WorkflowResult) {
                    throw new \Exception("There should only be Workflows returned");
                }
                $this->workflowHelper->applyResult($result);
            }
        } elseif (!is_null($return)) {
            throw new \Exception("There should only be Workflows returned");
        }
        $output->write($this->workflowHelper);
    }
}
