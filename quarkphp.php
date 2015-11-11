<?php
namespace QuarkPHP {
	use \PDO;
	use \PDOException;

	/*
	* QuarkPHP运行参数设置
	* \QuarkPHP\Dispatcher::$htmlCache = false;  //开启HTML缓存
	* \QuarkPHP\Dispatcher::$htmlCachePath = '/htmlcache';  //HTML缓存路径
	* \QuarkPHP\Dispatcher::$controllerPath = '/controller';  //控制器文件路径
	* \QuarkPHP\Base::$ModelPath = '/model';  //HTML缓存路径
	* \QuarkPHP\Base::$ViewPath = '/view';  //HTML缓存路径
	* \QuarkPHP\Logger::$Level = 'warn';   //日志记录级别
	* \QuarkPHP\Logger::$Path = '/log';   //日志文件目录
	* 更多配置参数请查看各类中的注释
	*/

	//版本号
	//public static $Version = '1.0.5';

    //调度器
    class Dispatcher {
        public static $htmlCache = false;
        public static $htmlCachePath = '';
        public static $controllerPath = '/controller';

        //执行调度
        static public function Run($controller, $routerParams = array()) {
            //判断并读取HTML缓存文件
            if (self::$htmlCache == true) {
                self::getHTMLCache();
            }

            //解析控制器信息
            $info = self::parseController($controller);
            //判断控制器文件是否存在
            if (!file_exists($info['path'])) {
                echo '控制器文件' . $info['path'] . '不存在';
                exit();
            }
            //载入控制器文件
            require_once($info['path']);
            //判断控制器类及方法是否存在
            if (!method_exists($info['class'], $info['func'])) {
                echo '控制器文件' . $info['path'] . '的' . $info["class"] . '类或其' . $info["func"] . '方法不存在';
                exit();
            }

            //把路由参数传入到
            Base::$RouteParams =& $routerParams;

            //打开输出缓存
            ob_start();

            //执行控制器
            $info['class']::$info['func']();

            //自动执行视图
            switch (Base::$ViewType) {
                case 'html':
                    if (Base::$ViewFile != '') {
                        Base::ShowHTML(Base::$ViewFile, Base::$ViewData);
                    }
                    break;
                case 'json';
					echo json_encode(Base::$ViewData);
					return;
                    break;
            }

            if (self::$htmlCache == true) {
                self::makeHTMLCache();
            }

        }

        private static function getHTMLCache() {
            if (self::$htmlCache == true) {
                $code = md5($_SERVER['REQUEST_URI']);
                $file = ROOT_PATH . '/' . Dispatcher::$htmlCachePath . '/' . $code . '.html';
                if (file_exists($file)) {
                    readfile($file);
                    exit();
                }
            }
        }

        private static function makeHTMLCache() {
            $code = md5($_SERVER['REQUEST_URI']);
            $file = ROOT_PATH . '/' . Dispatcher::$htmlCachePath . '/' . $code . '.html';
            $content = ob_get_contents();//取得php页面输出的全部内容
            $fp = fopen(ROOT_PATH . '/' . Dispatcher::$htmlCachePath . '/' . $code . '.html', 'w'); //创建一个文件，并打开，准备写入
            fwrite($fp, $content); //把php页面的内容全部写入output00001.html，然后……
            fclose($fp);
        }

        //解析控制器文件路径、类名、方法名
        private static function parseController($path) {
            $pathinfo = pathinfo($path);
            $return["class"] = ($pathinfo["filename"] == "") ? "" : $pathinfo["filename"];
            $return["func"] = ($pathinfo["extension"] == "") ? "" : $pathinfo["extension"];
            if ($pathinfo["dirname"] == DIRECTORY_SEPARATOR || $pathinfo["dirname"] == '.') {
                $return["path"] = ROOT_PATH . '/controller/' . $return["class"] . '.php';
            } else {
                $return["path"] = ROOT_PATH . '/controller/' . $return["path"] . '/' . $return["class"] . '.php';
            }
            return $return;
        }
    }

    //可被控制器继承的基类，实现 获取路由参数值、执行视图、载入模型、载入插件等功能
    class Base {
        //接收路由参数的变量
        public static $RouteParams = array();
        public static $ModelPath = '/model';

        //视图文件目录
        public static $ViewPath = '';

