<?php
//$_SESSION['token'] = md5(uniqid(rand(), true));
//debug_backtrace();
//xdebug_start_trace("trace");

	session_start();

	//反向代理
	$host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
	$sub = dirname($_SERVER['PHP_SELF']);

	//如果以cgi运行php，$_SERVER['SCRIPT_NAME']会变成cgi的路径,所以还是PHP_SELF靠谱
	define("WEBROOT","http://$host$sub");
	//echo WEBROOT;
	//var_dump($_SERVER);

	//set_include_path(get_include_path() . PS . ROOT. 'controller'. PS. ROOT. 'model');
 
	$mvc = getMvc();

	//todo: Reflection
	$obj = loadMvc($mvc[0]);

	if(!method_exists ($obj, $mvc[1])) {
		//header("Location: /pages/404.php");
		include(APP."/pages/404.php");
		qlog("method isnt exist: ".$mvc[1]);
		exit;
	}

	if(version_compare(phpversion(), "5.3", ">="))
		$obj($mvc);	//5.3后支持
	else 
		$obj->__invoke($mvc);  

	//debug_print_backtrace();
	//xdebug_call_function();
	//xdebug_stop_trace();
    //xdebug_print_function_stack();

	   //自动加载类文件
	/*
	function __autoload($class_name) {
		$filepath = '';

		if(strstr($class_name, "Controller")) {
			$filepath = APP.DS.'controller'.DS.$class_name . '.php';
		}
		else {
			$filepath = APP.DS.'model'.DS.strtolower($class_name) . '.php';
		}

		if(!file_exists($filepath)) {
			qlog("ERROR",$filepath."not found");
			header("Location: /pages/404.php");
			exit;
		}

		require $filepath;
	}
	*/

class qmvc
{
	function __construct()
	{
	}

	function run()
	{
	}

	function getMvc()
	{
	}
}

	function getRequestUri() 
	{
		if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
			// check this first so IIS will catch
			$requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
		}
		elseif (isset($_SERVER['REDIRECT_URL'])) {
			// Check if using mod_rewrite
			$requestUri = $_SERVER['REDIRECT_URL'];
		}
		elseif (isset($_SERVER['REQUEST_URI'])) {
			$requestUri = $_SERVER['REQUEST_URI'];
		}
		elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
			// IIS 5.0, PHP as CGI
			$requestUri = $_SERVER['ORIG_PATH_INFO'];
			if (!empty($_SERVER['QUERY_STRING'])) {
				$requestUri .= '?' . $_SERVER['QUERY_STRING'];
			}
		}
		return $requestUri;
	}

	function loadView($path)
	{
		
	}

	function loadModel($class)
	{
		$cfile = APP.DS."model".DS.strtolower($class)."_model.php";
		$class = ucfirst(strtolower($class))."Model";
    	if (file_exists($cfile)){
			include($cfile);
		}
		else{
			//header("Location: pages/404.php");
			include(APP."/pages/404.php");
			qlog("class file isnt exist: $cfile");
			exit;
		}
		
		if(!class_exists ($class, false)) {
			//header("Location: /pages/404.php");
			include(APP."/pages/404.php");
			qlog("class isnt exist: $class");
			exit;
		}

		return $obj = new $class();
	}

	function loadMvc($class)
	{
		$cfile = APP.DS."controller".DS.strtolower($class).".php";
    	if (file_exists($cfile)){
			include($cfile);
		}
		else{
			//header("Location: pages/404.php");
			include(APP."/pages/404.php");
			qlog("class file isnt exist: $cfile");
			exit;
		}
		
		$class = ucfirst(strtolower($class));
		if(!class_exists ($class, false)) {
			//header("Location: /pages/404.php");
			include(APP."/pages/404.php");
			qlog("class isnt exist: $class");
			exit;
		}

		//controlle的构造函数里已经new了model
		return  $obj = new $class();
	}


	function E404()
	{
		include(APP."/pages/404.php");
		exit;
	}

function getconfig() {
	$configs = array();
	$tag = null;
	$key = null;
	$value = null;
	
	$file = fopen(ORMFILE, "r") or exit("Unable to open config file!");
	while(!feof($file)) {
		$line =  fgets($file);
		if(!strlen(trim($line)))
			continue;
		elseif($line[0]==';')
			continue;
		elseif($line[0]=='['){
			$tag = strtolower(trim(trim($line), "[]"));
			$configs[$tag] = array();
		}
		else {
			if(strstr($line, "=")){
				$arr = explode("=",$line);
				$key = trim($arr[0]);
				$value = trim($arr[1]);
				
				if($tag) {
					$configs[$tag][$key] = $value;
				}
				else {
					$configs[$key] = $value;
				}
			}
			else {
				echo " **$line** getconfig ,woz up ";
			}
		}
	}
	
	fclose($file);
	
	return $configs;
}

