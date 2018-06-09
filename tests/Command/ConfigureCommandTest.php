<?php

namespace Dpeuscher\AlfredSymfonyTools\Tests\Command;

use Dpeuscher\AlfredSymfonyTools\Command\ConfigureCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

/**
 * @category  alfred-symfony-tools
 * @copyright Copyright (c) 2018 Dominik Peuscher
 * @covers \Dpeuscher\AlfredSymfonyTools\Command\ConfigureCommand
 * @covers \Dpeuscher\AlfredSymfonyTools\CommandExtension\AlfredInteractiveCommand
 * @covers \Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowHelper
 * @covers \Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowResult
 * @covers \Dpeuscher\AlfredSymfonyTools\CommandExtension\AlfredInteractiveContainerAwareCommand
 */
class ConfigureCommandTest extends TestCase
{
    /**
     * @var ConfigureCommand
     */
    protected $sut;

    /**
     * @var string
     */
    protected $prevPath;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var mixed
     */
    protected $previousApplication;

    public function setUp()
    {
        if (realpath(__DIR__ . '/../../') != realpath(getcwd())) {
            $this->prevPath = getcwd();
            chdir(__DIR__ . '/../../');
        }

        if (file_exists('tests/tmp/.env.new')) {
            unlink('tests/tmp/.env.new');
        }
        if (file_exists('tests/tmp/.env')) {
            unlink('tests/tmp/.env');
        }

        $this->container = new Container();
        $kernel = new class($this->container) extends Kernel
        {
            public function __construct(Container $container)
            {
                parent::__construct('test', false);
                $this->container = $container;
            }

            public function registerBundles()
            {
            }

            public function registerContainerConfiguration(LoaderInterface $loader)
            {
            }
        };
        $application = new Application($kernel);
        if (isset($GLOBALS['application'])) {
            $this->previousApplication = $GLOBALS['application'];
        }
        $GLOBALS['application'] = $application;
    }

    public function testDefinitionCanSelectScalarItem()
    {
        $this->loadContainer('configure_parameters.yml');

        $definition = $this->sut->getDefinition();

        $input = new ArrayInput(['optionName' => 'test'], $definition);

        $this->assertSame('test', $input->getArgument('optionName'));
    }

    public function testDefinitionCanSetStringScalar()
    {
        $this->loadContainer('configure_parameters.yml');

        $definition = $this->sut->getDefinition();

        $configValueKey = 'key';

        $input = new ArrayInput(['optionName' => 'test', 'operation' => 'set', 'key' => $configValueKey], $definition);

        $this->assertSame('test', $input->getArgument('optionName'));
        $this->assertSame('set', $input->getArgument('operation'));
        $this->assertSame($configValueKey, $input->getArgument('key'));

    }

    public function testDefinitionCanUnsetStringScalar()
    {
        $this->loadContainer('configure_parameters_array.yml');

        $definition = $this->sut->getDefinition();

        $input = new ArrayInput(['optionName' => 'test', 'operation' => 'unset'], $definition);

        $this->assertSame('test', $input->getArgument('optionName'));
        $this->assertSame('unset', $input->getArgument('operation'));
    }

    public function testDefinitionCanAddToArray()
    {
        $this->loadContainer('configure_parameters_array.yml');

        $definition = $this->sut->getDefinition();

        $configValueKey = 'key';
        $configValueValue = 'value';

        $input = new ArrayInput([
            'optionName' => 'configValue2',
            'operation'  => 'set',
            'key'        => $configValueKey,
            'value'      => [$configValueValue],
        ],
            $definition);

        $this->assertSame('configValue2', $input->getArgument('optionName'));
        $this->assertSame('set', $input->getArgument('operation'));
        $this->assertSame($configValueKey, $input->getArgument('key'));
        $this->assertSame([$configValueValue], $input->getArgument('value'));
    }

    public function testDefinitionCanUnsetFullArray()
    {
        $this->loadContainer('configure_parameters_array.yml');

        $definition = $this->sut->getDefinition();

        $input = new ArrayInput([
            'optionName' => 'configValue2',
            'operation'  => 'unset',
        ],
            $definition);

        $this->assertSame('configValue2', $input->getArgument('optionName'));
        $this->assertSame('unset', $input->getArgument('operation'));
        $this->assertNull($input->getArgument('key'));
    }

