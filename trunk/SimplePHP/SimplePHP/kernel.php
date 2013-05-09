<?php
namespace SimplePHP;
define('ISCLI', PHP_SAPI === 'cli');
spl_autoload_register('\SimplePHP\Util::loadClass');

Util::init();

class Util {
	private static $scriptBeginTime;
	public static $classMapping;
	
	public static function init() {
		self::$scriptBeginTime = microtime(true);
		self::$classMapping = array();
	}
	
	public static function timeused() {
		return microtime(true) - self::$scriptBeginTime;
	}
	
	public static function strStartWith($str, $find) {
	    return strpos($str, $find) === 0;
	}
	
	// 字符串是否以什么结尾
	public static function strEndsWith($str, $find) {
	    $length = strlen($find);
	    $start  = $length * -1; //negative
	    return (substr($str, $start) === $find);
	}
	
	public static function loadClass($className) {
		if (array_key_exists($className, self::$classMapping)) {
			$classFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . self::$classMapping[$className];
		} else {
			$classFile = __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
		}
		if (is_file($classFile)) {
	        require_once($classFile);
	    }
	}
	
	public static function loadClassFromFile($filename, $classes) {
		if (is_array($classes)) {
			foreach ($classes as $class) {
				self::$classMapping[$class] = $filename;
			}
		} else {
			self::$classMapping[$classes] = $filename;
		}
	}
}

// 模板处理类
class Template {
	private static $phpCodes;
	public static function replaceCallbackShortTag($matches) {
		$content = $matches[2];
		$mods = array('r'=>'_REQUEST', 'p'=>'_POST', 'g'=>'_GET', 'srv'=>'_SERVER', 's'=>'_SESSION', 'c'=>'_COOKIE', 'e'=>'_ENV', 'glb'=>'GLOBALS');
		if (array_key_exists($matches[1], $mods)) {
			//$matches[2]=testobj.proper:value ==> _REQUEST['testobj']->proper['value'];
			//$matches[2]=abc:name1.aaa ==> _REQUEST['abc']['name1']->aaa;
			$content = preg_replace('/^([^:.]+)/', $mods[$matches[1]]."['$1']", $content);
		}
		if (strpos($content, '?') === false && strpos($content, '(') === false) { // avoid express of 'a==b?1:2' and call function
			$content = str_replace('.', '->', $content);
			$content = preg_replace('/:([^:]+)/', "['$1']", $content);
		}
		return "<?=".( (strpos($content, '(') === false && !\SimplePHP\Util::strStartWith($content, '\\')) ? '$' : '' ). str_replace(array('::leftquote::','::rightquote::'), array('{','}'), $content)."?>";
	}


	public static function replaceCallbackControlTag($matches) {
		$size = count($matches);
		if ('elseif' == $matches[1]) {
			return "<?php } ".$matches[1]." (".str_replace("''", '"', $matches[2]).") { ?>";
		} else {
			return "<?php ".$matches[1]." (".str_replace("''", '"', $matches[2]).") { ?>";
		}
	}

	public static function executeViewlet($viewletName, $params=null) {
		$viewletObj = \SimplePHP\Services::get('viewlet.'.$viewletName, false);
		\SimplePHP\Services::populateRequest($viewletObj);
		if (isset($params)) {
			$props = array();
			$params = explode(';', $params);
			foreach ($params as $param) {
				$paramParts = explode('=', $param);
				if (strpos($paramParts[1], '$') == 0) {
					eval('$tmpvalue=$paramParts[1];');
					$props[$paramParts[0]] = $tmpvalue;
				} else {
					$props[$paramParts[0]] = $paramParts[1];
				}
			}
			\SimplePHP\Services::populateProps($viewletObj, $props);
		}
		$ref = \SimplePHP\Services::reflector($viewletObj);
		$doccmt = $ref->getMethod('execute')->getDocComment();
		$beAnnoCallbks = \SimplePHP\Services::getBeforeAnnotationCallbacks();
		foreach ($beAnnoCallbks as $matchRegu => $callbackFunc) {
			if (function_exists($callbackFunc) && preg_match($matchRegu, $doccmt, $matches)) {
				$matches['json'] = false;
				$callbackFunc($matches);
			}
		}
		$view = $viewletObj->execute();
		if (substr($view, 0, 1) != '/') $view = '/viewlet/'.$view;
		\SimplePHP\Template::processTemplate($view, $viewletObj);
	}

	public static function replaceCallbackViewletTag($matches) {
		$size = count($matches);
		$r = '<?php $viewletName = \''.str_replace("'","\\'",$matches[1]).'\'; eval("\$viewletName=\"$viewletName\";"); ';
		if ($size == 4) {
			$r .= '$paramstr = \''.$matches[3].'\'; eval("\$paramstr=\"$paramstr\";"); ';
		} else {
			$r .= '$paramstr = null; ';
		}
		return $r.'\SimplePHP\Template::executeViewlet($viewletName, $paramstr); ?>';
	}