//得到所有的控制器
function get_all_controllers() {
	$dir = "controller";
	$controllers = array();
	
	if(FALSE !== ($dh = opendir($dir))) {
		while (FALSE !== ($file = readdir($dh))) {
			if(is_file($dir.$file)) {
				//echo "we include $dir$file ";
				require_once "$dir$file";
				$controllers[] = $file;
			}
		}
		
		closedir($dh);
	}
	else {
		echo "open $dir failed \n";
	}
	
	return $controllers;
}

//根据用户请求的URL得到控制器的类名。
function get_this_controller($url) {
	
	$req = strchr($url, "=");
	
	$pos = strpos($req, "/");
	
	$req = ltrim($req, "=");
	
	if(!$pos) {
		return null; // site index
	}
	
	$controller = substr_replace($req, "", $pos-1);
	
	return $controller."C";   //控制器的类的名字都在后面加了个C。
	
}

function qlog($msg, $level="INFO") 
{
	$day = date("Ymd");
	error_log(date("[Y-m-d H:i:s]-") ."[$level]-" ."[$msg]-[" .$_SERVER['REQUEST_URI']."]\n", 3,LOGFILE.DS."$day.log");
}

//网站根目录可能在域名的子目录下
function getRealRoot($uri)
{
	$root = dirname($_SERVER['PHP_SELF']);
	if(strncasecmp($root,$uri, strlen($root)))
		return $uri;
	
	return ltrim(substr_replace($uri, "", 0, strlen($root)), "/");
}

function getMvc() {
	$prefix = null;
	$mvc = array();

	$req = getRequestUri();
	$req = getRealRoot($req);
	
	if(!strlen($req) || $req=="index.php" || $req=="/") {
		$req = INDEX;
	}
	$mvc =  explode('/', $req);
		
	if(strtolower($mvc[0]) == ADMIN) {
		$prefix = ADMIN."_";
		array_shift($mvc);
	}
	elseif(strtolower($mvc[0]) == PERSONAL) {
		$prefix = PERSONAL."_";
		array_shift($mvc);
	}
	else {}

	if(!$mvc[1])
		$mvc[1] = "index";
		
	$mvc[1] = $prefix.$mvc[1];
	return $mvc;
}


function captcha() {

	//生成验证码图片
	Header("Content-type: image/PNG");
	$im = imagecreate(44,18);
	$back = ImageColorAllocate($im, 245,245,245);
	imagefill($im,0,0,$back); //背景

	srand((double)microtime()*1000000);
	//生成4位数字
	for($i=0;$i<4;$i++){
	$font = ImageColorAllocate($im, rand(100,255),rand(0,100),rand(100,255));
	$authnum=rand(1,9);
	$vcodes.=$authnum;
	imagestring($im, 5, 2+$i*10, 1, $authnum, $font);
	}

	for($i=0;$i<100;$i++) //加入干扰象素
	{ 
	$randcolor = ImageColorallocate($im,rand(0,255),rand(0,255),rand(0,255));
	imagesetpixel($im, rand()%70 , rand()%30 , $randcolor);
	} 
	ImagePNG($im);
	ImageDestroy($im);

	$_SESSION['CAPTCHA'] = $vcodes;

}


	
class Model {
	//table name, default is same as Model name
	protected $table = null;
	
	//use mysql
	//todo static $mydb;
	protected $mydb ;

	public $sqlArgs = array(
						"fields" => "",
						"table" => "",
						"where" => "",
						"limit" => "",
						"others" => "",
						);
	
	function __construct() {
		$class = get_class($this);
		
		if(!$this->table)
			$this->table = strtolower(str_replace("Model","",$class));

		//todo:strategy
		$this->mydb = Mysqlop::getInstance();
	}
	
	function __set($name, $value) {
        echo " in modellllllllllll __set [$name] [$value]";
		$this->data[$name] = $value;
    }

    function __get($name) {
        echo " in modellllllllllllll __get [$name]";
		if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
	}
	
	function __call($name, $value) {
		if((strncasecmp($name,"findby", 6))) {
			echo " model.php __call no function $name ";
		}
		return $this->{$name} (null,$value);
	}
	

	function dbQuery($sql) {
		return $this->mydb->db_select($sql);
	}
	
