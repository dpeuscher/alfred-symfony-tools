<?php

namespace Dpeuscher\AlfredSymfonyTools\Alfred;

/**
 * @category  Alfred-Toolkit
 * @copyright Copyright (c) 2017 Dominik Peuscher
 */
class WorkflowResult
{
    const JSON_OPTIONS = JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE;
    /**
     * @var string
     */
    protected $uid;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $subtitle;

    /**
     * @var string
     */
    protected $quicklookurl;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var bool
     */
    protected $valid;

    /**
     * @var string
     */
    protected $icon;

    /**
     * @var string
     */
    protected $autocomplete;

    /**
     * @var string
     */
    protected $largetype;

    /**
     * @var string
     */
    protected $arg;

    /**
     * @var string
     */
    protected $copy;

    /**
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     * @return WorkflowResult
     */
    public function setUid(string $uid): WorkflowResult
    {
        $this->uid = $uid;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return WorkflowResult
     */
    public function setTitle(string $title): WorkflowResult
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * @param string $subtitle
     * @return WorkflowResult
     */
    public function setSubtitle(string $subtitle): WorkflowResult
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    /**
     * @return string
     */
    public function getQuicklookurl()
    {
        return $this->quicklookurl;
    }

    /**
     * @param string $quicklookurl
     * @return WorkflowResult
     */
    public function setQuicklookurl(string $quicklookurl): WorkflowResult
    {
        $this->quicklookurl = $quicklookurl;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return WorkflowResult
     */
    public function setType(string $type): WorkflowResult
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return bool
     */
    public function getValid()
    {
        return $this->valid;
    }

    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @param bool $valid
     * @return WorkflowResult
     */
    public function setValid(bool $valid): WorkflowResult
    {
        $this->valid = $valid;
        return $this;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     * @return WorkflowResult
     */
    public function setIcon(string $icon): WorkflowResult
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return string
     */
    public function getAutocomplete()
    {
        return $this->autocomplete;
    }

    /**
     * @param string $autocomplete
     * @return WorkflowResult
     */
    public function setAutocomplete(string $autocomplete): WorkflowResult
    {
        $this->autocomplete = $autocomplete;
        return $this;
    }

    /**
     * @return string
     */
    public function getLargetype()
    {
        return $this->largetype;
    }

    /**
     * @param string $largetype
     * @return WorkflowResult
     */
    public function setLargetype(string $largetype): WorkflowResult
    {
        $this->largetype = $largetype;
        return $this;
    }

    /**
     * @return string
     */
    public function getArg()
    {
        return $this->arg;
    }

    /**
     * @param string $arg
     * @return WorkflowResult
     */
    public function setArg(string $arg): WorkflowResult
    {
        $this->arg = $arg;
        return $this;
    }

    /**
     * @return string
     */
    public function getCopy()
    {
        return $this->copy;
    }

    /**
     * @param string $copy
     * @return WorkflowResult
     */
    public function setCopy(string $copy): WorkflowResult
    {
        $this->copy = $copy;
        return $this;
    }


}
