<?php

namespace Dpeuscher\AlfredSymfonyTools\Command;

use Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowResult;
use Dpeuscher\AlfredSymfonyTools\CommandExtension\AlfredInteractiveContainerAwareCommand;
use makbari\DotEnvEditor\handler\Handler;
use makbari\DotEnvEditor\services\Config;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

/**
 * @category  alfred-symfony-tools
 * @copyright Copyright (c) 2018 Dominik Peuscher
 */
class ConfigureCommand extends AlfredInteractiveContainerAwareCommand
{
    const CONFIG_CONTAINER_PARAMETER = 'configValues';
    const CONFIG_DOTENV_FILE = 'configDotEnvFileConfiguration';

    /**
     * @var string
     */
    protected $dotEnvConfigurationFile;

    /**
     * @var Handler
     */
    protected $envEditor;

    /**
     * @var array
     */
    protected $parameterTypes = [];

    protected function configure()
    {
        $this->setName('config')
            ->addOption('execute', 'x')
            ->addArgument('optionName', InputArgument::OPTIONAL)
            ->addArgument('operation', InputArgument::OPTIONAL)
            ->addArgument('key', InputArgument::OPTIONAL)
            ->addArgument('value', InputArgument::OPTIONAL + InputArgument::IS_ARRAY);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getContainer()->getParameter(self::CONFIG_CONTAINER_PARAMETER);
        foreach ($config as $item) {
            if (preg_match('/(.+)\[\]/', $item, $matches)) {
                $optionName = $matches[1];
                $this->parameterTypes[$optionName] = 'array';
            } else {
                $optionName = $item;
                $this->parameterTypes[$optionName] = 'string';
            }
        }

        try {
            $this->dotEnvConfigurationFile = $this->getContainer()->getParameter(self::CONFIG_DOTENV_FILE);
        } catch (ParameterNotFoundException|InvalidArgumentException $exception) {
            $this->dotEnvConfigurationFile = $this->getContainer()->getParameter('kernel.project_dir') . '/config/dotenveditor.php';
        }

        $config = new Config($this->dotEnvConfigurationFile);
        $envFile = $config->get('dotenveditor.pathToEnv');
        if (!file_exists($envFile)) {
            touch($envFile);
        }

        $this->envEditor = new Handler($this->dotEnvConfigurationFile);

        $this->buildAllowedArguments($input);

        $this->addInputHandler(['optionName'], [$this, 'handleOperationShow']);
        if ($input->getOption('execute')) {
            $this->addInputHandler(['optionName', 'operation'], [$this, 'handleExecution']);
            $this->addInputHandler(['optionName', 'operation', 'key'], [$this, 'handleExecution']);
            $this->addInputHandler(['optionName', 'operation', 'key', 'value'], [$this, 'handleExecution']);
        } else {
            $this->addInputHandler(['optionName', 'operation'], [$this, 'handleOptionOperation']);
            $this->addInputHandler(['optionName', 'operation', 'key'], [$this, 'handleOptionOperation']);
            $this->addInputHandler(['optionName', 'operation', 'key', 'value'], [$this, 'handleOptionOperation']);
        }
    }

    /**
     * @param InputInterface $input
     * @throws \Exception
     */
    protected function buildAllowedArguments(InputInterface $input): void
    {
        $this->addArgumentsAllowedValues('optionName', array_keys($this->parameterTypes));

        $selectedOption = $this->getSelectedArgument($input, 'optionName');
        if ($selectedOption) {
            switch ($this->parameterTypes[$selectedOption]) {
                case 'array':
                    $this->addArgumentsAllowedValues('operation',
                        ['set' => 'set', 'remove' => 'remove', 'unset' => 'unset']);
                    $selectedOperation = $this->getSelectedArgument($input, 'operation');
                    if ($selectedOperation) {
                        $vars = $this->envEditor->overview()['values'][$selectedOption] ?? json_encode([]);
                        $vars = str_replace(['\"', '\\\\'], ['"', '\\'], (trim($vars, '"')));
                        $vars = json_decode($vars, true);
                        $keys = [];
                        foreach ($vars as $key => $value) {
                            $keys[(string)$key] = (string)$key;
                        }
                        switch ($selectedOperation) {
                            case 'set':
                                $selectedKey = $input->getArgument('key');
                                if (!is_null($selectedKey) && !isset($keys[$selectedKey])) {
                                    $keys = [(string)$selectedKey => (string)$selectedKey] + $keys;
                                }
                                $this->addArgumentsAllowedValues('key', $keys, true);
                                break;
                            case 'remove':
                                $this->addArgumentsAllowedValues('key', $keys, false);
                                break;
                            case 'unset':
                                break;
                        }
                    }
                    break;
                case 'string':
                    $this->addArgumentsAllowedValues('operation', ['set' => 'set', 'unset' => 'unset']);
                    break;
            }
        }
    }

