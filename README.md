# QuarkPHP
“夸克PHP”是核心只有一个php文件的微型PHP框架，追求的是灵活定制、随处可用的特点。因此，框架没有包含URI路由功能，但兼容[nikic/FastRoute](https://github.com/nikic/FastRoute) 及 [c9s/Pux](https://github.com/c9s/Pux) 等成熟的路由程序。

#### 功能列表
* MVC结构
* 控制器函数return后自动加载视图，使控制器内的逻辑更灵活
* HTML静态文件缓存
* Logger日志文件记录器
* 更多功能准备以插件的形式提供...


#### 与 [nikic/FastRoute](https://github.com/nikic/FastRoute) 路由程序整合示例：
```PHP
<?php
define('ROOT_PATH', dirname($_SERVER['DOCUMENT_ROOT']));
require ROOT_PATH . '/vendor/autoload.php';

//载入QuarkPHP文件
require ROOT_PATH . '/quarkphp.php';

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
        //执行 QuarkPHP 调度器并传入(控制器名,路由参数)
        \QuarkPHP\Dispatcher::Run($routeInfo[1], $routeInfo[2]);
        break;
}
?>
```
#### 与 [c9s/Pux](https://github.com/c9s/Pux) 路由程序整合示例：
