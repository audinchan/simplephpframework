<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>SimplePHP手册</title>
<link href="/js/google-code-prettify/prettify.css" type="text/css" rel="stylesheet" />
<script src="/js/google-code-prettify/run_prettify.js"></script>
</head>
<body>
<h1>SimplePHP框架开发手册</h1>
<ul>
    <li><a href="#intro">简介</a></li>
    <li><a href="#hello">快速入门</a></li>
    <li><a href="#setup">配置</a></li>
    <li><a href="#structure">目录结构</a></li>
    <li><a href="#action">控制器</a></li>
    <li><a href="#view">视图</a></li>
    <li><a href="#viewlet">视图组件</a></li>
    <li><a href="#annotation">Annotation</a></li>
    <li><a href="#tag">标签</a></li>
    <li><a href="#express">内容显示表达式</a></li>
    <li><a href="#route">路由</a></li>
    <li><a href="#filter">过滤器</a></li>
    <li><a href="#database">访问数据库</a></li>
    <li><a href="#lib">核心类库介绍</a></li>
    <li><a href="#simplephp">框架级类和接口介绍</a></li>
</ul>

<a name="intro"></a>
<h2>简介</h2>
<p>SimplePHP框架是一个简单高效的PHP项目开发框架，框架遵循约定优于配置（CoC）思想，通过Annotation、依赖注入（IoC）、自定义标签、内容显示表达式和过滤器等技术来简化PHP项目开发。</p>

<h4>名词解释：</h4>
<ul>
    <li>Service - 业务层对象；</li>
    <li>Action - 控制器，用来接收HTTP请求，并调用业务层对象完成业务处理，通过View输出页面；</li>
    <li>View - 视图，将Action处理后的结果显示到页面；</li>
    <li>Viewlet - 视图组件，负责处理页面上的局部模块；</li>
    <li>Annotation - 在文档注释中的特定标记，通常以@符号开始，用于告知框架某些行为；</li>
    <li>Tag - 标签，在视图中用来控制逻辑或执行特殊代码的类HTML元素；</li>
    <li>内容显示表达式 - 在视图中用来显示变量或表达式值，如<i>{\$varname}</i>；</li>
    <li>Filter - 过滤器，通过正则表达式设置过滤条件，满足条件的URL将自动触发过滤器；</li>
</ul>

<h4>一般处理逻辑：</h4>
<ol>
    <li>浏览器访问网站，如http://domain/action/method?param1=value1&amp;param2=value2；</li>
    <li>框架解析并访问action对象的method方法，同时将param1和param2设置到action的属性中；</li>
    <li>框架执行完action根据方法返回值决定要显示的视图，并将处理过程中产生的变量输出到视图中；</li>
    <li>视图组织页面的实际显示内容。</li>
</ol>

<h4>约定：</h4>
<ol>
    <li>每个类或接口对应一个文件，类名或接口名与文件名一致，比如类名是FooService，则文件名是FooService.php；</li>
    <li>Action、View、Viewlet、Service、Filter分别放入各自的目录（见‘<a href="#structure">目录结构</a>’）；</li>
    <li>所有类都需要使用namespace。</li>
</ol>

<a name="setup"></a>
<h2>配置</h2>
<p>解压后将SimplePHP目录拷贝到网站根目录。</p>
<p>如果服务器是Apache，则在网站根目录下增加“.htaccess”文件，内容如下：</p>
<pre>
<highlighthtml>
<IfModule mod_rewrite.c>
        RewriteEngine on
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(.*)$ index.php?$1 [QSA,PT,L]
</IfModule>
</highlighthtml>
</pre>
<p>注意：Apache主配置文件中需要启用“AllowOverride Options”或“AllowOverride All”。</p>
<p>如果服务器是Nginx，则可以在“location / {...}”中增加如下内容：</p>
<pre>
<highlighthtml>
if (!-f $request_filename){
	rewrite (.*) /index.php;
}
</highlighthtml>
</pre>
<p>为了提高安全性，建议增加以下配置禁止直接访问框架文件：</p>
<pre>
<highlighthtml>
location ~ /SimplePHP {
        deny all;
        access_log off;
        log_not_found off;
}
</highlighthtml>
</pre>