    /**
     * @param array $arguments
     * @return array
     * @throws \Exception
     */
    protected function handleOperationShow($arguments): array
    {
        /** @var WorkflowResult $result */
        foreach ($arguments['genericResults'] as $result) {
            $command = json_decode($result->getArg(), true);
            if ($this->parameterTypes[$command['optionName']] == 'string') {
                $result->setTitle(ucwords($command['operation']) . ' ' . $command['optionName']);
            } elseif ($this->parameterTypes[$command['optionName']] == 'array') {
                switch ($command['operation']) {
                    case 'set':
                        $result->setTitle('Set key for ' . $command['optionName'] . '[]');
                        break;
                    case 'remove':
                        $result->setTitle('Remove key from ' . $command['optionName'] . '[]');
                        break;
                    case 'unset':
                        $result->setTitle('Unset ' . $command['optionName'] . '[]');
                        break;
                }
            }
        }
        return $arguments['genericResults'];
    }

    /**
     * @param array $arguments
     * @return array
     * @throws \Exception
     */
    protected function handleExecution($arguments): array
    {
        switch ($this->parameterTypes[$arguments['optionName']]) {
            case 'array':
                $vars = $this->envEditor->overview()['values'][$arguments['optionName']] ?? json_encode([]);
                $vars = str_replace(['\"', '\\\\'], ['"', '\\'], (trim($vars, '"')));
                switch ($arguments['operation']) {
                    case 'set':
                        if (is_string($arguments['key'])) {
                            $vars = json_decode($vars, true);
                            if (isset($arguments['value'])) {
                                $vars[$arguments['key']] = $arguments['value'];
                            } else {
                                $vars[$arguments['key']] = '';
                            }
                            if (isset($this->envEditor->overview()['values'][$arguments['optionName']])) {
                                $this->envEditor->update([
                                    $arguments['optionName'] => addcslashes(json_encode($vars), '"\\'),
                                ]);
                            } else {
                                $this->envEditor->add([
                                    $arguments['optionName'] => addcslashes(json_encode($vars), '"\\'),
                                ]);
                            }
                        } else {
                            return $this->handleOptionOperation($arguments);
                        }
                        break;
                    case 'remove':
                        if (is_string($arguments['key'])) {
                            $vars = json_decode(trim($vars,
                                '"'), true);
                            if (isset($vars[$arguments['key']])) {
                                unset($vars[$arguments['key']]);
                            }
                            if (isset($this->envEditor->overview()['values'][$arguments['optionName']])) {
                                $this->envEditor->update([
                                    $arguments['optionName'] => addcslashes(json_encode($vars), '"\\'),
                                ]);
                            }
                        } else {
                            return $this->handleOptionOperation($arguments);
                        }
                        break;
                    case 'unset':
                        $this->envEditor->delete([$arguments['optionName']]);
                        break;
                }
                break;
            case 'string':
                switch ($arguments['operation']) {
                    case 'set':
                        $value = '';
                        if (isset($arguments['key'])) {
                            $value .= $arguments['key'];
                            if (isset($arguments['value'])) {
                                $value .= ' ' . $arguments['value'];
                            }
                        }
                        if (isset($this->envEditor->overview()['values'][$arguments['optionName']])) {
                            $this->envEditor->update([$arguments['optionName'] => $value]);
                        } else {
                            $this->envEditor->add([$arguments['optionName'] => $value]);
                        }
                        break;
                    case 'unset':
                        $this->envEditor->delete([$arguments['optionName']]);
                        break;
                }
                break;
        }
        return [];
    }