        //视图类型：空为不加载视图，HTML加载视图文件，JSON输出JSON格式
        public static $ViewType = '';
        //视图变量
        public static $ViewData = array();
        //视图文件，仅在$viewType='html'时有效
        public static $ViewFile = '';

        //手动载入HTML视图
        public static function ShowHTML($viewFile, $viewData = array()) {
            $viewFile = ROOT_PATH . '/view/' . $viewFile;
            if (file_exists($viewFile)) {
                if (!empty($viewData)) {
                    extract($viewData, EXTR_OVERWRITE);
                }
                include($viewFile);
            } else {
                echo '视图文件' . $viewFile . '不存在';
                exit();
            }
        }

        //手动载入JSON视图
        public static function ShowJSON($viewData = array()) {
            echo json_encode($viewData);
        }

        //载入模型
        public static function Model($quarkModelFile) {
            $quarkModelInfo = self::parsePath($quarkModelFile);
            $quarkModelFile = ROOT_PATH . '/model/' . $quarkModelInfo['path'] . '/' . $quarkModelInfo["class"] . '.php';
            if (file_exists($quarkModelFile)) {
                require_once($quarkModelFile);
            } else {
                echo '模型文件' . $quarkModelFile . '不存在';
                exit();
            }
        }

        //解析文件路径和类名
        private static function parsePath($path) {
            $pathinfo = pathinfo($path);
            $return["path"] = ($pathinfo["dirname"] == DIRECTORY_SEPARATOR || $pathinfo["dirname"] == '.') ? "" : $pathinfo["dirname"];
            $return["class"] = ($pathinfo["filename"] == "") ? "" : $pathinfo["filename"];
            return $return;
        }
    }

    //日志记录类
    class Logger {

        //日志记录级别（留空则不记录）
        public static $Level = '';
        public static $Path = '';

        public static function Debug($msg = '') {
            $level = self::level();
            if ($level > 0) {
                $trace = debug_backtrace()[0];
                $info['level'] = 'debug';
                $info['file'] = $trace['file'];
                $info['line'] = $trace['line'];
                $info['msg'] = $msg;
                self::output($info);
            }
        }

        public static function Info($msg = '') {
            $level = self::level();
            if ($level > 0 && $level <= 2) {
                $trace = debug_backtrace()[0];
                $info['level'] = 'info';
                $info['file'] = $trace['file'];
                $info['line'] = $trace['line'];
                $info['msg'] = $msg;
                self::output($info);
            }
        }

        public static function Warn($msg = '') {
            $level = self::level();
            if ($level > 0 && $level <= 3) {
                $trace = debug_backtrace()[0];
                $info['level'] = 'warn';
                $info['file'] = $trace['file'];
                $info['line'] = $trace['line'];
                $info['msg'] = $msg;
                self::output($info);
            }
        }

        public static function Error($msg = '') {
            $level = self::level();
            if ($level > 0 && $level <= 4) {
                $trace = debug_backtrace()[0];
                $info['level'] = 'error';
                $info['file'] = $trace['file'];
                $info['line'] = $trace['line'];
                $info['msg'] = $msg;
                self::output($info);
            }
        }

        public static function Fatal($msg = '') {
            $level = self::level();
            if ($level > 0 && $level <= 5) {
                $trace = debug_backtrace()[0];
                $info['level'] = 'fatal';
                $info['file'] = $trace['file'];
                $info['line'] = $trace['line'];
                $info['msg'] = $msg;
                self::output($info);
            }
        }

        private static function level() {
            switch (self::$Level) {
                case 'debug':
                    return 1;
                    break;
                case 'info':
                    return 2;
                    break;
                case 'warn':
                    return 3;
                    break;
                case 'error':
                    return 4;
                    break;
                case 'fatal':
                    return 5;
                    break;
                default:
                    return 0;
                    break;
            }
        }