	function findById($id) {
		return $this->findAll($id);
	}

	function find() {
	}

	//Read 
	function findAll($id=null, $dbname=null) {
		$result = array();

		if($dbname)
			$this->table = $dbname;	

		if(empty($this->sqlArgs['fields']))
			$this->sqlArgs['fields'] = "*";

		$sql = "select  " .$this->sqlArgs['fields'] ." from " .$this->table. " "; 

		if($this->sqlArgs['where']) {
			$sql .= "  where  " .$this->sqlArgs['where']. " ";
		}
		else {
			if($id) {
				if(is_array($id))
					$sql .= "  where  id in (".implode(",",$id).") ";
				else 
					$sql .= "  where  id=$id limit 1";
			}
		}
	
		if($this->sqlArgs['others']) {
			$sql .= "  " .$this->sqlArgs['others']. " ";
		}

		if($this->sqlArgs['limit']) {
			$sql .= "  limit " .$this->sqlArgs['limit']. " ";
		}

		$result  = $this->mydb->db_select($sql);
		return $result;
	}


	//Create 
	function save ($data) {
		return $this->create($data);
	}

	function create($data, $table=null) {
		$sqlk = null;
		$sqlv = null;
		
		foreach ($data as $k => $v) {
			$sqlk .= "`$k`,";
			$sqlv .= "'".$this->filterValue($v)."',";
		}
		$sqlk .= "`created`";
		$sqlv .= " now()";		
		
		if($table)
			$sql = "insert into ". $table ." ($sqlk) values ($sqlv)";
		else
			$sql = "insert into ". $this->table ." ($sqlk) values ($sqlv)";

		return $this->mydb->db_Unselect($sql);
	}

	
	//Update 
	function update($data=null) {
		$id = null;
		$sql_key = null;
		$sql_value = null;

		$sql = "update ". $this->table ." set ";
		foreach ($data as $k => $v) {
			if($k == 'id') {
				$id = $v;
				continue;
			}
			
			$sql .= " `$k`='$v',";
		}
		
		$sql = rtrim($sql,",");

		//todo: need use sqlArgs['id']=array();
		$sql .= " where `id`=$id";

		return $this->mydb->db_Unselect($sql);	
	}
	
	
	//Delete 
	function delete($id) {
		$sql = "delete from ". $this->table. " where id=$id";

		return $this->mydb->db_Unselect($sql);	
	}

	function filterValue($value)
	{
		if(!$value)
			return '';

		if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
			$value = stripslashes($value);
		}

		if(is_string($value))
			$value = mysqli_real_escape_string($this->mydb->con, $value);

		return $value;
	}

}

class View {
	protected $pageTitle = "首页";
	protected $head = null;
	protected $footer= null;
	protected $body = null;
	protected $bodyContent = null;
	protected $sidebar = null;

	function output() {
		$pageTitle = $this->pageTitle;
		$head = $this->head;
		$footer = $this->footer;
		$body = $this->body;
		$sidebar = $this->sidebar;

		extract($this->bodyContent);

		include($head);
		include($body);
		include($footer);
		

	}

}

class Controller {
	
	// input data 
	protected $indata= array();
	
	//model data
	protected $tData = array();

	//data in views
	protected $vData = array();

	protected $model = null;
	
	protected $pageTitle = null;
	
	protected $tpl = null;
	
	protected $views = array();
	
	protected $needPost = array();
	protected $needAuth = array();

	protected $outputFormat = "html";

	//过滤post里变量的类型,初步过滤而已，具体跟业务相关的过滤还得到具体业务类里实现, key:变量名, value:int/string/email/等
	//todo
	protected $filterPostData = array();

	//post里必须的变量数量, key:方法名 value:数字
	//todo
	protected $argNum = array();
	
	function __construct() {
	
		if(!$this->model) {
			$class = get_class($this);
			//$this->modelName = ucfirst(strtolower($class))."Model"; //__toString();
			$model= ucfirst(strtolower($class))."Model"; //__toString();
			//todo:这样好还是直接一个controller只调用自己的modle好($this->model->save,而不是比如$this->stock_model->save)？
			$this->{$model} = loadModel($class);
		}
	}
	
	function __call($name, $arg) {
		qlog("no function [$name] in Controller:".get_class($this)."\n");
		exit;
	}
	
	function __get($key) {
		qlog("get $key in".get_class($this));
		/*
		if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
		*/
	}

	function __set($key, $value) {
		qlog("set $key in".get_class($this));
		if($key == $this->model) {
			echo " key == value ";
			//var_dump($key);
			return;
		}

		$this->$key = $value;
	}