<a name="hello"></a>
<h2>快速入门</h2>
<p>在“SimplePHP/classes/action”目录中创建“example1.php”（已有范例文件），内容如下：</p>
<highlight>
namespace action;

class example1 {
    public function execute() {
        echo 'Hello World!';
    }
}
</highlight>
<p>使用浏览器访问“<a href="http://localhost/example1" target="_blank">http://localhost/example1</a>”，将显示“Hello World!”。</p>

<a name="structure"></a>
<h2>目录结构</h2>
<pre>
网站根目录/
  ├─ index.php                        
  └─ SimplePHP/                        框架目录
      ├─ kernel.php                    框架内核文件
      ├─ classes/                      类目录
      │    ├─ action/                  控制器类的根目录
      │    ├─ filter/                  过滤器类的根目录
      │    └─ service/                 业务类的根目录
      │         ├─ core/               一般用来存放核心类库
      │         │    ├─ cache/         缓存技术的接口和实现
      │         │    ├─ db/            数据库访问接口和实现
      │         │    ├─ log/           日志记录接口和实现
      │         │    └─ queue/         队列接口和实现
      │         └─ viewlet/            视图组件类的根目录
      ├─ view/                         视图根目录
      │    └─ viewlet/                 视图组件视图的根目录
      └─ view_cache/                   视图缓存目录
</pre>

<a name="action"></a>
<h2>控制器</h2>
<p>Action是一个普通的PHP类，要求放在SimplePHP/classes/action目录或其子目录下。Action的任何public方法，都可以通过URL直接访问，如果URL中未指定访问哪个方法，则框架会自动尝试访问Action的execute方法。</p>
<p>Action方法通过返回值（通常是字符串）告诉框架用哪个视图来显示页面，如果方法没有返回值，则框架结束处理。参考SimplePHP/classes/action/example2.php：</p>
<highlight>
namespace action;

class example2 {
	// 自动从Request参数中获取
	public $username;
	
	public function execute() {
		// 返回一个字符串告诉框架视图名称，不加文件扩展名。
		return 'example2';
	}
}
</highlight>
<p>在浏览器中访问<a href="http://localhost/example2?username=xyz" target="_blank">http://localhost/example2?username=xyz</a>。
	execute方法通过返回值告诉框架使用名称为example2的视图。</p>

<a name="view"></a>
<h2>视图</h2>
<p>View就是一个简单的php页面，可以使用任何PHP语法，为了提高页面可读性，通常情况下只使用HTML和框架内置的标签以及内容显示表达式的语法。参考SimplePHP/view/example2.php：</p>
<pre>
<highlighthtml>
<html>
<head>
<title>example2</title>
</head>
<body>
<if exp="!empty($username)"/>
Hello，{$username}。
<else/>
Hello，匿名用户。
<end/>
<viewlet name="ViewletExample" params="username=${username};otherparam=xxx" />
</body>
</html>
</highlighthtml>
</pre>
<p>
该视图内容解读：
<ul>
	<li>使用了<highlighthtml><if exp=""/><else/><end/></highlighthtml>，这是框架内置的用于处理页面逻辑的标签，除了if标签，还有foreach、for、elseif、while、switch等标签，参考“<a href="#tag">标签</a>”；</li>
	<li>使用内容显示表达式语法来显示变量{\$username}；参考“<a href="#express">内容显示表达式</a>”。</li>
	<li>使用了视图组件<highlighthtml><viewlet name="ViewletExample" params="username=${username};otherparam=xxx" /></highlighthtml>，并传入了一个动态参数和一个静态参数，参数之间采用“;”分隔，参考“<a href="#tag-viewlet">viewlet标签</a>”。</li>
