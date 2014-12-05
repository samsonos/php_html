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
    /** @var \samson\fs\LocalFileService File system service */
    protected $fileService;

    /** @var string Source file path */
    protected $source;

    /** @var string Resource path matching template */
    protected $template;

    /** @var array Collection of paths for rewriting */
    protected $found;

    /**
     * Constructor
     * @param \samson\fs\LocalFileService $fs Local file system service
     * @param string $source File path
     * @param string Resource path matching template
     */
    public function __construct(\samson\fs\LocalFileService $fileService, $source, $template)
    {
        $this->fileService = $fileService;

        // Check if source file exists in current file system
        if ($fileService->exists($source)) {
            $this->source = $source;
            $this->template = $template;
        } else { // Signal error
            return e(
                'Cannot create '.get_class($this).' instance - Source file[##] does not exists',
                E_SAMSON_CORE_ERROR,
                $source
            );
        }
    }
}
