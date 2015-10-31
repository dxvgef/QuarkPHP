# QuarkPHP
基于 PHP 5.3+ 开发的轻型框架，以“灵活定制、随处可用”为开发理念。没有包含URI路由，但可以轻松与 [nikic/FastRoute](https://github.com/nikic/FastRoute) 或 [c9s/Pux](https://github.com/c9s/Pux) 等成熟的路由程序整合。

框架结构由以下五个类实现不同功能，可根据项目的功能需求删减不需要的类，或按自己的意愿增加、修改功能。
类都由静态方法组成，可通过\命名空间\类名::方法名 的方式直接访问，Base类也可以被控制器类继承后使用self访问。

#### Dispatcher 调度器
* 供URI路由调用
* 在控制器函数return后自动加载视图（可在控制器内启用或禁用）
* HTML静态文件缓存
#### Base 控制器基类
* 可被控制器类继承后以self::方式访问各方法
* 获取URI路由参数
* 手动HTML/JSON视图
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

        //载入QuarkPHP文件
        require ROOT_PATH . '/quarkphp.php';

        //执行 QuarkPHP 调度器并传入(控制器名,路由参数)
        \QuarkPHP\Dispatcher::Run($routeInfo[1], $routeInfo[2]);

        break;
}
?>
```
#### 与 [c9s/Pux](https://github.com/c9s/Pux) 路由程序整合示例：