    public function testDefinitionCanUnsetArrayItem()
    {
        $this->loadContainer('configure_parameters_array.yml');

        $definition = $this->sut->getDefinition();

        $configValueKey = 'key';

        $input = new ArrayInput([
            'optionName' => 'configValue2',
            'operation'  => 'unset',
            'key'        => $configValueKey,
        ],
            $definition);

        $this->assertSame('configValue2', $input->getArgument('optionName'));
        $this->assertSame('unset', $input->getArgument('operation'));
        $this->assertSame($configValueKey, $input->getArgument('key'));
    }

    public function testLoadsDotEnvConfigurationFileFromConfig()
    {
        $this->loadContainer('configure_parameters_array.yml');
        $definition = $this->sut->getDefinition();
        $input = new ArrayInput([], $definition);
        $output = new NullOutput();

        $this->callConfigure();
        $this->callInitialize($input, $output);

        $filename = $this->returnDotEnvFileName();

        $this->assertSame('tests/fixtures/dotenveditor.php', $filename);;
    }

    public function testLoadsDotEnvConfigurationFileFromConfigNoDotEnv()
    {
        $this->loadContainer('configure_parameters_nodotenv.yml');
        $definition = $this->sut->getDefinition();
        $input = new ArrayInput([], $definition);
        $output = new NullOutput();

        $this->callConfigure();
        $this->callInitialize($input, $output);

        $filename = $this->returnDotEnvFileName();

        $this->assertSame('tests/fixtures/config/dotenveditor.php', $filename);
    }

    public function testCanSetScalarValueInNewDotEnvFile()
    {
        $this->loadContainer('configure_parameters_newdotenv.yml');
        $definition = $this->sut->getDefinition();

        $configValue = 'test';

        $input = new ArrayInput([
            '--execute'  => true,
            'optionName' => 'configValue1',
            'operation'  => 'set',
            'key'        => $configValue,
        ],
            $definition);
        $output = new NullOutput();

        $this->callConfigure();
        $this->callInitialize($input, $output);
        $this->callExecute($input, $output);

        $this->assertFileExists('tests/tmp/.env.new');
    }

    public function testCanSetScalarValueWithMoreThanOneWordInNewDotEnvFile()
    {
        $this->loadContainer('configure_parameters_newdotenv.yml');
        $definition = $this->sut->getDefinition();

        $configValue = 'test 123';

        $input = new ArrayInput([
            '--execute'  => true,
            'optionName' => 'configValue1',
            'operation'  => 'set',
            'key'        => strtok($configValue, ' '),
            'value'      => [strtok(' ')],
        ],
            $definition);
        $output = new NullOutput();

        $this->callConfigure();
        $this->callInitialize($input, $output);
        $this->callExecute($input, $output);

        $this->assertFileExists('tests/tmp/.env.new');
    }

    public function testCanAddCorrectScalarValueInExistingDotEnvFile()
    {
        copy('tests/fixtures/configure_parameters.env', 'tests/tmp/.env');
        $this->loadContainer('configure_parameters.yml');
        $definition = $this->sut->getDefinition();

        $configValue = 'test';
        $envVarName = 'configValue1';

        $input = new ArrayInput([
            '--execute'  => true,
            'optionName' => $envVarName,
            'operation'  => 'set',
            'key'        => $configValue,
        ],
            $definition);
        $output = new NullOutput();

        $this->callConfigure();
        $this->callInitialize($input, $output);
        $this->callExecute($input, $output);

        $this->assertEnvVar('tests/tmp/.env', 'test0', 'configValue0');
        $this->assertEnvVar('tests/tmp/.env', $configValue, $envVarName);
    }

    public function testCanChangeCorrectScalarValueInExistingDotEnvFile()
    {
        copy('tests/fixtures/configure_parameters_existing.env', 'tests/tmp/.env');
        $this->loadContainer('configure_parameters.yml');
        $definition = $this->sut->getDefinition();

        $configValue = 'test';
        $envVarName = 'configValue1';

        $input = new ArrayInput([
            '--execute'  => true,
            'optionName' => $envVarName,
            'operation'  => 'set',
            'key'        => $configValue,
        ],
            $definition);
        $output = new NullOutput();

        $this->callConfigure();
        $this->callInitialize($input, $output);
        $this->callExecute($input, $output);

        $this->assertEnvVar('tests/tmp/.env', $configValue, $envVarName);
    }