</ul>
</p>

<a name="viewlet"></a>
<h2>视图组件</h2>
<p>Viewlet允许把大量通用的页面模块单独封装，重复使用。参考SimplePHP/service/viewlet/ViewletExample.php：</p>
<highlight>
namespace service\viewlet;

class ViewletExample {
	public $username;
	public $otherparam;
	
	// 每个Viewlet必须有execute方法，框架会调用此方法
	public function execute() {
		return 'ViewletExample';
	}
}
</highlight>
<p>Viewlet就像一个特殊的Action，只是要求放在特定的目录中，并且必须有execute方法，框架自动调用该方法并且根据返回值输出该组件的页面内容。</p>
<p>Viewlet的视图存放在SimplePHP/view/viewlet目录中，参考SimplePHP/view/viewlet/ViewletExample.php：</p>
<pre>
<highlighthtml>
<div>
Hello, {$username}.
</div>
</highlighthtml>
</pre>
<p>Viewlet的视图跟View没有区别。</p>

<a name="annotation"></a>
<h2>Annotation</h2>
<p>SimplePHP框架大量采用Annotation来简化代码编写，Annotation以“@”符号开始，书写在类、属性以及类方法的文档注释中，合理的运用Annotation可以达到事半功倍的效果。</p>
<ol>
	<li>
	<b>@service(别名或类)</b><br/>
	作用域：属性<br/>
	说明：将业务类的实现加载到属性变量中<br/>
	范例：<br/>
<highlight>
/**
* @service(database)
*/
private $db;
</highlight>
	</li>
	<li>
	<b>@session</b><br/>
	作用域：Action方法<br/>
	说明：如果Action方法没有标注@session，则在执行Action方法之前立即关闭SESSION。<br/>
	范例：<br/>
<highlight>
/**
* @session
*/
public function execute() {
}
</highlight>
	</li>
	<li>
	<b>@json</b><br/>
	作用域：Action方法<br/>
	说明：Action方法执行结果以JSON格式输出，如果方法返回值不是数组或对象，则自动创建array('result'=>返回值)数组后再转换为JSON。<br/>
	范例：<br/>
<highlight>
/**
* @json
*/
public function execute() {
}
</highlight>
	</li>
	<li>
	<b>@xml 或 @xml(根节点名称)</b><br/>
	作用域：Action方法<br/>
	说明：Action方法执行结果以XML格式输出，如果方法返回值不是数组或对象，则自动创建array('result'=>返回值)数组后再转换为XML。<br/>
	范例：<br/>
<highlight>
/**
* @xml
*/
public function execute() {
}
</highlight>
	</li>
	<li>
	<b>@contentType(页面输出类型)</b><br/>
	作用域：Action方法<br/>
	说明：设置Action方法对应的页面输出所使用的ContentType。<br/>
	范例：<br/>
<highlight>
/**
* @contentType(text/xml; charset=utf-8)
*/
public function execute() {
}
</highlight>
	</li>
	<li>
	<b>@preHandler(方法名)</b><br/>
	作用域：Action方法<br/>
	说明：设置Action在执行方法之前总是先执行的另一个方法。<br/>
	范例：<br/>
<highlight>
/**
* @preHandler(prehandler)
*/
public function execute() {
}
</highlight>
	</li>
	<li>
	<b>@afterHandler(方法名)</b><br/>
	作用域：Action方法<br/>
	说明：设置Action在执行方法之后总是执行的另一个方法。<br/>
	范例：<br/>
<highlight>
/**
* @afterHandler(afterhander)
*/
public function execute() {
}
</highlight>
	</li>
	<li>
	<b>@transaction</b><br/>
	作用域：Service方法<br/>
	说明：Service方法是否启用数据库事务，要使用自动数据库事务管理，必须在加载框架后执行一次<br/>
<highlight>
\SimplePHP\Services::setTransactionManagerClass('core.db.DefaultTransactionManager');
</highlight><br/>
	范例：<br/>