        //写日志文件
        private static function output($info = array()) {
            $fp = fopen(ROOT_PATH . '/' . self::$Path . '/' . date('Y-m-d') . '.txt', 'a');
            flock($fp, LOCK_EX | LOCK_NB);
            $content = date('Y-m-d H:i') . ' | ' . $info['level'] . ' | ' . $info['file'] . ' | ' . $info['line'] . "\n" . $_SERVER['REQUEST_URI'] . "\n" . $info['msg'] . "\n\n";
            fwrite($fp, $content);
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    //连接器类
    class Connect {
        //MySQL连接参数
        public static $MySQLconfig = array(
            0 => array(
                'host' => '127.0.0.1',      //主机
                'port' => 3306,             //端口
                'user' => 'root',           //账号
                'pwd' => 'password',        //密码
                'database' => 'test',       //数据库
                'charset' => 'utf-8',       //编码
                'timeout' => 10,            //超时
                'persistent' => false      //持久连接
            )
        );

        //PostgreSQL连接参数
        public static $PGSQLconfig = array(
            0 => array(
                'host' => '127.0.0.1',      //主机
                'port' => 5432,             //端口
                'user' => 'postgres',       //账号
                'pwd' => 'password',        //密码
                'database' => 'test',       //数据库
                'timeout' => 10,            //超时
                'persistent' => false      //持久连接
            )
        );

        //Redis连接参数
        public static $RedisConfig = array(
            0 => array(
                'host' => '127.0.0.1',      //主机
                'port' => 5432,             //端口
                'pwd' => 'password',        //密码
                'database' => 0,            //数据库序号
                'timeout' => 10             //超时
            )
        );

        //MongoDB连接参数
        public static $MongodbConfig = array(
            0 => array(
                'host' => '127.0.0.1',      //主机
                'port' => 5432,             //端口
                'user' => 'user',           //账号
                'pwd' => 'password',        //密码
                'database' => ''           //数据库序号
            )
        );

        //Memcached连接参数
        public static $MemcachedConfig = array(
            0 => array(
                'host' => '127.0.0.1',      //主机
                'port' => 5432,             //端口
                'user' => 10                //超时
            )
        );

        //创建MySQL数据库连接并返回连接对象
        public static function MySQL($configIndex = 0) {
            if (!class_exists('pdo')) {
                Logger::Error('不支持PDO扩展');
                return false;
            }

            try {
                $dsn = 'mysql:host=' . self::$MySQLconfig[$configIndex]['host'] . ';port=' . self::$MySQLconfig[$configIndex]['port'] . ';dbname=' . self::$MySQLconfig[$configIndex]['database'] . ';charset=' . self::$MySQLconfig[$configIndex]['charset'];
                $obj = new PDO($dsn, self::$MySQLconfig[$configIndex]['user'], self::$MySQLconfig[$configIndex]['pwd'], array(PDO::ATTR_TIMEOUT => self::$MySQLconfig[$configIndex]['timeout'], PDO::ATTR_PERSISTENT => self::$MySQLconfig[$configIndex]['persistent']));
                //关闭本地变量值处理，由mysql来转换绑定参数的变量值类型，防止SQL注入
                $obj->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                return $obj;
            } catch (PDOException $e) {
                Logger::Error($e->getMessage());
                return false;
            }
        }

        //创建PostgreSQL数据库连接并返回连接对象
        public static function PGSQL($configIndex = 0) {
            if (!class_exists('pdo')) {
                Logger::Error('PDO组件不存在');
                return false;
            }

            try {
                $dsn = 'pgsql:host=' . self::$PGSQLconfig[$configIndex]['host'] . ';port=' . self::$PGSQLconfig[$configIndex]['port'] . ';dbname=' . self::$PGSQLconfig[$configIndex]['database'];
                $obj = new PDO($dsn, self::$PGSQLconfig[$configIndex]['user'], self::$PGSQLconfig[$configIndex]['pwd'], array(PDO::ATTR_TIMEOUT => self::$PGSQLconfig[$configIndex]['timeout'], PDO::ATTR_PERSISTENT => self::$PGSQLconfig[$configIndex]['persistent']));
                //关闭本地变量值处理，由mysql来转换绑定参数的变量值类型，防止SQL注入
                $obj->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                return $obj;
            } catch (PDOException $e) {
                Logger::Error($e->getMessage());
                return false;
            }
        }

        //创建Redis连接并返回连接对象
        public static function Redis($configIndex = 0) {
            if (!class_exists('Redis')) {
                Logger::Error('不支持Redis扩展');
                return false;
            }

            $obj = new Redis();

            if ($obj->connect(self::$RedisConfig[$configIndex]['host'], self::$RedisConfig[$configIndex]['port'], self::$RedisConfig[$configIndex]['timeout'])) {
                if (self::$RedisConfig[$configIndex]['database'] != 0) {
                    $obj->select(self::$RedisConfig[$configIndex]['database']);
                }
                if (self::$RedisConfig[$configIndex]['pwd'] != '') {
                    $obj->auth(self::$RedisConfig[$configIndex]['pwd']);
                }
                return $obj;
            } else {
                Logger::Error('无法连接Redis服务器' . self::$RedisConfig[$configIndex]['host']);
                return false;
            }
        }

        //创建Mongodb连接并返回连接对象
        public static function Mongodb($configIndex = 0) {
            if (!class_exists('Mongo')) {
                Logger::Error('不支持Mongo扩展');
                return false;
            }

            $dsn = 'mongodb://' . self::$MongodbConfig[$configIndex]['user'] . ':' . self::$MongodbConfig[$configIndex]['pwd'] . '@' . self::$MongodbConfig[$configIndex]['host'] . ':' . self::$MongodbConfig[$configIndex]['port'] . '/' . self::$MongodbConfig[$configIndex]['database'];
            $conn = new Mongo($dsn);

            try {
                $obj = $conn->{self::$MongodbConfig[$configIndex]['database']};
                return $obj;
            } catch (MongoConnectionException $e) {
                Logger::Error($e->getMessage());
                return false;
            }

        }

        //创建Memcached连接并返回连接对象
        public static function Memcached($configIndex = 0) {
            if (!class_exists('memcache')) {
                Logger::Error('不支持memcache扩展');
                return false;
            }

            $obj = new Memcache();

            if ($obj->connect(self::$RedisConfig[$configIndex]['host'], self::$RedisConfig[$configIndex]['port'], self::$RedisConfig[$configIndex]['timeout'])) {
                return $obj;
            } else {
                Logger::Error('无法连接Mmecached服务器' . self::$MemcachedConfig[$configIndex]['host']);
                return false;
            }
        }
    }

    class Verifycode {
        //-------------- 验证码绘制参数 -------------------
        public static $ImageWidth = 85; //图片宽度
        public static $ImageHeight = 25; //图片高度
        public static $ImageBgcolor = array(255, 255, 255); //图片的背景颜色
        public static $StrCount = 4; //显示字符数量
        public static $FontFace = '/data/www/simhei.ttf'; //字体文件的绝对路径
        public static $FontSize = 18; //文字大小(像素)
        public static $FontRotate = 30; //文字旋转角度(0-180)
        public static $FontSpace = 3; //文字间距
        public static $DisturbLine = 15; //干扰曲线数量
        public static $DisturbPixel = 100; //干扰噪点数量
        public static $VarName = 'vcode';   //验证码session变量的名称
        //允许出现的字符，可以是汉字
        public static $AllowStr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'M', 'P', 'R', 'S', 'U', 'W', 'X', 'Y', 'Z');

        public static function show() {
            //缓存控制
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Content-type: image/jpeg');

            $vcode = '';
            //按参数中的宽高来创建一个图像
            $image = imagecreatetruecolor(self::$ImageWidth, self::$ImageHeight);
            //定义图像的背景色
            $bgColor = imagecolorallocate($image, self::$ImageBgcolor[0], self::$ImageBgcolor[1], self::$ImageBgcolor[2]);
            //按高宽绘制一个矩形
            imagerectangle($image, 1, 1, self::$ImageWidth, self::$ImageHeight, $bgColor);
            //填充背景颜色
            imagefill($image, 0, 0, $bgColor);

            //循环绘制干扰曲线
            for ($i = 0; $i < self::$DisturbLine; $i++) {
                //随机定义线条颜色
                $lineColor = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
                //随机位置，角度和弧度来绘制曲线
                imagearc($image, mt_rand(-10, self::$ImageWidth), mt_rand(-10, self::$ImageHeight), mt_rand(30, 300), mt_rand(20, 200), 55, 44, $lineColor);
            }

            //循环绘制干扰噪点
            for ($i = 0; $i < self::$DisturbPixel; $i++) {
                //定义随机噪点颜色
                $pixelColor = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
                //绘制噪点
                imagesetpixel($image, mt_rand(0, self::$ImageWidth), mt_rand(0, self::$ImageHeight), $pixelColor);
            }

            //从预定义字符数组中随机抽取出指定数量的键名，并复制到一个新的数组
            $randKey = array_rand(self::$AllowStr, self::$StrCount);

            //计算文字的顶距
            $topSpace = self::$ImageHeight - ((self::$ImageHeight - self::$FontSize) / 2);
            $i = 0;
            //循环绘制字符
            foreach ($randKey as $key) {
                $i++;
                //拼接字符
                $vcode .= self::$AllowStr[$key];
                //随机定义字符颜色
                $fontColor = imagecolorallocate($image, mt_rand(0, 170), mt_rand(0, 170), mt_rand(0, 170));
                //写入文字（图像，文字大小，旋转角度，文字间距，文字顶距，文字颜色，字体，字符）
                imagettftext($image, self::$FontSize, mt_rand(-self::$FontRotate, self::$FontRotate), (self::$FontSize + self::$FontSpace) * ($i - 0.8), $topSpace, $fontColor, self::$FontFace, self::$AllowStr[$key]);
            }
            //写入session
            $_SESSION[self::$VarName] = $vcode;
            //输出图像
            imagejpeg($image);
            //释放内存
            imagedestroy($image);
        }
    }