	function __invoke($mvc) {
		$method = $mvc[1];
		
		//check auth
		if(!$this->checkAuth($mvc)) {
			header("Location: /user/login");
			exit;
		}
		
		$this->settpl(strtolower($method).".php");
				
		array_shift($mvc);
		array_shift($mvc);

		//filter_var();
		//$type = $_SERVER['REQUEST_METHOD'];
		$this->_postdata();

		$this->{$method}($mvc);
		
		if(!$this->views)
			$this->addView($method);

		$this->output();
	}
	

	function addView($vname)
	{
		$vfile = APP.DS."view".DS.get_class($this).DS."$vname.php";
    	if(!file_exists($vfile)){
			//header("Location: pages/404.php");
			include(APP."/pages/404.php");
			qlog("view file isnt exist: $vfile");
			exit;
		}

		$this->views []= $vfile;
	}

	function _postdata()
	{
		/*
		todo:可以在这里对参数进行统一过滤.方法是在controller类里定义一个数组名$args,
		每个子类的构造函数里设置这个数组的值
		$this->args = array("username" => "string", "email"=>"email","page"=>"num");
		然后在这个函数里进行初级过滤
		*/
		/*
		if (!filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL)) {
			echo "E-Mail is not valid";
		}
		else {
			echo "E-Mail is valid";
		}
		*/
		$this->indata = $_POST;
	}
	
	function outputHtml() {
		extract($this->vData);
		$pageTitle = $this->pageTitle.' '.TITLE;
		foreach($this->views as $view)
			include($view);
	}

	function outputJson() {
		//extract($this->vData);
		//echo json_encode($this->vData);
		echo json_encode($channel);
		exit;
	}

	function outputRss() {
		echo $this->vData;
		exit;
	}

	function output() {
		switch($this->outputFormat) {
			case "rss":
				$this->outputRss();
				break;
			case "xml":
				break;
			case "wml":
				break;
			case "json":
				$this->outputJson();
				break;
			case "html":
			default:
				$this->outputHtml();
				break;
		}
		
		//var_dump(get_defined_vars());
		//echo $this->vData;
	}


	function settpl($name) {
		$this->tpl .= $name;
	}

	function gettpl() {
		return $this->tpl ;
	}
	
	function checkSession() {
		//var_dump($_POST);
		if(isset($_SESSION['token']) && ($_POST['token'] == $_SESSION['token']) ){
			return true;
		}
		
		echo "server token ".$_SESSION['token']."##your token*".$_POST['token']."*session failed ";
		$_SESSION['token'] = md5(uniqid(rand(), true));
		return false;
	}

	function checkAuth($m) {
		if(strstr($m[1], "admin_")) {
			if(!isset($_SESSION['username']))
				return false;
		}	
		elseif(isset($m[1]) && $m[1]=="p_edit") {
			return true;
		}
		else { 
			return true; 
		}

		return true;

	}
}

class Mysqlop{
	public $con = null;
	private static $_instance;
	
	private function __construct() {
		$this->db_login();
	}
	
	private function __clone(){}


	function __destruct() {
		$this->db_logout();
	}

	public static function getInstance()
	{
		if(!self::$_instance instanceof self)
			self::$_instance = new self();
			
		return self::$_instance;	
	}
	
	
	function db_login() {
		$this->con = mysqli_connect(DBHOST, DBUSER, DBPW, DBNAME);
		if (mysqli_connect_errno($this->con)) {
			die('Could not connect: ' . mysqli_connect_error());
		}
		
		mysqli_query($this->con, "set names utf8");
	}
		
	function db_logout() {
		if($this->con)
			mysqli_close($this->con);
	}

	function db_select($sql) {
		$total = array();
		
		qlog("$sql","SQL");
		$result = mysqli_query($this->con, $sql);

		if (!$result) {
			qlog(mysqli_error($this->con).":*$sql*");
		}
	
		while(($row = mysqli_fetch_assoc($result))) {
			$total []= $row;
		}

		mysqli_free_result($result);
		return $total;
	}
	
	
	function db_Unselect($sql) {
		qlog("$sql","SQL");

		if (!mysqli_query($this->con, $sql)) {
			qlog(mysqli_error($this->con).":*$sql*");
			return -1;
		}

		if(strncasecmp(trim($s),'insert',6)) {
			return mysqli_insert_id($this->con);
		}
		else {
			return mysqli_affected_rows($this->con);
		}
	}
	
	
}

?>
