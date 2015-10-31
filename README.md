# QuarkPHP
“QuarkPHP”是一个使用PHP开发的微型PHP框架，以“灵活定制、随处可用”为开发理念。没有包含URI路由，但可以轻松与 [nikic/FastRoute](https://github.com/nikic/FastRoute) 或 [c9s/Pux](https://github.com/c9s/Pux) 等成熟的路由程序整合。

## 功能列表
* MVC结构
* 控制器函数return后自动加载视图（可在控制器内启用或禁用）
* HTML静态文件缓存
* Logger日志文件记录器
* 连接器(连接并返回MySQL/PostgreSQL/Redis/Memcache/Mongodb原生对象)
* 支持中文或自定义字符的图形验证码


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

        //载入QuarkPHP文件
        require ROOT_PATH . '/quarkphp.php';

        //执行 QuarkPHP 调度器并传入(控制器名,路由参数)
        \QuarkPHP\Dispatcher::Run($routeInfo[1], $routeInfo[2]);

        break;
}
?>
```
#### 与 [c9s/Pux](https://github.com/c9s/Pux) 路由程序整合示例：