<highlight>
/**
* @transaction
*/
public function execute() {
}
</highlight>
	</li>
	<li>
	<b>@xmlAlias(别名)</b><br/>
	作用域：类<br/>
	说明：与@xml配合，当Action方法返回的对象设置了@xmlAlias，则xml输出的元素为指定的别名，否则使用对象类的简单名称。<br/>
	范例：<br/>
<highlight>
/**
* @xmlAlias(data)
*/
class obj {
}
</highlight>
	</li>
	<li>
	<b>@cData</b><br/>
	作用域：属性<br/>
	说明：与@xml配合，xml输出该属性值时是否放入&lt;![CDATA[...]]&gt;标签中。<br/>
	范例：<br/>
<highlight>
/**
* @cData
*/
private $propName;
</highlight>
	</li>
</ol>

<a name="tag"></a>
<h2>标签</h2>
<p>SimplePHP框架内置了大量视图页面的标签，方便流程控制和显示特殊内容。</p>
<ol>
	<li>
	<b><highlighthtml><if/></highlighthtml></b><br/>
	语法：<highlighthtml><if exp="..."/></highlighthtml><br/>
	描述：条件判断，当exp中的表达式成立时，执行后续内容。<br/>
	范例：<highlighthtml><if exp="$var==3"/></highlighthtml>
	</li>
	<li>
	<b><highlighthtml><elseif/></highlighthtml></b><br/>
	语法：<highlighthtml><elseif exp="..."/></highlighthtml><br/>
	描述：条件判断，当exp中的表达式成立时，执行后续内容。<br/>
	范例：<highlighthtml><elseif exp="$var==3"/></highlighthtml>
	</li>
	<li>
	<b><highlighthtml><else/></highlighthtml></b><br/>
	语法：<highlighthtml><else/></highlighthtml><br/>
	描述：条件判断，与if和elseif标签配套，当其条件不成立时，执行后续内容。<br/>
	</li>
	<li>
	<b><highlighthtml><foreach/></highlighthtml></b><br/>
	语法：<highlighthtml><foreach exp="..."/></highlighthtml><br/>
	描述：循环迭代器，执行后续内容，直到end或break，碰到continue则跳过一次循环。<br/>
	范例：<highlighthtml><foreach exp="$array as $key => $value"/></highlighthtml>
	</li>
	<li>
	<b><highlighthtml><for/></highlighthtml></b><br/>
	语法：<highlighthtml><for exp="..."/></highlighthtml><br/>
	描述：循环迭代器，执行后续内容，直到end或break，碰到continue则跳过一次循环。<br/>
	范例：<highlighthtml><for exp="$i=1;$i<10;$i++"/></highlighthtml>
	</li>
	<li>
	<b><highlighthtml><while/></highlighthtml></b><br/>
	语法：<highlighthtml><while exp="..."/></highlighthtml><br/>
	描述：循环迭代器，执行后续内容，直到end或break，碰到continue则跳过一次循环。<br/>
	范例：<highlighthtml><while exp="$var<10"/></highlighthtml>
	</li>
	<li>
	<b><highlighthtml><switch/></highlighthtml></b><br/>
	语法：<highlighthtml><switch exp="..."/></highlighthtml><br/>
	描述：选择器，与case、default、break配合。<br/>
	范例：<highlighthtml><switch exp="$var"/></highlighthtml>
	</li>
	<li>
	<b><highlighthtml><case/></highlighthtml></b><br/>
	语法：<highlighthtml><case value="..."/></highlighthtml><br/>
	描述：与switch配合，当value中的值等于switch中的exp描述的变量，执行后续内容。<br/>
	范例：<highlighthtml><case value="3"/></highlighthtml>
	</li>
	<li>
	<b><highlighthtml><default/></highlighthtml></b><br/>
	语法：<highlighthtml><default/></highlighthtml><br/>
	描述：与switch和case配合，所有case都不满足，执行后续内容。<br/>
	</li>
	<li>
	<b><highlighthtml><continue/></highlighthtml></b><br/>
	语法：<highlighthtml><continue/></highlighthtml><br/>
	描述：与循环迭代器配合，表示跳过本次循环。<br/>
	</li>
	<li>
	<b><highlighthtml><break/></highlighthtml></b><br/>
	语法：<highlighthtml><break/></highlighthtml><br/>
	描述：与循环迭代器配合，表示终止本次循环。<br/>
	</li>
	<li>
	<b><highlighthtml><end/></highlighthtml></b><br/>
	语法：<highlighthtml><end/></highlighthtml><br/>
	描述：与条件、循环、选择器配合，表示结束。<br/>
	</li>
	<li>
	<b><highlighthtml><setvar/></highlighthtml></b><br/>
	语法：<highlighthtml><setvar name="..." value="..."/></highlighthtml><br/>
	描述：设置变量以供后续代码使用。<br/>
	范例：<highlighthtml><setvar name="varname" value="3"/> <setvar name="varname" value="$anotherVar"/></highlighthtml>
	</li>
	<li>
	<b><highlighthtml><const/></highlighthtml></b><br/>
	语法：<highlighthtml><const 常量名称/></highlighthtml><br/>
	描述：输出常量。<br/>
	范例：<highlighthtml><const SiteTitle/></highlighthtml>
	</li>
	<li>
	<b><highlighthtml><tpl/></highlighthtml></b><br/>
	语法：<highlighthtml><tpl page="视图名称"/></highlighthtml><br/>
	描述：载入并解析视图文件，经常用来将页面分成header、footer等多个独立的模块。<br/>
	范例：<highlighthtml><tpl tpl="exampleview"/></highlighthtml>
	</li>
	<li>
	<a name="tag-viewlet"/><b><highlighthtml><viewlet/></highlighthtml></b><br/>
	语法：<highlighthtml><viewlet name="..." params="..."/></highlighthtml><br/>
	描述：载入视图组件，通过视图组件将页面上常用的功能做成独立的模块，每个模块可以单独执行业务处理，多个参数使用“;”分隔。<br/>
	范例：<highlighthtml><viewlet name="SidebarViewlet" params="moduleId=${moduleId};activeId=1"/></highlighthtml>
	</li>
	<li>
	<b><highlighthtml><timeused/></highlighthtml></b><br/>
	语法：<highlighthtml><timeused/></highlighthtml><br/>
	描述：显示执行到当前位置所话时间（秒）。<br/>
	</li>
