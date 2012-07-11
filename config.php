<?php

//加载当前路径
if (DS == '\\')
	set_include_path(get_include_path() . ".;". CORE);
else
	set_include_path(get_include_path() . ".:". CORE);
//@ini_set(,       '.;' . ROOT);



//默认管理后台和个人后台的后缀
define('ADMIN', "admin");
define('PERSONAL', "u");

//默认首页指向
define("INDEX", "yuce");

//日志
define('LOGFILE', APP.DS.'logs');
define('LOGLEVEL', "INFO");  //INFO*WARN*ERR

define('ORMFILE',APP.DS.'orm.ini');

//DB define
define('DBHOST','127.0.0.1');
define('DBUSER','root');
define('DBPW','');
define('DBNAME','16liang');

?>
