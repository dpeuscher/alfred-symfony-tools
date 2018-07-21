[![Build Status](https://travis-ci.org/dpeuscher/alfred-symfony-tools.svg?branch=master)](https://travis-ci.org/dpeuscher/alfred-symfony-tools) [![codecov](https://codecov.io/gh/dpeuscher/alfred-symfony-tools/branch/master/graph/badge.svg)](https://codecov.io/gh/dpeuscher/alfred-symfony-tools)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fdpeuscher%2Falfred-symfony-tools.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2Fdpeuscher%2Falfred-symfony-tools?ref=badge_shield)

<!--ts-->
   * [alfred-symfony-tools](#alfred-symfony-tools)
      * [Autocomplete Lists](#autocomplete-lists)
         * [Hard coded argument-lists](#hard-coded-argument-lists)
         * [Dynamically loaded argument-lists](#dynamically-loaded-argument-lists)
         * [Execution operations](#execution-operations)
      * [ConfigureCommand](#configurecommand)
         * [DotEnvEditor](#dotenveditor)
      * [Alfred Workflow](#alfred-workflow)
         * [alfred.php](#alfredphp)
         * [Creating the workflow](#creating-the-workflow)
         * [Tips](#tips)
            * [Environment variables](#environment-variables)
            * [Config command](#config-command)
            * [Example](#example)

<!-- Added by: dominikpeuscher, at: Sun Jun 10 21:45:42 CEST 2018 -->

<!--te-->

# alfred-symfony-tools
Toolkit for integrating Alfred workflows with Symfony4 Frameworks Commands

This toolkit helps if you want to use the Symfony Command-Structure with Alfred workflows.
You can easily add ways to autocomplete parameters without thinking too much about the idea that Alfred workflows will
start a new process everytime you type something. Also there is a Command that you can extend from to allow you setting
.env-Parameters via Alfred.

## Autocomplete Lists

To provide the user of your Alfred workflow with an easy way to navigate through his options, you have to provide a list
of allowed values for your arguments. With that the user can easily walk through all options with the Alfred interface.

To create a Command that is capable of this, you need to extend from either 
```Dpeuscher\AlfredSymfonyTools\CommandExtension\AlfredInteractiveContainerAwareCommand``` or
```Dpeuscher\AlfredSymfonyTools\CommandExtension\AlfredInteractiveCommand```. You have then the ability to either define
the allowed values while defining the argument in the ```configure()```-method (good for static arguments, whose values
are hardcoded) or later in the ```initialize()```-method of the Command.

### Hard coded argument-lists

```php
    protected function configure()
    {
        $this
            ->setName('commandname')
            ->addArgument('arg1', InputArgument::OPTIONAL, ['option1', 'option2'])
            ->addArgument('arg2', InputArgument::OPTIONAL, 
                ['shortoption2.1' => 'longoption2.1', 'shortoption2.2' => 'longoption2.2'])
            ->addArgument('arg3', InputArgument::OPTIONAL, ['option3.1'], true)
            ->addArgument('arg4', InputArgument::OPTIONAL)
            ->addArgument('arg5', InputArgument::OPTIONAL + InputArgument::IS_ARRAY);
    }
```
You can see that it is possible to use numeric or associative option-lists. If you use numeric lists (arg1), the 
selector willbe defined automatically by the first combination of words that identify your option. For associative lists
(arg2) the keys will be used as identifier. You can allow to add selections that are not in the list by setting the
```$allowNew```-parameter to true (arg3). Also it is possible that you mix autocomplete-arguments with general ones (arg4, arg5).

### Dynamically loaded argument-lists

```php
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $options4 = $this->getContainer()->getParameter('options4');
        $this->addArgumentsAllowedValues('arg4', $options4);
    }
```

Since the container is not available in the ```configure()```-method, you might want to add options in the 
```initialize()```-method from parameters that are set in the config or .env file. You can to that with the 
```addArgumentsAllowedValues()```-method that also supports the ```$allowNew```-parameter.

### Execution operations

Now you want to be able to handle different inputs differently. There are predefined input-handlers for the autocomplete
arguments, but you can easily override them with the ```addInputHandler($setArguments, $handlerCallable)```-method. Just
define for which set of arguments you want to define a handler and define it. The handler itself can be a closure or a
method within the Command-class. It gets an array of the given variables and possible autocomplete-values and the result
of the generic result as a parameter so you can get started pretty quick in changing the result. If you don't return
something it will default to the (possibly aligned) genericResult-entry from the arguments-array.

To set the titles of the options of _arg3_ to have a leading uppercase letter when you had selected _option1_ as _arg1_
you could do something like this:

```php
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // ...
        $this->addInputHandler(['arg1', 'arg2'], function ($arguments) {
            if ($arguments['arg1'] === 'option1') {
                foreach ($arguments['genericResults'] as $result) {
                    $result->setTitle(ucwords($result->getTitle()));
                }
            }
        });
    }
```

> The order of the parameters matters. ```['arg2', 'arg1']``` in the upper example will never match!

The ```WorkflowResult```-class is only a representation for the format of the Alfred workflow json definition. It 
supports the following method:
- uid
- title
- subtitle
- quicklookurl
- type
- valid
- icon
- autocomplete
- largetype
- arg
- copy

To understand their meaning please look into the
[Alfred documentation](https://www.alfredapp.com/help/workflows/inputs/script-filter/json/). As an addition, if you
provide a url as an icon, ```WorkflowHelper``` will copy it to your tmp folder (that you can change in the constructor),
formats it and uses it, since Alfred itself can only handle local images.

## ConfigureCommand

To use the ConfigureCommand to write data into your .env-file you have to extend it in your commands-folder to make it
dispatchable. You then have to define which Variables you want to be able to change via Alfred and define them in your
configuration at the key ```configValues```. It should be a numeric array, that has the names of the variables as values.
If you want to define an array, add ```[]``` behind its name.
```yaml
    configValues:
      - CONFIG1
      - CONFIG2
      - CONFIGARRAY3[]
```
You can then set these values if you call the route ```config``` in your application.

### DotEnvEditor
To use the ConfigureCommand you have to create a special config, that has be to named _dotenveditor.php_ and should be
in your _config/_-folder. If you want to set it somewhere else you can do so by setting the config parameter 
**configDotEnvFileConfiguration** to the path where it can be found. the file needs to return an array with at least the
key ```pathToEnv``` that links to your .env-file. You can optionally provide a ```backupPath``` for backups. An example
file for the default configuration would be:

**config/dotenveditor.php**:
```php
<?php

$root = dirname(__DIR__);

return [
    'pathToEnv'  => $root . '/.env',
    'backupPath' => $root . '/var/backups/',
];
``` 

## Alfred Workflow

### alfred.php
To properly work, you need a special alfred-bin file. It should not have the shebang-line of the _bin/console_ script in
it, because that would break the valid json-output. Therefor the easiest way would be to copy the _bin/console_ to
_bin/alfred.php_. You can also use the provided alfred.php in the example-folder of this repository.

To have a proper escaping, you should add these lines underneath the use-statements of the console-file (the alfred.php
in the examples folder has it already):
```php
if (!isset($alfredArgv)) {
    $alfredArgv = implode(' ', $argv);
}

$_SERVER['argv'] = explode(' ', iconv("UTF-8-MAC", "UTF-8", $alfredArgv));

$argv = $_SERVER['argv'];
```

### Creating the workflow
You create a new Alfred workflow by clicking on the "+" in the bottom bar and select "Blank Workflow". After that, 
select your workflow and right click into the empty space, click on "Input" and select "Script Filter". Double-click the
"Script Filter" item on the page and define a placeholder. Configure the placeholder-stuff as you like. Use the 
following script to have the dispatching working:
```php
$alfredArgv = "bin/console command {query}";

include 'bin/alfred.php';
```
You have to select "/usr/bin/php" as Language, "with input as {query}", tick "Double Quotes", "Backslashes" and 
"Dollars" in the "Escaping"-Selection on the bottom. 

![Input Filter look](/examples/images/alfred_input_filter.png "Input Filter look")

> Replace the "command" in the _script_ and _keyword_ section by the command you want to have

### Tips

#### Environment variables
The output commands can use variables, that you pass in the ```variables()```-method of the 
```WorkflowHelper```-Class to work with it. You can access it in the command as a protected parameter 
```$workflowHelper```:
```php
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->workflowHelper->variable('var', 'value');
    }
```
In the connected script, you can access it by calling environment variables. Here are a few ways to access:
- Applescript
```applescript
(do shell script "echo $var")
```
- Bash
```bash
echo ${var}
```
- PHP
```php
echo $_ENV['var'];
```
#### Config command
Activate "Argument Optional" instead of "Argument Required". 
The script for the config looks like this:
 ```php
$alfredArgv = "bin/console config {query}";

include 'bin/alfred.php';
 ```
It is connected to a "Run Script" item with the following script:
```php
$alfredArgv = "bin/console config -x {query}";

include 'bin/alfred.php';
```
The rest of the settings of the "Run Script" should look like the "Script Input" item.

#### Example
Find a proof-of-concept here: [dpeuscher/alfred-movie-rating](https://github.com/dpeuscher/alfred-movie-rating)


## License
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fdpeuscher%2Falfred-symfony-tools.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2Fdpeuscher%2Falfred-symfony-tools?ref=badge_large)