</ol>

<a name="express"></a>
<h2>内容显示表达式</h2>
<p>内容显示表达式用于将变量或者函数以及产生输出的PHP表达式的值显示到页面上，语法规范如下：</p>
<i>{[修饰词]?$变量名称或表达式}</i>
<p>修饰词是可选的，范例：</p>
<ol>
	<li>普通变量：<i>{\$varname}</i></li>
	<li>表达式：<i>{\$function1()}</i></li>
	<li>数组变量：<i>{\$array['key']}</i></li>
	<li>对象变量：<i>{\$obj->propname}</i></li>
	<li>对象表达式：<i>{\$obj->function()}</i></li>
	<li>数组变量的简化写法：<i>{\$array:key}</i></li>
	<li>对象变量的简化写法：<i>{\$obj.propname}</i></li>
</ol>
<p>目前支持修饰词有：</p>
<ol>
	<li>从$_REQUEST变量中获取数据：{r\$paraname}</li>
	<li>从$_GET变量中获取数据：{g\$paraname}</li>
	<li>从$_POST变量中获取数据：{p\$paraname}</li>
	<li>从$_SESSION变量中获取数据：{s\$paraname}</li>
	<li>从$_COOKIE变量中获取数据：{c\$paraname}</li>
	<li>从$_ENV变量中获取数据：{e\$paraname}</li>
	<li>从$GLOBALS变量中获取数据：{glb\$paraname}</li>
	<li>从$_SERVER变量中获取数据：{srv\$paraname}</li>
