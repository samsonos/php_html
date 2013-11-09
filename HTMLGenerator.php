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
	public $restricted = array( 'php', 'vphp', 'buildpath', 'setting', 'project', 'htaccess');
	
	/**
	 * Core render handler for including CSS and JS resources to html
	 * 
	 * @param sting $view View content
	 * @param array $data View data
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
				mkdir( $dir_path, 0755, true );
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
			
			// Создадим папку для свернутого сайта
			if( !file_exists($this->output)) mkdir( $this->output, 0775, true );
			
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
			
			// Collection of existing generated views
			$views = '<h1>Список страниц:</h1>';
			
			// Files array
			$files = array();
						
			// Iterate generated pages
			foreach ( \samson\core\File::dir($pages_path,'html',null,$files,0) as $f) 
			{
				// Get just file name
				$view_path = basename($f);
				
				// Copy file
				$this->copy_resource( $this->input.$f, $this->output.$view_path );			
				
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