    class Upload {
        //上传参数
        static public $option = array(
            'inputName' => '',        //上传控件的name值
            'allowMIME' => array(),    //允许上传的文件MIME值
            'allowSize' => 1024,        //允许上传的文件大小（KB）
            'convertName' => 1,        //是否转换文件名字母大小写，[0不转换/1小写/2大写]
            'savePath' => '',            //上传后保存的绝对路径，基于 ROOT_PATH 常量
            'saveName' => '',            //上传后保存的文件名，不含后缀名
        );

        //执行上传
        static function start() {
            //如果没有定义文件大小限置
            if (self::$option['allowSize'] == 0) {
                return 'allowSize属性值无效(' . self::$option['allowSize'] . ')';
            }
            //如果没有定义保存文件名
            if (self::$option['saveName'] == '') {
                return 'saveName属性值无效(' . self::$option['saveName'] . ')';
            }
            //如果没有上传数据
            if (!isset($_FILES[self::$option['inputName']])) {
                return '请选择要上传的文件';
            }

            //获取原始文件名
            $srcName = $_FILES[self::$option['inputName']]['name'];
            //获取原始文件扩展名
            $srcSuffix = pathinfo($srcName, PATHINFO_EXTENSION);
            //获取原始文件大小
            $srcSize = $_FILES[self::$option['inputName']]['size'];
            //获取原始文件MIME值
            $srcMIME = $_FILES[self::$option['inputName']]['type'];

            //检查文件大小
            if (self::$option['allowSize'] < $srcSize) {
                return '文件大小超出限制(' . $srcSize . ')';
            }
            //检查文件MIME值
            if (empty(self::$option['allowMIME']) == false && in_array($srcMIME, self::$option['allowMIME'], true) == false) {
                return '不允许上传该类型的文件(' . $srcMIME . ')';
            }

            //如果目录不存在
            if (!is_dir(self::$option['savePath'])) {
                //创建目录
                if (!mkdir(self::$option['savePath'])) {
                    return '无法创建文件保存目录(' . self::$option['savePath'] . ')';
                }
            }

            //转换文件名大小写
            switch (self::$option['convertName']) {
                case 1:
                    self::$option['saveName'] = strtolower(self::$option['saveName']);
                    $srcSuffix = strtolower($srcSuffix);
                    break;
                case 2:
                    self::$option['saveName'] = strtoupper(self::$option['saveName']);
                    $srcSuffix = strtoupper($srcSuffix);
                    break;
            }

            //获取上传后的临时文件名
            $tmpName = $_FILES[self::$option['inputName']]['tmp_name'];
            //保存文件
            if (!move_uploaded_file($tmpName, self::$option['savePath'] . '/' . self::$option['saveName'] . '.' . $srcSuffix)) {
                return '文件上传失效，请检查目录权限';
            }

            //获取上传结果
            $filesError = $_FILES[self::$option['inputName']]['error'];

            //判断上传结果
            switch ($filesError) {
                case 0:
                    return self::$option['saveName'] . '.' . $srcSuffix;
                    break;
                case 1:
                    return '文件大小(' . $srcSize . ')超出PHP的限制';
                    break;
                case 2:
                    return '文件大小超出HTML表单中指定的限制';
                    break;
                case 3:
                    return '文件未完整上传';
                    break;
                case 4:
                    return '文件上传失败';
                    break;
                case 5:
                    return '上传文件的大小为0';
                    break;
            }
            return true;
        }
    }
}
?>
