# QuarkPHP
基于 PHP 5.3+ 开发的轻型框架，以“灵活定制、随处可用”为开发理念。没有包含URI路由，但可以轻松与 [nikic/FastRoute](https://github.com/nikic/FastRoute) 或 [c9s/Pux](https://github.com/c9s/Pux) 等成熟的路由程序整合。

## 安装方法
推荐使用composer在所需项目的路径里使用以下命令安装，使用[composer中国全量镜像](http://pkg.phpcomposer.com/)可加快安装速度

	composer require dxvgef/quarkphp
	
或者下载本库src目录中的quarkphp.php源码文件并require该文件

##功能简介
框架由以下五个类实现不同功能，可在遵守[BSD-3-Clause](https://github.com/dxvgef/QuarkPHP/blob/master/LICENSE)协议的情况下，根据项目的功能需求删减不需要的类，或增加修改功能。

#### Dispatcher 调度器
* 供URI路由调用
* 在控制器函数return后自动加载视图（可在控制器内启用或禁用）
* HTML静态文件缓存
#### Base 控制器基类
* 可选择供控制器类继承
* 获取URI路由参数
* 手动加载HTML/JSON视图
* 加载模型

#### Logger 日志文件记录器
* 根据级别将日志记录到日志文件
* 日志文件自动按天分割

#### Connect 连接器
* 使用PDO扩展连接到MySQL
* 使用PDO扩展连接到PostgreSQL
* 使用Redis扩展连接到Redis
* 使用Mongo扩展连接到MongoDB
* 使用Memcache扩展连接到Memcached
* 未封装任何CURD，连接器仅供建立连接并返回连接对象

#### Verifycode 图形验证码
* 生成包含中文在内的自定义字符的图形验证码
* 可定义验证码图形的尺寸、背景色、前景色、验证码字符数、字体、字体大小、干扰线、干扰噪点、字间距、旋转角度、验证码变量名等参数

#### Upload 文件上传
* 根据文件MIME值限制上传的文件类型
* 可开启文件名字动转换为小写



#### 与 [nikic/FastRoute](https://github.com/nikic/FastRoute) 路由程序整合示例：
```PHP
<?php
define('ROOT_PATH', dirname($_SERVER['DOCUMENT_ROOT']));
require ROOT_PATH . '/vendor/autoload.php';

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    //Index.Main 代表'控制器类名.方法名'
    $r->addRoute('GET', '/', 'Index.Main');
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case \FastRoute\Dispatcher::NOT_FOUND:
        echo '404 Not Found';
        break;
    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        echo '405 Method Not Allowed';
        break;
    case \FastRoute\Dispatcher::FOUND:

        //如果使用composer require命令安装QuarkPHP，则不需要此行
        require ROOT_PATH . '/quarkphp.php';


        //设置QuarkPHP运行参数
        Dispatcher::$htmlCache = false;  //是否开启HTML缓存
        Dispatcher::$htmlCachePath = '/htmlcache';  //HTML缓存路径
        Dispatcher::$controllerPath = '/controller';  //控制器文件路径
        Base::$ModelPath = '/model';  //HTML缓存路径
        Base::$ViewPath = '/view';  //HTML缓存路径
        Logger::$Level = 'warn';   //日志记录级别
        Logger::$Path = '/log';   //日志文件目录
		//更多参数配置请查看源码中各个class里的注释


        //执行 QuarkPHP 调度器并传入(控制器名,路由参数)
        \QuarkPHP\Dispatcher::Run($routeInfo[1], $routeInfo[2]);

        break;
}
?>
```
