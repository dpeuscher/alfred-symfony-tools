<?php

namespace Dpeuscher\AlfredSymfonyTools\Alfred;

use Alfred\Workflows\Workflow;

/**
 * @category  Alfred-Toolkit
 * @copyright Copyright (c) 2017 Dominik Peuscher
 */
class WorkflowHelper
{
    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var Workflow
     */
    protected $workflow;

    /**
     * WorkflowHelper constructor.
     *
     * @param string $cacheDir
     * @param Workflow $workflow
     */
    public function __construct(string $cacheDir = '/tmp/', Workflow $workflow = null)
    {
        $this->cacheDir = $cacheDir;
        $this->workflow = $workflow;
        if (is_null($this->workflow)) {
            $this->workflow = new Workflow();
        }
    }

    /**
     * @param $url
     * @return string|null
     * @see https://stackoverflow.com/questions/48087867/php-output-images-with-fixed-filesize
     */
    public function getImage($url)
    {
        $localPath = $this->cacheDir . md5($url) . '.jpg';
        if (!file_exists($localPath)) {
            file_put_contents($localPath, file_get_contents($url));
            list($orig_w, $orig_h) = getimagesize($localPath);
            $orig_img = imagecreatefromstring(file_get_contents($localPath));

            $output_w = 200;
            $output_h = 200;

            if ($orig_h > $orig_w) {
                $scale = $output_h / $orig_h;
            } else {
                $scale = $output_w / $orig_w;
            }

            // calc new image dimensions
            $new_w = $orig_w * $scale;
            $new_h = $orig_h * $scale;

            $offest_x = ($output_w - $new_w) / 2;
            $offest_y = ($output_h - $new_h) / 2;

            // create new image and fill with background colour
            $new_img = imagecreatetruecolor($output_w, $output_h);
            $bgcolor = imagecolorallocate($new_img, 255, 255, 255); // red
            imagefill($new_img, 0, 0, $bgcolor); // fill background colour

            // copy and resize original image into center of new image
            imagecopyresampled($new_img, $orig_img, $offest_x, $offest_y, 0, 0, $new_w, $new_h, $orig_w, $orig_h);

            //save it
            imagejpeg($new_img, $localPath, 80);
        }
        return $localPath;
    }

    public function applyResult(WorkflowResult $result, $forceCopyIcon = false)
    {
        if ($forceCopyIcon || parse_url($result->getIcon(), PHP_URL_HOST)) {
            $result->setIcon($this->getImage($result->getIcon()));
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $this->workflow->result()->uid($result->getUid())
            ->title($result->getTitle())
            ->subtitle($result->getSubtitle())
            ->quicklookurl($result->getQuicklookurl())
            ->type($result->getType())
            ->valid($result->getValid())
            ->icon($result->getIcon())
            ->autocomplete($result->getAutocomplete())
            ->largetype($result->getLargetype())
            ->arg($result->getArg())
            ->copy($result->getCopy());
    }

    public function __toString(): string
    {
        return $this->workflow->output();
    }

    /**
     * Add a variables to the workflow
     *
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function variable($key, $value)
    {
        $this->workflow->variable($key, $value);
        return $this;
    }

    /**
     * Sort the current results
     *
     * @param string $direction
     * @param string $property
     * @return $this
     */
    public function sortResults($direction = 'asc', $property = 'title')
    {
        $this->workflow->sortResults($direction, $property);
        return $this;
    }

    /**
     * Filter current results (destructive)
     *
     * @param string $query
     * @param string $property
     * @return $this
     */
    public function filterResults($query, $property = 'title')
    {
        $this->workflow->filterResults($query, $property);
        return $this;
    }
}
