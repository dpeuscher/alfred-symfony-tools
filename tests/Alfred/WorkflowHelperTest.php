<?php

namespace Dpeuscher\AlfredSymfonyTools\Tests\Alfred;

use Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowHelper;
use Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowResult;
use PHPUnit\Framework\TestCase;

/**
 * @category  alfred-symfony-tools
 * @copyright Copyright (c) 2018 Dominik Peuscher
 * @covers \Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowHelper
 * @covers \Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowResult
 */
class WorkflowHelperTest extends TestCase
{
    /**
     * @var WorkflowHelper
     */
    protected $sut;

    /**
     * @var string
     */
    protected $prevPath;

    public function setUp()
    {
        if (realpath(__DIR__ . '/../../') != realpath(getcwd())) {
            $this->prevPath = getcwd();
            chdir(__DIR__ . '/../../');
        }

        $this->sut = new WorkflowHelper('tests/tmp/');
    }

    public function tearDown()
    {
        if (isset($this->prevPath)) {
            chdir($this->prevPath);
        }
    }

    public function testResizeSquareImage()
    {
        $this->buildDefaultWorkflow('tests/fixtures/100x100.png');

        $expected = $this->buildDefaultJson('tests/fixtures/100x100.png');
        $this->assertJsonStringEqualsJsonString($expected, $this->sut->__toString());
    }

    public function testResizeHeightImage()
    {
        $this->buildDefaultWorkflow('tests/fixtures/100x100_height.png');

        $expected = $this->buildDefaultJson('tests/fixtures/100x100_height.png');
        $this->assertJsonStringEqualsJsonString($expected, $this->sut->__toString());
    }

    public function testResizeWidthImage()
    {
        $this->buildDefaultWorkflow('tests/fixtures/100x100_width.png');

        $expected = $this->buildDefaultJson('tests/fixtures/100x100_width.png');
        $this->assertJsonStringEqualsJsonString($expected, $this->sut->__toString());
    }

    public function testVariable()
    {
        $this->buildDefaultWorkflow();

        $expectedJson = json_decode($this->buildDefaultJson(), JSON_OBJECT_AS_ARRAY);
        $expectedJson['variables'] = [];
        $expectedJson['variables']['key'] = 'value';

        $this->sut->variable('key', 'value');

        $this->assertJsonStringEqualsJsonString(json_encode($expectedJson), $this->sut->__toString());
    }

    public function testSort()
    {
        $this->buildDefaultWorkflow('tests/fixtures/100x100.png', 'Title B');
        $this->buildDefaultWorkflow('tests/fixtures/100x100.png', 'Title A');

        $expectedJson = json_decode($this->buildDefaultJson('tests/fixtures/100x100.png'), JSON_OBJECT_AS_ARRAY);
        $item = current($expectedJson['items']);
        $expectedJson['items'][] = $item;
        $expectedJson['items'][0]['title'] = 'Title B';
        $expectedJson['items'][1]['title'] = 'Title A';

        $this->assertJsonStringEqualsJsonString(json_encode($expectedJson), $this->sut->__toString());

        $this->sut->sortResults();

        $this->assertJsonStringNotEqualsJsonString(json_encode($expectedJson), $this->sut->__toString());

        $expectedJson['items'][0]['title'] = 'Title A';
        $expectedJson['items'][1]['title'] = 'Title B';

        $this->assertJsonStringEqualsJsonString(json_encode($expectedJson), $this->sut->__toString());
    }

    public function testFilter()
    {
        $this->buildDefaultWorkflow('tests/fixtures/100x100.png', 'Title B');
        $this->buildDefaultWorkflow('tests/fixtures/100x100.png', 'Title A');

        $expectedJson = json_decode($this->buildDefaultJson('tests/fixtures/100x100.png'), JSON_OBJECT_AS_ARRAY);
        $item = current($expectedJson['items']);
        $expectedJson['items'][] = $item;
        $expectedJson['items'][0]['title'] = 'Title B';
        $expectedJson['items'][1]['title'] = 'Title A';

        $this->assertJsonStringEqualsJsonString(json_encode($expectedJson), $this->sut->__toString());

        $this->sut->filterResults('Title A');

        $this->assertJsonStringNotEqualsJsonString(json_encode($expectedJson), $this->sut->__toString());

        $item = $expectedJson['items'][1];
        unset($expectedJson['items'][0], $expectedJson['items'][1]);
        $expectedJson['items'][0] = $item;

        $this->assertJsonStringEqualsJsonString(json_encode($expectedJson), $this->sut->__toString());
    }

    protected function buildDefaultWorkflow($imagePath = 'tests/fixtures/100x100.png', $title = 'title'): void
    {
        $result = new WorkflowResult();
        $result->setArg('arg');
        $result->setLargetype('largetype');
        $result->setSubtitle('subtitle');
        $result->setTitle($title);
        $result->setAutocomplete('autocomplete');
        $result->setValid(true);
        $this->assertTrue($result->isValid());
        $result->setType('default');
        $result->setUid('uid');
        $result->setCopy('copy');
        $result->setQuicklookurl('https://www.alfredapp.com');
        $result->setIcon($imagePath);
        $this->sut->applyResult($result, true);
        unlink('tests/tmp/' . md5($imagePath) . '.jpg');
    }

    protected function buildDefaultJson($imagePath = 'tests/fixtures/100x100.png', $title = 'title'): string
    {
        $expected = '{
            "items": [
                {
                    "arg": "arg",
                    "autocomplete": "autocomplete",
                    "icon": {
                        "path": "tests\/tmp\/' . md5($imagePath) . '.jpg"
                    },
                    "quicklookurl": "https:\/\/www.alfredapp.com",
                    "subtitle": "subtitle",
                    "text": {
                        "largetype": "largetype",
                        "copy": "copy"
                    },
                    "title": "' . $title . '",
                    "type": "default",
                    "uid": "uid",
                    "valid": true
                }
            ]
        }';
        return $expected;
    }
}