    public function testCanUnsetScalarValueInExistingDotEnvFile()
    {
        copy('tests/fixtures/configure_parameters_existing.env', 'tests/tmp/.env');
        $this->loadContainer('configure_parameters.yml');
        $definition = $this->sut->getDefinition();

        $envVarName = 'configValue1';

        $input = new ArrayInput([
            '--execute'  => true,
            'optionName' => $envVarName,
            'operation'  => 'unset',
        ],
            $definition);
        $output = new NullOutput();

        $this->callConfigure();
        $this->callInitialize($input, $output);
        $this->callExecute($input, $output);

        $this->assertEnvVarNotSet('tests/tmp/.env', $envVarName);
    }

    public function testCanAddCorrectArrayValueInExistingDotEnvFile()
    {
        $this->loadContainer('configure_parameters_array.yml');
        $definition = $this->sut->getDefinition();

        $configValue = 'test';
        $configKey = 'configKey1';
        $envVarName = 'configValue2';

        $input = new ArrayInput([
            '--execute'  => true,
            'optionName' => $envVarName,
            'operation'  => 'set',
            'key'        => $configKey,
            'value'      => [$configValue],
        ],
            $definition);
        $output = new NullOutput();

        $this->callConfigure();
        $this->callInitialize($input, $output);
        $this->callExecute($input, $output);

        $this->assertEnvVar('tests/tmp/.env', json_encode([$configKey => $configValue]), $envVarName, true);
    }

    public function testCanAddCorrectArrayValueToExistingArrayInExistingDotEnvFile()
    {
        copy('tests/fixtures/configure_parameters_array_existing.env', 'tests/tmp/.env');
        $this->loadContainer('configure_parameters_array.yml');
        $definition = $this->sut->getDefinition();

        $configValue = 'test';
        $configKey = 'configKey1';
        $envVarName = 'configValue2';

        $input = new ArrayInput([
            '--execute'  => true,
            'optionName' => $envVarName,
            'operation'  => 'set',
            'key'        => $configKey,
            'value'      => [$configValue],
        ],
            $definition);
        $output = new NullOutput();

        $this->callConfigure();
        $this->callInitialize($input, $output);
        $this->callExecute($input, $output);

        $this->assertEnvVar('tests/tmp/.env', json_encode([
            'configKey0' => 'test0',
            $configKey   => $configValue,
        ]), $envVarName, true);
    }

    public function testCanAddCorrectEmptyStringInExistingArrayInExistingDotEnvFile()
    {
        copy('tests/fixtures/configure_parameters_array_existing.env', 'tests/tmp/.env');
        $this->loadContainer('configure_parameters_array.yml');
        $definition = $this->sut->getDefinition();

        $configKey = 'configKey1';
        $envVarName = 'configValue2';

        $input = new ArrayInput([
            '--execute'  => true,
            'optionName' => $envVarName,
            'operation'  => 'set',
            'key'        => $configKey,
        ],
            $definition);
        $output = new NullOutput();

        $this->callConfigure();
        $this->callInitialize($input, $output);
        $this->callExecute($input, $output);

        $this->assertEnvVar('tests/tmp/.env', json_encode([
            'configKey0' => 'test0',
            $configKey   => '',
        ]), $envVarName, true);
    }

    public function testCanRemoveItemInExistingArrayInExistingDotEnvFile()
    {
        copy('tests/fixtures/configure_parameters_array_existing.env', 'tests/tmp/.env');
        $this->loadContainer('configure_parameters_array.yml');
        $definition = $this->sut->getDefinition();

        $configKey = 'configKey0';
        $envVarName = 'configValue2';

        $input = new ArrayInput([
            '--execute'  => true,
            'optionName' => $envVarName,
            'operation'  => 'remove',
            'key'        => $configKey,
        ],
            $definition);
        $output = new NullOutput();

        $this->callConfigure();
        $this->callInitialize($input, $output);
        $this->callExecute($input, $output);

        $this->assertEnvVar('tests/tmp/.env', json_encode([]), $envVarName, true);
    }

