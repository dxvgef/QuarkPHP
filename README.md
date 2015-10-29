# QuarkPHP
“夸克PHP”是一个非常微小的PHP框架，框架结构低耦合，可自由定制。

为了便于与其它项目整合，本框架只保留了最基本的MVC结构，因此需要选择与 [nikic/FastRoute](https://github.com/nikic/FastRoute) 、[c9s/Pux](https://github.com/c9s/Pux) 等路由程序搭配使用，以实现统一入口及URI路由解析。


#### 与 nikic/FastRoute 配合使用：
```PHP
<?php
define('ROOT_PATH', dirname($_SERVER['DOCUMENT_ROOT']));
require ROOT_PATH . '/vendor/autoload.php';
//载入QuarkPHP文件
require ROOT_PATH . '/quarkphp.php';

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
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
        //执行QuarkPHP调度器并传入控制器名和路由参数
        \QuarkPHP\Dispatcher::Run($routeInfo[1], $routeInfo[2]);
        break;
}
?>
```
#### 与 c9s/Pux 配合使用：