</ol>

<a name="route"></a>
<h2>路由</h2>
<p>路由是SimplePHP框架的重要功能，启用路由功能可以让URL更友好直接。在加载框架后即可设置路由：</p>
<highlight>
\SimplePHP\Services::addRoute('~^/user/([0-9]+)$~', '/user/info?userId=$1');
</highlight>
<p>则访问http://domain/user/1会被路由到http://domain/user/info?userId=1。</p>

<a name="filter"></a>
<h2>过滤器</h2>
<p>SimplePHP允许拦截特定的URL，交给过滤器进行预处理。要启用过滤器需要在加载框架后调用以下语句：</p>
<highlight>
\SimplePHP\Services::addFilter('/URL匹配正则表达式/', '过滤器类名称');
</highlight>
<p>其中URL匹配正则表达式用来确定用户访问的哪些URL要被过滤器拦截并预处理，过滤器类名称需要包含完整的namespace，比如：filter.auth.LoginFilter。</p>

<a name="database"></a>
<h2>访问数据库</h2>
<p>框架类库中实现了访问Mysql数据库的类，参考SimplePHP/classes/action/example3.php：</p>
<highlight>
namespace action;

class example3 {
	/**
	* @service(database)
	*/
	private $db;
	
	public function execute() {
		$user = $this->db->row('select login_name from test where id=?', 1);
		$loginname = $this->db->col('select login_name from test where id=?', 1);
		echo 'loginname: '.$user['login_name'].'<br/>';
		echo 'loginname: '.$loginname;
	}
}
</highlight>

<a name="lib"></a>
<h2>核心类库介绍</h2>

<ol>
	<li>
		<h4>Mysql数据库访问接口</h4>
		<p>配置项：</p>
			<ul>
				<li>db.dsn：数据库链接串，如：mysql:dbname=test;host=127.0.0.1</li>
				<li>db.user：数据库访问用户名</li>
				<li>db.password：数据库访问密码</li>
			</ul>
		<p>框架初始化后设置别名：</p>
<highlight>
\SimplePHP\Services::alias('database', 'core.db.MysqlDB');
</highlight>
		<p>在类属性中使用别名依赖注入：</p>
<highlight>
/**
* @service(database)
*/
private $db;
</highlight>
		<p>接口：</p>
<highlight>
namespace service\core\db;

interface IDatabase {
	
	/**
	* 从数据库查询一行数据，至少输入一个参数
	* @param 第一个参数是SQL语句，后面的参数是变量。
	* @return 一维数组
	* 范例：
	* row($sql, $v1, $v2, $v3)
	* row('select * from user where id=1');
	* row('select * from user where loginname=?', $loginname);
	*/
	public function row();
	
	// 从数据库查询一行一列的数据
	public function col();
	
	// 从数据库查询多行数据，返回二维数组
	public function rows();
	
	// 从数据库查询多行数据，返回二维数组
	public function rowsWithParams($sql, $params=null);
	
	// 执行SQL，返回Statement
	public function execQuery();
	
	// 命名SQL，返回Statement
	public function execNamedQuery($sql, $args);
}
</highlight>
		<p>范例：</p>
<highlight>
$user = $this->db->row('select login_name from test where id=?', 1);
</highlight>
	</li>
	
	<li>
	<h4>基于数据库的系统日志</h4>
	<p>配置项：</p>
		<ul>
			<li>logger.table：保存日志的表名，默认是log</li>
			<li>logger.level：日志记录级别，默认是off，可选的级别有：info,debug,warn,error,off</li>
		</ul>
	<p>需要建立以下数据表：</p>
	<pre>
