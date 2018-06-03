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

    protected function buildDefaultWorkflow($imagePath): void
    {
        $result = new WorkflowResult();
        $result->setArg('arg');
        $result->setLargetype('largetype');
        $result->setSubtitle('subtitle');
        $result->setTitle('title');
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

    protected function buildDefaultJson(string $imagePath): string
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
                    "title": "title",
                    "type": "default",
                    "uid": "uid",
                    "valid": true
                }
            ]
        }';
        return $expected;
    }
}
