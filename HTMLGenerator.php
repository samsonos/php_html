<?php
namespace samson\html;

use samson\core\ExternalModule;
use samson\core\File;

/**
 * Интерфейс для подключения модуля в ядро фреймворка SamsonPHP
 *
 * @package SamsonPHP
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @author Nikita Kotenko <nick.w2r@gmail.com>
 * @version 0.1
 */
class HTMLGenerator extends ExternalModule
{
	/** Идентификатор модуля */
	protected $id = 'html';
	
	/** Source path */
	public $input = __SAMSON_CWD__;

	/** Output path */
	public $output;
	
	/* Email recipients */
	public $recipients = array('vitalyiegorov@gmail.com');
	
	/** Path for storing generated views */
	public $cachepath = '/html/';
	
	/** Restricted file types for compression */
	public $restricted = array( 'php', 'vphp', 'buildpath', 'setting', 'project', 'htaccess', 'json', 'js', 'less', 'css', 'coffee', 'gitignore', 'md');

    /**
     * Core render handler for including CSS and JS resources to html
     *
     * @param string                $view   View content
     * @param array                 $data   View data
     * @param \samson\core\Module   $m      Pointer to current module
     *
     * @return string Processed view content
     */
	public function renderer( $view, array $data = array(), $m = null )
	{
		// Cache only local modules
		if( is_a( $m, ns_classname('LocalModule','samson\core')) && url()->module != $this->id )
		{		
			// Build HTML file path
			$path = __SAMSON_CWD__.__SAMSON_CACHE_PATH.$this->cachepath.locale_path();
			
			// Module name
			$path .= isset(url()->module{0}) ? url()->module:'index';
			
			// Module action name
			$path .= isset(url()->method{0}) ? '_'.url()->method{0}:'';
			
			$path .= '.html';		
			
			// Get directory path
			$dir = pathname( $path );
			
			// Create folder
			if( ! file_exists( $dir )) mkdir( $dir, 0755, TRUE );		
			
			// Save html data
			file_put_contents( $path, $view );			
		}
		
		return $view;
	}

    /**
     * Copy file from source location to destination location with
     * analyzing last file modification time, and copying only changed files
     *
     * @param string $src source file
     * @param string $dst destination file
     * @param null   $handler
     *
     * @return bool
     */
	public function copy_resource( $src, $dst, $handler = null )
	{
		if( !file_exists( $src )  ) return e('Cannot copy file - Source file(##) does not exists', E_SAMSON_SNAPSHOT_ERROR, $src );
	
		// Action to do
		$action = null;
		
		// Get source file timestamp
		$source_ts = filemtime( $src );
	
		// If destination file does not exists
		if( !file_exists( $dst ) ) $action = 'Creating';
		// If source file has been changed
		else if( abs($source_ts - filemtime( $dst )) > 125 ) $action = 'Updating';
	
		// If we know what to do
		if( isset( $action ))
		{				
			// Create folder structure if nessesary
			$dir_path = pathname( $dst );
			if( !file_exists( $dir_path ))
			{
				elapsed( '  -- Creating folder structure '.$dir_path.' from '.$src );
				mkdir( $dir_path, 0775, true );
			}
				
			// If file handler specified
			if( is_callable($handler) ) call_user_func( $handler, $src, $dst, $action );
			// Copy file
			else copy( $src, $dst );
			
			elapsed( '  -- '.$action.' file '.$dst.' from '.$src.'(Difference '.date('H:i:s',abs($source_ts - filemtime( $dst ))).')' );
	
			// Touch source file with copied file			
			touch( $src );
		}
	}
	