<highlighthtml>
CREATE TABLE `log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `logtime` datetime DEFAULT NULL,
  `level` varchar(10) DEFAULT NULL,
  `msg` text,
  `filename` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
</highlighthtml>
	</pre>
	<p>框架初始化后设置别名：</p>
<highlight>
\SimplePHP\Services::alias('logger', 'core.log.DBLogger', false);
</highlight>
	<p>在类属性中使用别名依赖注入：</p>
<highlight>
/**
* @service(logger)
*/
private $logger;
</highlight>
	<p>接口：</p>
<highlight>
namespace service\core\log;

interface ILogger {
	const LEVEL_DEBUG = 1;
	const LEVEL_INFO = 2;
	const LEVEL_WARN = 3;
	const LEVEL_ERROR = 4;
	const LEVEL_OFF = 10;
	
	/**
	 * 设置日志级别，可以是常量，也可以是字符串
	 * @param mix $level
	 */
	public function setLevel($level);
	
	/**
	 * 获取日志级别
	 * @return int 级别
	 */
	public function getLevel();
	
	/**
	 * 记录日志，第一个参数是级别，第二个参数是格式，之后的多个参数是变量
	 * 如：log($level, 'log format is %s and %s', $value1, $value2);
	 */
	public function log();
	
	/**
	 * 记录info级别的日志，第二个参数是格式，之后的多个参数是变量
	 * 如：info('some log data is %s and %s', $value1, $value2);
	 */
	public function info();
	
	/**
	 * 记录debug级别的日志，第二个参数是格式，之后的多个参数是变量
	 * 如：debug('some log data is %s and %s', $value1, $value2);
	 */
	public function debug();
	
	/**
	 * 记录warn级别的日志，第二个参数是格式，之后的多个参数是变量
	 * 如：warn('some log data is %s and %s', $value1, $value2);
	 */
	public function warn();
	
	/**
	 * 记录error级别的日志，第二个参数是格式，之后的多个参数是变量
	 * 如：info('some log data is %s and %s', $value1, $value2);
	 */
	public function error();
}
</highlight>
	<p>范例：</p>
<highlight>
$this->logger->debug('postStr=%s', $postStr);
</highlight>
	</li>
	
	<li>
	<h4>缓存（支持文件缓存和Memcached的分布式缓存）</h4>
	<p>文件缓存配置项：</p>
		<ul>
			<li>cache.FileCache.cacheDir：缓存文件保存目录，WEB服务器必须对该目录可以读写操作。</li>
		</ul>
	<p>Memcached缓存配置项：</p>
		<ul>
			<li>cache.MMCache.servers：Memcached服务器列表（数组）。如：<br/>
<highlight>
// 每行配置一个服务器，分别是服务器地址、端口号、权重。
$servers = array(
    array('mem1.domain.com', 11211, 33),
    array('mem2.domain.com', 11211, 67)
);
</highlight>
			</li>
		</ul>
	<p>框架初始化后设置别名：</p>
<highlight>
// 文件缓存
\SimplePHP\Services::alias('cache', 'core.cache.FileCache');
// Memcached缓存
\SimplePHP\Services::alias('cache', 'core.cache.MMCache');
</highlight>
	<p>在类属性中使用别名依赖注入：</p>
<highlight>
/**
* @service(cache)
*/
private $cache;
</highlight>
	<p>接口：</p>
<highlight>
namespace service\core\cache;

/**
* 缓存接口。
*/
interface ICache {
	/**
	* 将数据放入缓存。
	* @param $key 缓存项。
	* @param $data 要缓存的数据。
	* @param $expireSeconds 缓存过期时间（秒）。
	*/
	public function put($key, $data, $expireSeconds);
	
	/**
	*
	* 将数据放入缓存。
	* @param $key 缓存项。
	* @return 缓存中的数据。
	*/
	public function get($key);
	
	/**
	* 将数据放入缓存。
	* @param $key 缓存项。
	*/
	public function delete($key);
}
</highlight>
	<p>范例：</p>
<highlight>
$this->cache->put($key, $value, 60*60*24);
</highlight>
	</li>
	
	<li>
	<h4>Httpsqs队列</h4>
	<p>配置项：</p>
		<ul>
			<li>queue.httpsqs.address：队列服务器IP地址</li>
			<li>queue.httpsqs.port：Httpsqs运行端口</li>
			<li>queue.httpsqs.auth：认证信息</li>
		</ul>
	<p>框架初始化后设置别名：</p>
<highlight>
\SimplePHP\Services::alias('queue', 'core.queue.HttpsqsQueue');
</highlight>
	<p>在类属性中使用别名使用依赖注入：</p>
<highlight>
/**
* @service(queue)
*/
private $queue;
</highlight>
	<p>接口：</p>
<highlight>
namespace service\core\queue;
use \Exception;

/**
* 队列
*/
interface IQueue {
	
	/**
	* 往队列中写入数据
	* @param $name 队列名称
	* @param $data 数据
	*
	* @return array('code'=>错误代码, 'result'=>队列存入结果, 'pos'=>队列存入位置【没有错误时才有效】)
	* code 0: 没有错误，1：队列已满，-1：存入出错，-2：其它错误
	*/
	function put($name, $data);
	
	/**
	* 往队列中读出数据
	* @param $name 队列名称
	*
	* @return array('code'=>错误代码, 'data'=>数据, 'pos'=>队列读出位置【没有错误时才有效】)
	* code 0: 没有错误，1：队列已空，-2：其它错误
	*/
	function get($name);
}
</highlight>
	<p>范例：</p>
<highlight>
$this->queue->put('upload', $data);
</highlight>
	</li>

</ol>


<a name="simplephp"></a>
<h2>框架级类和接口介绍</h2>

<ol>
	<li>
		<b>\SimplePHP\NFRedirect</b>
		当Action方法返回new NFRedirect($url)时，框架自动重定向到$url。
	</li>
	<li>
		<b>\SimplePHP\NFSkipAction</b>
		通常用在preHander中，返回该对象表示执行完不再执行Action方法。
	</li>
	<li>
		<b>\SimplePHP\IConfigurable</b>
		当业务类实现了该接口，框架会自动调用业务类的config方法来完成初始化，通常用来读取系统运行参数。<br/>
		接口：<br/>
<highlight>
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
</highlight>
		<br/>IConfiguration接口：<br/>
<highlight>
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
</highlight>
		<br/>
		范例：<br/>
<highlight>
namespace service\core\db;
use PDO;
use PDOException;
use SimplePHP\IConfigurable, SimplePHP\ITransactionManager, SimplePHP\Services;

class MysqlDB implements IDatabase, IConfigurable, ITransactionManager {
	protected $dsn;
	protected $user;
	protected $password;

	public function config($config) {
		$this->dsn = $config->get('db.dsn');
		$this->user = $config->get('db.user');
		$this->password = $config->get('db.password');
	}
}
</highlight>
	</li>
	<li>
		<b>\SimplePHP\IActionMethodHelper</b>
		Action类通过实现该接口告诉框架应该执行哪个方法。<br/>
<highlight>
interface IActionMethodHelper {
	public function getActionMethod();
}
</highlight>
		<br/>范例：<br/>
<highlight>
class wx implements \SimplePHP\IActionMethodHelper {
	public function getActionMethod() {
		return (isset($this->echostr)) ? 'valid' : 'responseMsg';
	}
}
</highlight>
	</li>
	<li>
		<b>\SimplePHP\IServiceCallerAware</b>
		业务类通过实现该接口来获取调取本业务类的对象。<br/>
<highlight>
interface IServiceCallerAware {
	public function setCallerInfo($obj);
}
</highlight>
		<br/>范例：<br/>
<highlight>
namespace service\core\log;
use SimplePHP\IConfigurable, SimplePHP\IServiceCallerAware, SimplePHP\Services;

class DBLogger implements ILogger, IConfigurable, IServiceCallerAware {
	public function setCallerInfo($obj) {
		$this->callerClass = get_class($obj);
	}
}
</highlight>
	</li>
</ol>

</body>
</html>