    /**
     * @param array $arguments
     * @return array
     * @throws \Exception
     */
    protected function handleOptionOperation($arguments): array
    {
        switch ($this->parameterTypes[$arguments['optionName']]) {
            case 'array':
                $arguments = $this->handleOperationsForArrayOption($arguments);
                return $arguments['genericResults'];
            case 'string':
                $return = $this->handleOperationsForScalarOption($arguments);
                return $return;
        }
        return []; //@codeCoverageIgnore
    }

    /**
     * @param array $arguments
     * @return array
     */
    protected function handleOperationsForArrayOption(array $arguments): array
    {
        $vars = $this->envEditor->overview()['values'][$arguments['optionName']] ?? json_encode([]);
        $vars = str_replace(['\"', '\\\\'], ['"', '\\'], (trim($vars, '"')));
        $vars = json_decode($vars, true);
        switch ($arguments['operation']) {
            case 'set':
                /** @var WorkflowResult $result */
                foreach ($arguments['genericResults'] as $result) {
                    $command = json_decode($result->getArg(), true);
                    $result->setTitle($arguments['optionName'] . '[' . $command['key'] . '] = "' . (isset($arguments['value']) ? $arguments['value'] : '') . '"');
                    $result->setSubtitle('Set ' . $command['key'] . ' for Parameter ' . $arguments['optionName'] . ' from "' . ($vars[$command['key']] ?? '<null>') . '" to "' . (isset($arguments['value']) ? $arguments['value'] : '') . '"');
                    $result->setArg('-x ' . implode(' ', $command));
                    $result->setValid(is_string($arguments['key']));
                }
                break;
            case 'remove':
                /** @var WorkflowResult $result */
                foreach ($arguments['genericResults'] as $result) {
                    $command = json_decode($result->getArg(), true);
                    $result->setTitle('Remove ' . $arguments['optionName'] . '[' . $command['key'] . ']');
                    $result->setSubtitle('Remove ' . $command['key'] . ' for Parameter ' . $arguments['optionName'] . ' with "' . $vars[$command['key']] . '"');
                    $result->setArg('-x ' . implode(' ', $command));
                    $result->setValid(is_string($arguments['key']));
                }
                break;
            case 'unset':
                $result = new WorkflowResult();
                $result->setTitle("Unset " . $arguments['optionName'] . '[]');
                $result->setSubtitle("Unset " . $arguments['optionName'] . ' with "' . json_encode($vars) . '"');
                $result->setValid(true);
                $result->setArg(implode(' ', $this->buildCommandFromArguments($arguments)[1]));
                $result->setAutocomplete(implode(' ', $this->buildCommandFromArguments($arguments)[1]));
                $arguments['genericResults'] = [$result];
        }
        return $arguments;
    }

    /**
     * @param $arguments
     * @return array
     */
    protected function handleOperationsForScalarOption($arguments): array
    {
        $return = [];
        $vars = $this->envEditor->overview()['values'];
        switch ($arguments['operation']) {
            case 'set':
                $result = new WorkflowResult();
                $result->setTitle($arguments['optionName'] . ' = "' . trim(($arguments['key'] ?? '') . ' ' . (isset($arguments['value']) ? $arguments['value'] : '')) . '"');
                $result->setSubtitle("Set " . $arguments['optionName'] . ' from "' . ($vars[$arguments['optionName']] ?? '<null>') . '" to "' . trim(($arguments['key'] ?? '') . ' ' . (isset($arguments['value']) ? $arguments['value'] : '')) . '"');
                $result->setValid(true);
                $result->setArg('-x ' . implode(' ', $this->buildCommandFromArguments($arguments)[1]));
                $result->setAutocomplete(implode(' ', $this->buildCommandFromArguments($arguments)[1]));
                $return = [$result];
                break;
            case 'unset':
                $result = new WorkflowResult();
                $result->setTitle("Remove " . $arguments['optionName']);
                $result->setSubtitle("Remove " . $arguments['optionName'] . ' with "' . $vars[$arguments['optionName']] . '"');
                $result->setValid(true);
                $result->setArg('-x ' . implode(' ', $this->buildCommandFromArguments($arguments)[1]));
                $result->setAutocomplete(implode(' ', $this->buildCommandFromArguments($arguments)[1]));
                $return = [$result];
                break;
        }
        return $return;
    }
}
