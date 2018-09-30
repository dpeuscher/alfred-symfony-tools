<?php

namespace Dpeuscher\AlfredSymfonyTools\CommandExtension;

use Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowHelper;
use Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowResult;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @category  alfred-symfony-tools
 * @copyright Copyright (c) 2018 Dominik Peuscher
 */
abstract class AlfredInteractiveCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var WorkflowHelper
     */
    protected $workflowHelper;

    /**
     * @var string[]
     */
    protected $acFields = [];

    /**
     * @var string[][]
     */
    protected $acFieldsList = [];

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
    private $acFieldAllowNew = [];

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

    public function addArgument(
        $name,
        $mode = null,
        $description = '',
        $default = null,
        array $allowedValues = null,
        bool $allowNew = false
    ) {
        $this->arguments[] = $name;
        if (isset($allowedValues)) {
            $this->addArgumentsAllowedValues($name, $allowedValues, $allowNew);
        }
        return parent::addArgument($name, $mode, $description, $default);
    }

    public function addArgumentsAllowedValues($name, array $allowedValues, $allowNew = false)
    {
        $this->acFields[] = $name;
        $this->acFieldsList[$name] = $allowedValues;
        $this->acFieldAllowNew[$name] = $allowNew;
    }

    public function getArgumentIdentifier(InputInterface $input, string $name)
    {
        if (!\in_array($name, $this->acFields)) {
            return \is_string($input->getArgument($name)) ? trim(/** @scrutinizer ignore-type */
                $input->getArgument($name), "'") : $input->getArgument($name);
        }
        if (empty($this->acFieldsList[$name]) && !$this->acFieldAllowNew[$name]) {
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
        if (empty($this->acFieldsList[$name]) && !$this->acFieldAllowNew[$name]) {
            $this->log(LogLevel::NOTICE, 'There are no possible values for argument ' . $name . ' configured');
            return [];
        }
        $argument = \is_string($input->getArgument($name)) ? trim(/** @scrutinizer ignore-type */
            $input->getArgument($name),
            "'") : $input->getArgument($name);
        if ($argument === null || $argument === '') {
            return $this->acFieldsList[$name];
        }
        $matches = [];
        foreach ($this->acFieldsList[$name] as $key => $value) {
            if ($key == $argument) {
                return [$key => $value];
            }
            if (!$this->acFieldAllowNew[$name] && false !== strpos(str_replace(' ', '', $value), $argument)) {
                $matches[$key] = $value;
            }
        }
        if ($this->acFieldAllowNew[$name]) {
            $matches[$argument] = $argument;
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
        if ($callable === null) {
            $callable = [$this, 'genericParameterHandler'];
        }
        if (\in_array($setParameters, $this->argumentFunctionsCombinations, true)) {
            $key = array_search($setParameters, $this->argumentFunctionsCombinations, true);
            $this->argumentFunctionsCombinations[$key] = $setParameters;
            $this->argumentFunctions[$key] = $callable;
        } else {
            $count = \count($this->argumentFunctionsCombinations);
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
        if ($this->logger !== null) {
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
        [$dynamicArguments, $command] = $this->buildCommandFromArguments($arguments);
        $results = [];
        if ($dynamicArguments !== null) {
            foreach ($arguments[$dynamicArguments] as $key => $dynamicArgument) {
                $result = new WorkflowResult();
                $result->setValid(false);
                $result->setAutocomplete(trim(implode(' ', $command) . ' ' . $key));
                $result->setArg(json_encode($command + [$dynamicArguments => (string)$key]));
                $result->setTitle($dynamicArgument);
                $results[] = $result;
            }
        } else {
            $result = new WorkflowResult();
            $result->setValid(false);
            $result->setAutocomplete(trim(implode(' ', $command)));
            $result->setArg(json_encode($command));
            /** @noinspection PhpStatementHasEmptyBodyInspection */
            foreach ($command as $key => $value) {
            }
            if (isset($value)) {
                $result->setTitle($value);
            }
            $results[] = $result;
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
        if ($this->workflowHelper === null) {
            $this->workflowHelper = new WorkflowHelper();
        }
        $setParameters = [];
        $arguments = [];
        foreach ($this->arguments as $argument) {
            if (\in_array($argument, $this->acFields)) {
                $selectedArgument = $this->getArgumentIdentifier($input, $argument);
                if ($selectedArgument !== null) {
                    $setParameters[] = $argument;
                    $arguments[$argument . '.key'] = $selectedArgument;
                    $arguments[$argument] = current($this->getArgumentMatches($input, $argument));
                } else {
                    $arguments[$argument] = $this->getArgumentMatches($input, $argument);
                }
            } else {
                $selectedArgument = \is_string($input->getArgument($argument)) ? trim(/** @scrutinizer ignore-type */
                    $input->getArgument($argument), "'") : (\is_array($input->getArgument($argument)) ?
                    implode(' ', $input->getArgument($argument)) : $input->getArgument($argument));
                if ($selectedArgument !== null && $selectedArgument !== '') {
                    $setParameters[] = $argument;
                    $arguments[$argument] = $selectedArgument;
                }
            }
        }
        $key = array_search($setParameters, $this->argumentFunctionsCombinations, true);
        $callable = [$this, 'genericParameterHandler'];
        $genericResults = $callable($arguments);
        $return = $genericResults;
        if ($key !== false && isset($this->argumentFunctions[$key])) {
            $callable = $this->argumentFunctions[$key];
            $arguments['genericResults'] = $genericResults;
            $return = $callable($arguments);
        }
        if (\is_array($return)) {
            foreach ($return as $result) {
                if (!$result instanceof WorkflowResult) {
                    throw new \RuntimeException('There should only be Workflows returned');
                }
                $this->workflowHelper->applyResult($result);
            }
        } elseif ($return !== null) {
            throw new \RuntimeException('There should only be Workflows returned');
        }
        $output->write($this->workflowHelper);
    }

    /**
     * @param $arguments
     * @return array
     */
    protected function buildCommandFromArguments($arguments): array
    {
        $dynamicArguments = null;
        $command = [];
        foreach ($arguments as $argument => $value) {
            if (substr($argument, -4, 4) === '.key') {
                continue;
            }
            if (\is_array($value)) {
                if (\in_array($argument, $this->acFields, true)) {
                    $dynamicArguments = $argument;
                }
                break;
            }
            $command[$argument] = $arguments[$argument . '.key'] ?? $value;
        }
        return [$dynamicArguments, $command];
    }
}