	/**
	 * Create static HTML site version
	 */
	public function compress()
	{
		// Generate project name
		$project_name = str_replace('local.', '', $_SERVER['HTTP_HOST']);
		
		// If no output path specified
		if( !isset($this->output) )
		{			
			$this->output = str_replace( $_SERVER['HTTP_HOST'], 'final.'.$_SERVER['HTTP_HOST'], $_SERVER['DOCUMENT_ROOT']).url()->base();
		}	
		
		// Clear output folder
		File::clear($this->output);
		
		// Save original output path
		$o_output = $this->output;	
			
		// Iterate all site supported locales
		foreach ( \samson\core\SamsonLocale::$locales as $locale )
		{		
			// Get original output path
			$this->output = $o_output;
			
			// Generate localized path to cached html pages
			$pages_path = __SAMSON_CACHE_PATH.$this->cachepath.locale_path($locale);
			
			// Get realpath to web application
			$realpath = s()->path();			
			
			// Add localized path
			$this->output .= ($locale == \samson\core\SamsonLocale::DEF ? 'def' : $locale).'/';
		
			elapsed('Creating static HTML web-application from: '.$this->input.' to '.$this->output);

			// Clear old HTML data
			if (file_exists($this->output)) {
                elapsed('Clearing old html data from: '.$this->output);
                \samson\core\File::remove($this->output);
            }

            // Create output directory
            mkdir($this->output, 0775, true);
			
			//создаем набор дескрипторов cURL
			$mh = curl_multi_init();
			
			// Perform generation of every controller
			foreach ( s()->load_module_stack['local']['controllers'] as $ctrl )
			{
				// generate controller URL
				$controller = '/'.locale_path($locale).strtolower(basename($ctrl,'.php'));
					
				elapsed('Generating HTML snapshot for: '.$controller);
			
				// Create curl instance
				$ch = \curl_init('127.0.0.1'.$controller);
					
				// Set base request options
				\curl_setopt_array($ch, array(
					CURLOPT_VERBOSE => true,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_HTTPHEADER =>array('Host: '.$_SERVER['HTTP_HOST'] ),
				));	
				
				// Add curl too multi request
				curl_multi_add_handle( $mh, $ch );
			}
			
			// TODO: Create function\module for this
			
			// Curl multirequest sheet
			$active = null;	
			do{
				$mrc = curl_multi_exec($mh, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			
			while ($active && $mrc == CURLM_OK) 
			{
				if (curl_multi_select($mh) != -1) 
				{
					do {
						$mrc = curl_multi_exec($mh, $active);
					} while ($mrc == CURLM_CALL_MULTI_PERFORM);
				}
			}			
			curl_multi_close($mh);

            // Path to new resources
            $cssPath = '';
            $jsPath = '';

            // Copy generated css & js resources to root folder
            if(class_exists('\samson\resourcer\ResourceRouter')) {

                $rr = m('resourcer');

                // Get resourcer generated files
                $cssPath = $rr->cached['css'];
                $jsPath = $rr->cached['js'];

                elapsed('Creating CSS resource file from:'.$cssPath);
                // Read CSS file
                $css = file_get_contents($cssPath);
                // Perform URL rewriting
                $css = preg_replace_callback( '/url\s*\(\s*(\'|\")?([^\)\s\'\"]+)(\'|\")?\s*\)/i', array( $this, 'src_replace_callback'), $css );
                // Write new CSS file
                file_put_contents($this->output.'style.css', $css);

                elapsed('Creating JavaScript resource file from:'.$jsPath);
                $this->copy_resource($this->input.$jsPath, $this->output.'index.js');
            }
			
			// Collection of existing generated views
			$views = '<h1>Список страниц:</h1>';
			
			// Files array
			$files = array();
						
			// Iterate generated pages
			foreach ( \samson\core\File::dir($pages_path,'html',null,$files,0) as $f) 
			{
				// Get just file name
				$view_path = basename($f);

                // Read HTML file
                $html = file_get_contents($this->input.$f);

                // If we have resourcer CSS resource
                if (isset($cssPath{0})) {
                    // Find all CSS links in HTML
                    if (preg_match_all('/<\s*link.*href\s*=\s*["\']?(?<url>[^"\']*)/i', $html, $matches)) {
                        // Iterate all included in HTML CSS links
                        foreach ($matches['url'] as $url) {
                            // If this is link generated by resourcer
                            if ($url == '/'.$cssPath) { // Change it to new one
                                $html = str_replace($url, 'style.css', $html);
                                break;
                            }
                        }
                    }
                }

                // If we have resourcer JS resource
                if (isset($jsPath{0})) {
                    // Find all JS links in HTML
                    if (preg_match_all('/<\s*script.*src\s*=\s*["\']?(?<url>[^"\']*)/i', $html, $matches)) {
                        // Iterate all included in HTML JS links
                        foreach ($matches['url'] as $url) {
                            // If this is link generated by resourcer
                            if ($url == '/'.$jsPath) { // Change it to new one
                                $html = str_replace($url, 'index.js', $html);
                                break;
                            }
                        }
                    }
                }

                // Change path in all img SRC attributes
                if (preg_match_all('/<\s*img\s*src\s*=\s*["\']?(?<url>[^"\']*)/i', $html, $matches)) {
                    if(isset($matches['url'])) {
                        foreach ($matches['url'] as $match) {
                            $html = str_ireplace($match, ltrim($match, '/'), $html);
                        }
                    }
                }

                // Save HTML file
                file_put_contents($this->output.$view_path, $html);

				// Create index.html record
				$views .= '<a href="'.$view_path.'">'.$view_path.'</a><br>';
			}

            // Iterate other resources
			foreach ( s()->load_stack['local']['resources'] as $type => $files )
			{
				if( !in_array( $type, $this->restricted ) ) foreach ( $files as $f )
				{
					$this->copy_resource( $f, str_replace( $this->input, $this->output, $f ));
				}
			}
	
			// Write index file
			file_put_contents( $this->output.'index.html', $views );
					
			// Generate zip file name
			$zip_file = $o_output.'www'.$locale.'.zip';
			
			elapsed('Creating ZIP file: '.$zip_file);
			
			// Create zip archieve			
			$zip = new \ZipArchive;				
			if ($zip->open($zip_file, \ZipArchive::CREATE) === true)
			{
				foreach (\samson\core\File::dir($this->output) as $file ) $zip->addFile( $file, str_replace($this->output, '', $file));
				
				$zip->close();
			}
			else elapsed('Cannot create zip file');		

			/*
			// If email module is loaded
			if( $email = m('email') && sizeof($this->recipients))
			{
				// Generate notification message
				$message = $this->project_name($project_name)->locale($locale)->output('email');
				$title = 'Generated HTML snapshot for '.$project_name.($locale != \samson\core\SamsonLocale::DEF ? '('.$locale.')' : '');
							
				elapsed('Sending notification E-mail to '.$this->recipients[0]);
				
				$email
					->from('htmlgenerator@samsonos.com','HTML Generator')
					->to($this->recipients)					
					->message($message)
					->subject($title)
					->attach($zip_file)
				->send();				
			}*/
		}		
	}

    /** Callback for CSS url rewriting */
    public function src_replace_callback( $matches )
    {
        // If pattern element is found
        if( isset( $matches[2]) )
        {
            // Change path
            $url = ltrim (str_replace('../','', $matches[2]), '/');

            return 'url("'.$url.'")';
        }
    }
	
	/**	@see ModuleConnector::init() */
	public function init( array $params = array() )
	{	
		// Register view renderer
		s()->renderer( array( $this, 'renderer') );		
		
		// Вызовем родительский метод
		parent::init( $params );				
	}	
	
	/** Default controlller */
	public function __BASE()
	{		
		// Perform compression
		$this->compress();			
		$this->view('index');			
	}
}