#!/usr/bin/php -q
<?

define(DS, DIRECTORY_SEPARATOR);

class ExtbaseHelper {
	protected $configuration = null;
	protected $extPath = null;
	
	public function __construct() {
		date_default_timezone_set('Europe/Berlin');
		$this->extPath 		= dirname(__FILE__);
		$this->templatePath = $this->extPath . DS . 'templates' . DS;
		
		if (file_exists('extbase_helper.json')) {
			$this->configuration = json_decode(file_get_contents('extbase_helper.json'));
		}
		
		$action		= $_SERVER['argv'][1];
		$component	= $_SERVER['argv'][2];
		
		switch ($action) {
			case 'create':
				switch ($component) {
					case 'controller':
						$this->showHelp($action.'_'.$component);
					break;
					case 'extension':
						if ($_SERVER['argc'] == 7) {
							$this->createExtension($_SERVER['argv'][3], $_SERVER['argv'][4], $_SERVER['argv'][5], $_SERVER['argv'][6]);
						} else {
							$this->showHelp($action.'_'.$component);
						}
					break;
					case 'model':
						if ($_SERVER['argc'] == 4) {
							$this->createModel($_SERVER['argv'][3]);
						} else {
							$this->showHelp($action.'_'.$component);
						}
					break;
					case 'repository':
						$this->showHelp($action.'_'.$component);
					break;
				}
				$this->showHelp($action);
			break;
		}
		$this->showHelp();
	}
	
	protected function createExtension($extkey, $exttitle = '', $author_name = '', $author_email = '') {
		$basePath = getcwd() . DS . $extkey . DS;
		if (file_exists($basePath)) {
			throw new Exception("Folder '{$extkey}' already exists");
		}
		mkdir($basePath);
		
		$pathes = array(
			'Classes' => array(
				'Controller',
				'Domain' => array(
					'Model',
					'Repository',
					'Validator',
				),
				'Service',
				'ViewHelper',
			),
			'Configuration' => array(
				'TCA',
				'TypoScript',
			),
			'Resources' => array(
				'Private' => array(
					'Language',
					'Layouts',
					'Partials',
					'Scripts',
					'Templates',
				),
				'Public' => array(
					'Icons',
				),
			),
			'Tests' => array(
				'Controller',
				'Domain' => array(
					'Model',
					'Repository',
				)
			)
		);
		
		$this->createPathes($basePath, $pathes);
		
		file_put_contents($basePath.'extbase_helper.json', json_encode(array(
			'extkey'	=> $extkey,
			'exttitle'	=> $exttitle,
			'author'	=> array(
				'name'	=> $author_name,
				'email'	=> $author_email
			)
		)));
		
		$this->marker = array(
			'date'		=> strftime('%Y-%m-%d'),
			'author'	=> $author_name,
			'email'		=> $author_email,
			'extkey'	=> $extkey,
			'ext_title'	=> $exttitle,
		);
		
		$files = array(
			'ChangeLog',
			'ext_emconf.php',
			'ext_icon.gif',
			'ext_localconf.php',
			'ext_tables.php',
			'ext_tables.sql',
			'README.txt'
		);
		
		foreach ($files as $file) {
			$this->copyFile($file, $basePath);
		}
		exit;
	}

	protected function createModel($model) {
		if ($this->configuration == null) {
			throw new Exception('no extbase_helper.json file found, it looks you are in the wrong folder or this extension was not created with EXT:extbase_helper');
		}
		$model = ucfirst($model);
		$basePath = getcwd() . DS . 'Classes' . DS . 'Domain' . DS . 'Model' . DS;
		if (file_exists($basePath.$model.'.php')) {
			throw new Exception('file "'.$model.'.php" found, it looks you have already a model with this name');
		}
		
		$content = file_get_contents($this->templatePath . 'Classes' . DS . 'Domain' . DS . 'Model' . DS . 'Model.php');
		
		$this->marker = array(
			'date'		=> strftime('%Y-%m-%d'),
			'year'		=> strftime('%Y'),
			'author'	=> $this->configuration->author->name,
			'email'		=> $this->configuration->author->email,
			'extkey'	=> $this->configuration->extkey,
			'ext_title'	=> $this->configuration->exttitle,
			'extkeyUP'	=> ucfirst($this->configuration->extkey),
			'model'		=> $model
		);
		
		foreach ($this->marker as $marker => $replace) {
			$content = str_replace('%'.strtoupper($marker).'%', $replace, $content);
		}
		file_put_contents($basePath.$model.'.php', $content);
		exit;
	}
	
	protected function copyFile($file, $basePath) {
		$content = file_get_contents($this->templatePath.$file);
		foreach ($this->marker as $marker => $replace) {
			$content = str_replace('%'.strtoupper($marker).'%', $replace, $content);
		}
		file_put_contents($basePath.$file, $content);
	}
	
	protected function createPathes($basePath, $pathes) {
		foreach ($pathes as $path => $value) {
			if (is_string($value)) {
				mkdir($basePath.DS.$value);
			} else {
				mkdir($basePath.DS.$path);
				$this->createPathes($basePath.DS.$path, $value);
			}
		}
	}
	
	protected function showHelp($key = '') {
		$helpDir = $this->extPath . DS . 'help' . DS;
		if (strlen($key) == 0) {
			$content = file_get_contents($helpDir.'00_Intro.txt');
		} else {
			$content = file_get_contents($helpDir.$key.'.txt');
		}
		
		echo $content;
		exit;
	}
}

$extbaseHelper = new ExtbaseHelper();

?>