	public static function replaceCallbackPhpTag($matches,$tplNameKey) {
		if (!isset(self::$phpCodes)) self::$phpCodes=array();
		self::$phpCodes[$tplNameKey][] = $matches[0];
		return '[__simplephp_code'.$tplNameKey.count(self::$phpCodes[$tplNameKey]).']';
	}


	public static function processTemplate($tplName, $currObj=NULL, $reflector=NULL) {
		if (is_object($currObj) && ($reflector == NULL)) $reflector = Services::reflector($currObj);
		if (substr($tplName, 0, 1) != '/') $tplName = '/'.$tplName;
		$tplName = str_replace('.', '/', $tplName);
		$tpl = __DIR__.'/view'.$tplName.'.php';
		$tplCache = __DIR__.'/view_cache'.$tplName.'.php';
		$tplNameKey = str_replace('/','_',$tplName);
		if (!file_exists($tplCache) || (file_exists($tplCache) && (filemtime($tpl) > filemtime($tplCache)))) {
			// update template cache
			$tplCacheContent = @file_get_contents($tpl);
			$tplCacheContent = str_replace('<highlighthtml>', '<code class="prettyprint"><?php ob_start(); ?>', $tplCacheContent);
			$tplCacheContent = str_replace('</highlighthtml>', '<?php $buffer=ob_get_contents();ob_end_clean();echo htmlspecialchars($buffer); ?></code>', $tplCacheContent);
			$tplCacheContent = str_replace('<highlight>', '<?php ob_start(); ?>', $tplCacheContent);
			$tplCacheContent = str_replace('</highlight>', '<?php $buffer="<?php\n".ob_get_contents()."?>";ob_end_clean();highlight_string($buffer); ?>', $tplCacheContent);
			$tplCacheContent = preg_replace_callback('/<code\s(.+)<\/code>/sU', function ($matches) use ($tplNameKey) {return \SimplePHP\Template::replaceCallbackPhpTag($matches,$tplNameKey);}, $tplCacheContent);
			$tplCacheContent = preg_replace_callback('/<\?php(.+)\s\?>/sU', function ($matches) use ($tplNameKey) {return \SimplePHP\Template::replaceCallbackPhpTag($matches,$tplNameKey);}, $tplCacheContent);
			//$tplCacheContent = str_replace(array('\{','\}'), array('::leftquote::','::rightquote::'), $tplCacheContent);
			$tplCacheContent = preg_replace_callback('/[\t\s]*<(if|foreach|for|elseif|while|switch)[\s]+exp=["]([^"]+)["][\s]*\/>/', '\\SimplePHP\\Template::replaceCallbackControlTag', $tplCacheContent);
			$tplCacheContent = preg_replace('/[\t\s]*<(end)\/>/', "<?php } ?>", $tplCacheContent);
			$tplCacheContent = preg_replace('/[\t\s]*(<else\/>)/', "<?php } else { ?>", $tplCacheContent);
			$tplCacheContent = preg_replace('/[\t\s]*<(continue|break)\/>/', "<?php $1; ?>", $tplCacheContent);
			$tplCacheContent = preg_replace('/[\t\s]*<case[\s]+value=["]([^"]+)["][\s]*\/>/', "<?php case $1: ?>", $tplCacheContent);
			$tplCacheContent = preg_replace('/[\t\s]*<(default)\/>/', "<?php $1: ?>", $tplCacheContent);
			$tplCacheContent = preg_replace('/[\t\s]*<setvar[\s]+name=["]([^"]+)["][\s]+value=["]([^"]+)["][\s]*\/>/', '<?'.'php \$$1=$2; ?'.'>', $tplCacheContent);
			$tplCacheContent = preg_replace('/[\t\s]*<tpl[\s]+page=["]([^"]+)["][\s]*\/>/', "<?php \\SimplePHP\\Template::processTemplate('$1'); ?>", $tplCacheContent);
			$tplCacheContent = preg_replace('/[\t\s]*<const[\s]+([\w]+)[\s]*\/>/', "<?php echo $1; ?>", $tplCacheContent);
			$tplCacheContent = preg_replace_callback('/[\t\s]*<viewlet[\s]+name=["]([^"]+)["]([\s]+params=["]([^"]+)["])?[\s]*\/>/', '\\SimplePHP\\Template::replaceCallbackViewletTag', $tplCacheContent);
			$tplCacheContent = str_replace('<timeused/>', '<?=sprintf(\'%.4f\', \\SimplePHP\\Util::timeused())?>', $tplCacheContent);
			$hackers = Services::getHackers('template');
			foreach ($hackers as $hacker) {
				$tplCacheContent = $hacker($tplCacheContent);
			}
			
			$tplCacheContent = preg_replace_callback('|\{(\w+)?\$([^}]+)\}|', '\\SimplePHP\\Template::replaceCallbackShortTag', $tplCacheContent);
			$tplCacheContent = preg_replace('/\{(\w+)?\\\\\$/', '{${1}$', $tplCacheContent);
			
			if (isset(self::$phpCodes) && is_array(self::$phpCodes) && is_array(self::$phpCodes[$tplNameKey])) {
				foreach (self::$phpCodes[$tplNameKey] as $key => $value) {
					$tplCacheContent = str_replace('[__simplephp_code'.$tplNameKey.($key+1).']', $value, $tplCacheContent);
				}
			}
		
			$cachePath = substr($tplCache, 0, strrpos($tplCache, '/'));
			if (!is_dir($cachePath)) if ( ! @mkdir($cachePath, 0755, true) ) exit('Error: Unable to creat cache dir -> '.$cachePath);
			if ( @file_put_contents($tplCache, $tplCacheContent) === FALSE) exit('Error: Unable to write cache file -> '.$tplCache);;
		}
		
		// export request vars
		foreach ($_REQUEST as $key => $value) {
			if(!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/',$key)) continue;
			eval("\$$key = \$value;");
			//eval("\$$key = \$_REQUEST['$key'];");
		}
		
		// export some global vars
		if (isset($GLOBALS['globaExportVars'])) {
			foreach ($GLOBALS['globaExportVars'] as $key) {
				if(!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/',$key)) continue;
				eval("\$$key = \$GLOBALS['$key'];");
			}
		}
		
		if (is_object($currObj)) {
			// other params by request
			if (is_array($currObj->_other_params)) {
				foreach ($currObj->_other_params as $key => $value) {
					if(!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/',$key)) continue;
					eval("\$$key = \$value;");
				}
			}
			// populate const in this object
			$consts = $reflector->getConstants();
			foreach ($consts as $key => $value) {
				if(!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/',$key)) continue;
				eval("\$$key = \$value;");
			}
			// only populate public properties
			$props = $reflector->getProperties(\ReflectionProperty::IS_PUBLIC);
			foreach ($props as $prop) {
				$propName = $prop->getName();
				$propValue = $prop->getValue(($currObj instanceof AOP) ? $currObj->__get_real_instance() : $currObj);
				eval("\$$propName = \$propValue;");
			}
			
			// finally we populate the action object it self, then it's functions can be accessed from view.
			$actObj = $currObj;
		}
		
		include $tplCache;
	}

}

class AOP {
	private $instance;
	private $transactionManager;
	private static $trans;
	
	public function __construct(&$instance, &$transactionManager) {
		$this->instance = $instance;
		$this->transactionManager = $transactionManager;
	}
	
	public function __call($method, $arguments) {
		if (!method_exists($this, $method) && method_exists($this->instance, $method)) {
			$reflector = Services::reflector($this->instance);
			$doccmt = $reflector->getMethod($method)->getDocComment();
			if (preg_match('/@transaction/', $doccmt, $matches)) {
				try {
					if (empty(self::$trans) && isset($this->transactionManager)) {
						$this->transactionManager->beginTransaction();
						self::$trans = array($this->instance, $method);
					}
					$rt = call_user_func_array(array($this->instance, $method), $arguments);
					if (isset(self::$trans) && isset($this->transactionManager)) {
						if (self::$trans[0] == $this->instance && self::$trans[1] == $method) {
							$this->transactionManager->commit();
						}
					}
				} catch (Exception $e) {
					if (isset(self::$trans) && isset($this->transactionManager)) {
						if (self::$trans[0] == $this->instance && self::$trans[1] == $method) {
							$this->transactionManager->rollback();
						}
					}
					throw $e;
				}
			} else {
				$rt = call_user_func_array(array($this->instance, $method), $arguments);
			}
			
			return $rt;
		} else {
			throw new \RuntimeException($this->__get_real_class() . ' no such method: ' . $method);
		}
	}
	
	public function __get_real_class() {
		return get_class($this->instance);
	}
	
	public function __get_real_instance() {
		return $this->instance;
	}
}

// action方法返回此对象表示执行一个重定向
class NFRedirect {
	private $url;
	
	public function __construct($url) {
		$this->url = $url;
	}
	
	public function getUrl() {
		return $this->url;
	}
}

class NFViewPath {
	private $path;
	
	public function __construct($path) {
		$this->path = $path;
	}
	
	public function getPath() {
		return $this->path;
	}
}

// 经常用在prehander中，返回该对象表示执行完不再执行Action
class NFSkipAction {
}

/**
* 表示一个类是否可配置的，当类实现此接口，框架会自动调用config方法。
*/
interface IConfigurable {

	/**
	* 配置类实例。
	* @param $config IConfiguration实例
	*/
	public function config($config);
}

/**
* 设置或读取系统配置。
*/
interface IConfiguration {

	/**
	* 读取系统配置。
	* @param $key 配置项
	*/
	public function get($key, $defaultValue=null);
	
	/**
	* 设置系统配置。
	* @param $key 配置项
	* @param $value 配置值
	*/
	public function set($key, $value);
	
	/**
	* 检查是否存在某项配置。
	*/
	public function has($key);
}

interface ITransactionManager {
	public function beginTransaction();
	
	public function commit();
	
	public function rollback();
	
	public function inTransaction();
}

interface IActionMethodHelper {
	public function getActionMethod();
}


interface IServiceCallerAware {
	public function setCallerInfo($obj);
}

interface ITemplateEngine {
	public function processTemplate($tplName, $currObj=NULL, $reflector=NULL);
}

class SmartyTemplate implements ITemplateEngine {
	private $smarty;
	private $tplFileExtension;
	public function __construct($smarty, $tplFileExtension) {
		$this->smarty = $smarty;
		$this->tplFileExtension = $tplFileExtension;
	}
	public function processTemplate($tplName, $currObj=NULL, $reflector=NULL) {
		if (is_object($currObj) && ($reflector == NULL)) $reflector = Services::reflector($currObj);
		if (substr($tplName, 0, 1) == '/') $tplName = substr($tplName,1);
		$tplName = str_replace('.', '/', $tplName);
		$tpl = $tplName.'.'.$this->tplFileExtension;
		
		// export request vars
		foreach ($_REQUEST as $key => $value) {
			if(!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/',$key)) continue;
			$this->smarty->assign($key, $value);
		}
		
		// export some global vars
		if (isset($GLOBALS['globaExportVars'])) {
			foreach ($GLOBALS['globaExportVars'] as $key) {
				if(!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/',$key)) continue;
				$this->smarty->assign($key, $GLOBALS[$key]);
			}
		}
		
		if (is_object($currObj)) {
			// other params by request
			if (is_array($currObj->_other_params)) {
				foreach ($currObj->_other_params as $key => $value) {
					if(!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/',$key)) continue;
					$this->smarty->assign($key, $value);
				}
			}
			// populate const in this object
			$consts = $reflector->getConstants();
			foreach ($consts as $key => $value) {
				if(!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/',$key)) continue;
				$this->smarty->assign($key, $value);
			}
			// only populate public properties
			$props = $reflector->getProperties(\ReflectionProperty::IS_PUBLIC);
			foreach ($props as $prop) {
				$propName = $prop->getName();
				$propValue = $prop->getValue(($currObj instanceof AOP) ? $currObj->__get_real_instance() : $currObj);
				$this->smarty->assign($propName, $propValue);
			}
			
			// finally we populate the action object it self, then it's functions can be accessed from view.
			$this->smarty->assign('actObj', $currObj);
		}
		
		$this->smarty->display($tpl);
	}
}

/**
* 系统默认的配置实现，基于内存数组。
*/
class DefaultConfiguration implements IConfiguration {
	private $conf;
	public function __construct() {
		$this->conf = array();
	}
	
	public function set($key, $value) {
		$this->conf[$key] = $value;
	}
	
	public function get($key, $defaultValue=null) {
		return $this->has($key) ? $this->conf[$key] : $defaultValue;
	}
	
	public function has($key) {
		return array_key_exists($key, $this->conf);
	}
}

/////////// Services is service class instance manager //////////
// all service class is singletone
class Services {
	private static $instances;
	private static $reflectors = array();
	private static $transactionManager;
	private static $config;
	private static $implements = array();
	private static $hackers = array();
	private static $templateEngine;
	
	public static function init() {
		self::$config = new DefaultConfiguration();
		self::$hackers['template'] = array();
	}
	
	public static function __callStatic($name, $args) {
		return call_user_func_array(array(ActionHandler::getInstance(), $name), $args);
	}
	
	public static function setTemplateEngine($engine) {
		self::$templateEngine = $engine;
	}
	
	public static function getTemplateEngine() {
		return self::$templateEngine;
	}
	
	public static function processTemplate($tplName, $currObj=NULL, $reflector=NULL) {
		$tplEngine = self::getTemplateEngine();
		if ($tplEngine) {
			$tplEngine->processTemplate($tplName, $currObj, $reflector);
		} else {
			Template::processTemplate($tplName, $currObj, $reflector);
		}
	}
	
	public static function addHacker($type, $handler) {
		array_push(self::$hackers[$type], $handler);
	}
	
	public static function getHackers($type) {
		return self::$hackers[$type];
	}
	
	public static function loadClassFromFile($filename, $classes) {
		Util::loadClassFromFile($filename, $classes);
	}
	
	public static function setConfiguration($configClassName) {
		self::$config = self::get($configClassName);
	}
	
	public static function getConfiguration() {
		return self::$config;
	}
	
	public static function loadConfig($configArray) {
		foreach ($configArray as $key => $value) {
			self::$config->set($key, $value);
		}
	}
	
	/**
	* 设置事务管理类。
	* @param $transactionManagerClass 事务管理类
	*/
	public static function setTransactionManagerClass($transactionManagerClass) {
		$transMgr = Services::get($transactionManagerClass);
		if (!empty($transMgr)) {
			self::$transactionManager = $transMgr;
		}
	}
	
	public static function alias($interfaceName, $implementClass, $singletone=true) {
		self::$implements[$interfaceName] = array($implementClass, $singletone);
	}
	
	public static function getImplement($interfaceName) {
		return (array_key_exists($interfaceName, self::$implements)) ? self::$implements[$interfaceName] : false;
	}
	
	/**
	* 根据类名或别名获取类实例。
	* @param $serviceClassName 类名
	* @param $singletone 是否启用单实例
	*/
	public static function get($serviceClassName, $singletone=true, $caller=null) {
		$realClassName = self::getImplement($serviceClassName);
		if ($realClassName) {
			$serviceClassName = $realClassName[0];
			$singletone = $realClassName[1];
		}
		$serviceClassName = '\\service\\'.str_replace('.', '\\', $serviceClassName);
		if (!class_exists($serviceClassName)) exit('Fatal: class not found: '.$serviceClassName);
		if ($singletone) {
			if (!is_array(self::$instances)) self::$instances = array();
			if (array_key_exists($serviceClassName, self::$instances)) {
				$instance = self::$instances[$serviceClassName];
			} else {
				$instance = new $serviceClassName();
				if (isset(self::$transactionManager)) {
					$instance = new AOP($instance, self::$transactionManager);
				}
				
				self::$instances[$serviceClassName] = $instance;
			}
		} else {
			$instance = new $serviceClassName();
			if (isset(self::$transactionManager)) {
				$instance = new AOP($instance, self::$transactionManager);
			}
		}
		if ($instance instanceof IConfigurable) {
			$instance->config(self::$config);
		}
		if ($caller!=null && $instance instanceof IServiceCallerAware) {
			$instance->setCallerInfo($caller);
		}
		self::injectService($instance);
		return $instance;
	}
	
	/**
	* 根据类名或实例获取类反射器。
	* @param $mix 类名或者实例
	* @param $cached 是否启用缓存
	* @return 反射器对象
	*/
	public static function reflector($mix, $cached=true) {
		$className = $mix;
		if (is_object($mix)) {
			if ($mix instanceof AOP) {
				$className = $mix->__get_real_class();
			} else {
				$className = get_class($mix);
			}
		}
		//$className = is_object($mix) ? get_class($mix) : $mix;
		if ($cached) {
			//if (!is_array(self::$reflectors)) self::$reflectors = array();
			if (array_key_exists($className, self::$reflectors)) {
				$reflector = self::$reflectors[$className];
			} else {
				$reflector = new \ReflectionClass($className);
				self::$reflectors[$className] = $reflector;
			}
		} else {
			$reflector = new \ReflectionClass($className);
		}
		return $reflector;
	}
	
	public static function injectService($obj, $reflector=NULL) {
		if ($reflector==NULL) $reflector = self::reflector($obj);
		$props = $reflector->getProperties();
		foreach ($props as $prop) {
			$c = $prop->getDocComment();
			// handle @ServiceClass
			//echo $c;
			if (preg_match('/@service\(([\w\.]+)\)/', $c, $matches)) {
				$prop->setAccessible(true);
				if ($obj instanceof AOP) {
					$prop->setValue($obj->__get_real_instance(), Services::get($matches[1], true, $obj));
				} else {
					$prop->setValue($obj, Services::get($matches[1], true, $obj));
				}
				$prop->setAccessible(false);
			}
			//var_dump($matches);
		}
	}
	
	public static function populateProps(&$obj, $props) {
		//var_dump($obj);
		//var_dump($props);
			$reflector = self::reflector($obj);
			//if ($obj instanceof AOP) var_dump($reflector);
			// populate action fields
			$unUsedProps = array();
			foreach ($props as $key => $value) {
				$found = false;
				//echo $key.'<br/>';
				if (strpos($key, '-') !== false) { // user-name=zhangsan 这里对象与属性之间只能使用‘-’分隔，而不能使用‘.’，因为php会自动将‘.’转换为‘_’
					$props = explode('-', $key);
					if (count($props) == 2) { // current we only process two parts
						$propObj = $props[0];
						$propName = $props[1];
						if ($reflector->hasProperty($propObj)) {
							$p = $reflector->getProperty($propObj)->getValue(($obj instanceof AOP) ? $obj->__get_real_instance() : $obj);
							$p->$propName = $value;
							$found = true;
						}
					}
				} else if ($reflector->hasProperty($key)) {
					if ($obj instanceof AOP) {
						$obj2 = $obj->__get_real_instance();
						$obj2->$key = $value;
						//var_dump($obj2);
					} else {
						$obj->$key = $value;
					}
					$found = true;
				}
				if (!$found) {
					$unUsedProps[$key] = $value;
				}
			}
			// not populated props will dynamic set to action object as properties
			if ($obj instanceof AOP) {
				$obj2 = $obj->__get_real_instance();
				$obj2->_other_params = $unUsedProps;
			} else {
				$obj->_other_params = $unUsedProps;
			}
	}
	
	public static function populateRequest(&$obj) {
			self::populateProps($obj, $_REQUEST);
			return self::populateProps($obj, $_FILES);
	}
}

// ActionHandler的监听接口，Action类通过监听此接口来获取ActionHandlerInfo对象
interface IActionHandlerInfoListenser {
	public function setActionHandlerInfo($actionHandlerInfo);
}

// ActionHandler基本信息处理获取类
class ActionHandlerInfo {
	private $actionHandler;
	
	public function __construct($actionHandler) {
		$this->actionHandler = $actionHandler;
	}
	
	public function getActionMethod() {
		return $this->actionHandler->actionMethod;
	}
	
	public function getReqPath() {
		return $this->actionHandler->reqPath;
	}
	
	public function getViewPath() {
		return $this->actionHandler->viewPath;
	}
	
	public function getActionClass() {
		return $this->actionHandler->actionClass;
	}
	
	public function getActionPrepend() {
		return $this->actionHandler->actionPrepend;
	}
	
	public function getView() {
		return $this->actionHandler->view;
	}
}

class SimpleXMLExtended extends \SimpleXMLElement 
{ 
  public function addCData($cdata_text) 
  { 
    $node= dom_import_simplexml($this); 
    $no = $node->ownerDocument; 
    $node->appendChild($no->createCDATASection($cdata_text)); 
  } 
}

class ActionHandler {
	public $reqPath;
	public $viewPath;
	public $actionClass;
	public $actionMethod;
	public $actionObj;
	public $actionReflector;
	public $actionPrepend;
	public $view;
	public $contentType;
	protected $isJson;
	protected $isXML;
	protected $xmlRoot;
	protected $xmlCDataKeys;
	public $doccmt; // doc comment for action method
	public $clscmt; // doc comment for action class
	
	public $defaultAction;
	
	private static $instance;
	private $badRequestRedirect;
	
	private $beforeAnnotationCallbacks;
	private $afterAnnotationCallbacks;
	private $filters;
	
	// /api/{apiKey}/{actionMethod}/{orderBy}/{category}/{compatible}/{page}
	private $routes;
	// /api/
	private $enableRoute = true;
	private $uri;
	private $failedExit;
	
	public static function getInstance($prepend = '\\action') {
		if (self::$instance) return self::$instance;
		self::$instance = new ActionHandler($prepend);
		return self::$instance;
	}
	
	public function setDefaultAction($actionUrl) {
		$this->defaultAction = $this->actionPrepend.'\\'.$actionUrl;
	}
	
	public function addBeforeAnnotationCallback($matchRegu, $callbackFunc) {
		$this->beforeAnnotationCallbacks[$matchRegu] = $callbackFunc;
	}
	
	public function getBeforeAnnotationCallbacks() {
		return $this->beforeAnnotationCallbacks;
	}
	
	public function addAfterAnnotationCallback($matchRegu, $callbackFunc) {
		$this->afterAnnotationCallbacks[$matchRegu] = $callbackFunc;
	}
	
	public function addFilter($matchRegu, $handlerClass) {
		$this->filters[$matchRegu] = Services::get($handlerClass, false);
	}
	
	public function addRoute($rule, $path) {
		$this->setEnableRoute(true);
		$this->routes[$rule] = $path;
	}
	
	public function getUri() {
		return $this->uri;
	}
	
	public function setBadRequestRedirect($page) {
		$this->badRequestRedirect = $page;
	}
	
	public function setEnableRoute($enabled = true) {
		$this->enableRoute = $enabled;
	}
	
	private function basePath($uri) {
		$p = strpos($uri, '?');
		$rs = ($p === false) ? $uri : substr($uri, 0, $p);
		if ($rs != '/' && $_SERVER["PHP_SELF"] != '/index.php') {
			$p2 = strrpos($_SERVER["PHP_SELF"], '/');
			$rs = substr($rs, $p2);
		}
		return $rs;
	}
	
	public function __construct($prepend = '\\action', $failedExit=true, $urlPrefix=null) {
		$this->actionPrepend = $prepend;
		$this->failedExit = $failedExit;
		$this->uri = empty($_GET['spf_action']) ? $this->basePath($_SERVER["REQUEST_URI"]) : $_GET['spf_action'];
		$this->beforeAnnotationCallbacks = array();
		$this->afterAnnotationCallbacks = array();
		$this->routes = array();
		$this->filters = array();
		$this->enableRoute = false;
		$this->defaultAction = '\\action\\homepage';
		if ($urlPrefix) {
			$this->setActionUrlPrefix($urlPrefix);
		}
	}
	
	private function parse($reqPath, $failedExit=true) {
		$this->reqPath = $reqPath;
		if ($this->reqPath == '/') {
			$this->actionClass = $this->defaultAction;
			$this->actionMethod = 'execute';
			$this->viewPath = '/';
			$this->actionReflector = Services::reflector($this->actionClass);
		} else {
			if (Util::strEndsWith($this->reqPath, '/')) {
				$this->reqPath = substr($this->reqPath, 0, count($this->reqPath)-1);
			}
			$parts = explode('/', str_replace('//', '/', $this->reqPath));
			$partsCount = count($parts);
			$flag = 1;
			if ($partsCount == 2) { // /actionName
				$this->actionClass = $this->actionPrepend.str_replace('/', '\\', $this->reqPath);
				$this->actionMethod = 'execute';
				$flag = 2;
			} else {
				$this->actionMethod = array_pop($parts);
				$this->actionClass = $this->actionPrepend.implode('\\', $parts);
				$flag = 2;
			}
			try {
				$this->actionReflector = Services::reflector($this->actionClass);
			} catch (\ReflectionException $e) {
				$this->actionClass = $this->actionPrepend.str_replace('/', '\\', $this->reqPath);
				$this->actionMethod = 'execute';
				$flag = 2;
				try {
					$this->actionReflector = Services::reflector($this->actionClass);
				} catch (\ReflectionException $e2) {
					// really bad request
					if ($this->badRequestRedirect) {
						header('Location: '.$this->badRequestRedirect);
					} else {
						echo 'bad request: '.$this->actionClass; 
						if ($failedExit) die();
					}
				}
			}
			
			for ($i=1;$i<$flag;$i++) array_pop($parts);
			
			// now we get namespace (view path);
			$this->viewPath = implode('/', $parts).'/'; // /demo
		}
		
	
		$class = $this->actionClass;
		$this->actionObj = new $class();
		// populate request fields to action object
		Services::populateRequest($this->actionObj);
		
		// process service injection
		Services::injectService($this->actionObj, $this->actionReflector);
		if ($this->actionObj instanceof IActionMethodHelper) {
			$this->actionMethod = $this->actionObj->getActionMethod();
		}
	}
	
	private function setSimpleValue(&$obj, $paraName, $value) {
		$obj->$paraName = $value;
	}
	
	protected function populate($failedExit=true) {		
		// populate request fields to action object
		Services::populateRequest($this->actionObj);
		
		// process service injection
		Services::injectService($this->actionObj, $this->actionReflector);
	}

	private function escXml($str, $escQuota=false) {	
		$s = htmlspecialchars($str);
		if ($escQuota) $s = str_replace(array('"'), array('&quot;'), $s);
		return $s;
	}

	private function toXML($data, $rootNodeName='data', $xml=null) {
		// turn off compatibility mode as simple xml throws a wobbly if you don't.
		if (ini_get('zend.ze1_compatibility_mode') == 1) {
			ini_set ('zend.ze1_compatibility_mode', 0);
		}
		
		if (is_object($data)) {
			$reflector = Services::reflector($data);
			if ($xml == null) {
				$clscmt = $reflector->getDocComment();
				if (preg_match('/@xmlAlias\(([\w]+)\)/', $clscmt, $matches)) {
					$rootNodeName = $matches[1];
				}
				$xml = new SimpleXMLExtended("<?xml version='1.0' encoding='utf-8'?><$rootNodeName />");
			}
			$props = $reflector->getProperties();
			foreach ($props as $prop) {
				$key = $prop->getName();
				$value = $prop->getValue($data);
				if (is_array($value)) {
					$node = $xml->addChild($key);
					//$this->toXml($value, $key, $node);
					foreach ($value as $key2 => $value2) {
						if (is_numeric($key2)) {
							$reflector2 = Services::reflector($value2);
							$clscmt = $reflector2->getDocComment();
							if (preg_match('/@xmlAlias\(([\w]+)\)/', $clscmt, $matches)) {
								$key2 = $matches[1];
							} else {
								$key2 = $reflector2->getShortName();
							}
						}
						$node2 = $node->addChild($key2);
						$this->toXml($value2, $key2, $node2);
					}
				} else {
					$c = $prop->getDocComment();
					if (preg_match('/@cData/', $c, $matches)) {
						$node = $xml->addChild($key);
						$node->addCData($value);
					} else {
						$xml->addChild($key, $this->escXml($value));
					}
				}
			}
		} else {
 
			if ($xml == null) {
				$xml = new SimpleXMLExtended("<?xml version='1.0' encoding='utf-8'?><$rootNodeName />");
			}
 
			// loop through the data passed in.
			foreach($data as $key => $value) {
				// no numeric keys in our xml please!
				if (is_numeric($key)) {
					$key = "unknownNode_". (string) $key;
				}
	 
				// if there is another array found recrusively call this function
				if (is_array($value)) {
					$node = $xml->addChild($key);
					// recrusive call.
					$this->toXml($value, $rootNodeName, $node);
				} else {
					// add single node.
					if (is_array($this->xmlCDataKeys) && in_array($key, $this->xmlCDataKeys)) {
						$node = $xml->addChild($key);
						$node->addCData($value);
					} else {
						$xml->addChild($key, $this->escXml($value));
					}
				}
			}
		}
		// pass back as string. or simple xml object if you want!
		return $xml->asXML();
	}
	
	public function execute() {
		// filters
		foreach ($this->filters as $rule => $handler) {
			if (preg_match($rule, $this->uri)) {
				Services::populateRequest($handler->__get_real_instance());
				$handler->execute();
			}
		}
		
		if ($this->enableRoute) {
			foreach ($this->routes as $route => $dst) {
				$uri = preg_replace($route, $dst, $this->uri);
				if ($uri != $this->uri) {
					$this->uri = $uri;
					$p = strpos($uri, '?');
					if ($p !== false) {
						$this->uri = substr($uri, 0, $p);
						$paramsStr = substr($uri, $p+1);
						parse_str($paramsStr, $params);
						$_REQUEST = array_merge($params, $_REQUEST);
						$_GET = array_merge($params, $_GET);
					}
					break;
				}
			}
		}
		$this->parse($this->uri);
		//$this->populate($this->failedExit);
		
			

		
		// execute action method
		$func = $this->actionMethod;
		if ($this->actionReflector->hasMethod($func)) {
			// check if content type setted
			$this->doccmt = $this->actionReflector->getMethod($func)->getDocComment();
			if (!preg_match('/@session/', $this->doccmt, $matches)) {
				// 如果Action方法没有标注@session，则在执行Action方法之前立即关闭，
				// 防止因为Sessin锁定导致同浏览器其它页面无法加载
				@session_write_close();
			}
			$this->isJson = false;
			$this->isXML = false;
			if (preg_match('/@contentType\(([^(]*)\)/', $this->doccmt, $matches)) {
				$this->contentType = $matches[1];
			}
			
			if (preg_match('/@json/', $this->doccmt, $matches)) {
				if (!isset($this->contentType)) $this->contentType = 'application/json';
				$this->isJson = true;
			}
			else if (preg_match('/@xml(\(([\w]+)\))?/', $this->doccmt, $matches)) { //@xml(root)
				if (!isset($this->contentType)) $this->contentType = 'text/xml; charset=utf-8';
				$this->isXML = true;
				if (count($matches) == 3) $this->xmlRoot = $matches[2]; else $this->xmlRoot = 'data';
				//xmlCDATAKeys
				if (preg_match('/@xmlCDataKeys\(([\w,]+)\)/', $this->doccmt, $matches)) {
					$this->xmlCDataKeys = explode(',', $matches[1]);
				}
			}
			
			if (!isset($this->contentType)) {
				$this->contentType = 'text/html; charset=utf-8';
			}
			
			if ($this->actionObj instanceof IActionHandlerInfoListenser) {
				$this->actionObj->setActionHandlerInfo(new ActionHandlerInfo($this));
			}
			
			$execAction = true;
			$this->clscmt = $this->actionReflector->getDocComment();
			if (preg_match('/@preHandler\(([\w]+)\)/', $this->clscmt, $matches)) {
				$hdl = $matches[1];
				$ret = $this->actionObj->$hdl();
				
				if (isset($ret)) {
					if ($ret instanceof NFSkipAction) {
						$execAction = false;
					} else {
						$this->view = $ret;
						$this->render();
						return;
					}
				}
			}
			
			foreach ($this->beforeAnnotationCallbacks as $matchRegu => $callbackFunc) {
				if (function_exists($callbackFunc) && preg_match($matchRegu, $this->doccmt, $matches)) {
					$matches['json'] = $this->isJson;
					$matches['xml'] = $this->isXML;
					$callbackFunc($matches);
				}
			}
			
			// send default contentType
			if (!ISCLI && !empty($this->contentType)) {
				header('Content-type: '.$this->contentType);
			}
			
			// execute the action method
			if ($execAction) {
				$this->view = $this->actionObj->$func();
			}
			
			$this->render();
		} else {
			echo 'class: '.$this->actionClass.' has no such method: '.$func; die();
		}
	}
	
	public function render() {
		foreach ($this->afterAnnotationCallbacks as $matchRegu => $callbackFunc) {
			if (function_exists($callbackFunc) && preg_match($matchRegu, $this->doccmt, $matches)) {
				$callbackFunc($matches);
			}
		}
		
		if (preg_match('/@afterHandler\(([\w]+)\)/', $this->clscmt, $matches)) {
			$hdl = $matches[1];
			$ret = $this->actionObj->$hdl();
			
			if (isset($ret)) {
				$this->view = $ret;
				$this->render();
				return;
			}
		}
		
		if ($this->view instanceof NFRedirect) {
			header('Location: ' . $this->view->getUrl());
			exit;
		}
		
		if ($this->isJson) {
			echo json_encode((is_array($this->view) || is_object($this->view)) ? $this->view : array('result'=>$this->view));
		} else if ($this->isXML) {
			echo $this->toXML((is_array($this->view) || is_object($this->view)) ? $this->view : array('result'=>$this->view), $this->xmlRoot);
		} else if ($this->view && is_string($this->view) && strlen($this->view) > 0) {
			if (substr($this->view, 0, 1) !== '/') $this->view = $this->viewPath.$this->view;
			Services::processTemplate($this->view, $this->actionObj, $this->actionReflector);
		}
	}
}

Services::init();
?>