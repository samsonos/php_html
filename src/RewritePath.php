<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 05.12.2014
 * Time: 12:36
 */
namespace samson\html;

/**
 * Resource paths rebuilder
 * @package samson\html
 */
class RewritePath
{
    /** @var \samson\fs\AbstractFileService File system service */
    protected $fs;

    /** @var string Source file path */
    protected $source;

    /** @var string Resource path matching template */
    protected $template;

    /** @var array Collection of paths for rewriting */
    protected $found;

    /**
     * Constructor
     * @param string $source File path
     * @param string Resource path matching template
     */
    public function __construct(\samson\fs\AbstractFileService $fs, $source, $template)
    {
        $this->fs = $fs;
        $this->source = $source;
        $this->template = $template;
    }
}