    public function testCanUnsetArrayInExistingDotEnvFile()
    {
        copy('tests/fixtures/configure_parameters_array_existing.env', 'tests/tmp/.env');
        $this->loadContainer('configure_parameters_array.yml');
        $definition = $this->sut->getDefinition();

        $envVarName = 'configValue2';

        $input = new ArrayInput([
            '--execute'  => true,
            'optionName' => $envVarName,
            'operation'  => 'unset',
        ],
            $definition);
        $output = new NullOutput();

        $this->callConfigure();
        $this->callInitialize($input, $output);
        $this->callExecute($input, $output);

        $this->assertEnvVarNotSet('tests/tmp/.env', $envVarName);
    }

    protected function callConfigure()
    {
        $this->sut = new ConfigureCommand();
    }

    protected function callInitialize(InputInterface $input, OutputInterface $output)
    {
        $initializeCall = function (InputInterface $input, OutputInterface $output) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->initialize($input, $output);
        };
        $initializeCall->call($this->sut, $input, $output);
    }

    protected function callInitializeReflection(InputInterface $input, OutputInterface $output)
    {
        $reflectionObject = new \ReflectionObject($this->sut);
        $reflectionMethod = $reflectionObject->getMethod('initialize');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->sut, $input, $output);
    }

    protected function callExecute(InputInterface $input, OutputInterface $output)
    {
        $executeCall = function (InputInterface $input, OutputInterface $output) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->execute($input, $output);
        };
        $executeCall->call($this->sut, $input, $output);
    }

    protected function returnDotEnvFileName()
    {
        $executeCall = function () {
            /** @noinspection PhpUndefinedFieldInspection */
            return $this->dotEnvConfigurationFile;
        };
        return $executeCall->call($this->sut);
    }

    protected function tearDown()
    {
        unset($GLOBALS['application']);
        if (isset($this->previousApplication)) {
            $GLOBALS['application'] = $this->previousApplication;
        }
        if (file_exists('tests/tmp/.env.new')) {
            unlink('tests/tmp/.env.new');
        }
        if (file_exists('tests/tmp/.env')) {
            unlink('tests/tmp/.env');
        }
    }

    /**
     * @param $configFile
     */
    protected function loadContainer($configFile): void
    {
        $json = Yaml::parseFile('tests/fixtures/' . $configFile);
        foreach ($json as $key => $value) {
            $this->container->setParameter($key, $value);
        }

        $this->callConfigure();
    }

    /**
     * @param $dotEnvFile
     * @param $expected
     * @param $envVarName
     * @param bool $json
     */
    protected function assertEnvVar($dotEnvFile, $expected, $envVarName, $json = false): void
    {
        if (isset($_ENV[$envVarName])) {
            $recoverConfigValue1 = $envVarName;
            unset($_ENV[$envVarName]);
        }
        $dotEnv = new Dotenv();
        $dotEnv->load($dotEnvFile);

        $this->assertArrayHasKey($envVarName, $_ENV);
        if ($json) {
            $this->assertJsonStringEqualsJsonString($expected, $_ENV[$envVarName]);
        } else {
            $this->assertSame($expected, $_ENV[$envVarName]);
        }

        if (isset($recoverConfigValue1)) {
            $_ENV[$envVarName] = $recoverConfigValue1;
            putenv("configValue1=$recoverConfigValue1");
        }
    }

    /**
     * @param $dotEnvFile
     * @param $envVarName
     */
    protected function assertEnvVarNotSet($dotEnvFile, $envVarName): void
    {
        if (isset($_ENV[$envVarName])) {
            $recoverConfigValue1 = $envVarName;
            unset($_ENV[$envVarName]);
        }
        $dotEnv = new Dotenv();
        $dotEnv->load($dotEnvFile);

        $this->assertArrayNotHasKey($envVarName, $_ENV);

        if (isset($recoverConfigValue1)) {
            $_ENV[$envVarName] = $recoverConfigValue1;
            putenv("configValue1=$recoverConfigValue1");
        }
    }

}
