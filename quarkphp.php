<?php
namespace QuarkPHP {

    //����QuarkPHP���в���
    Dispatcher::$htmlCache = false;  //����HTML����
    Dispatcher::$htmlCachePath = '/htmlcache';  //HTML����·��
    Dispatcher::$controllerPath = '/controller';  //�������ļ�·��
    Base::$ModelPath = '/model';  //HTML����·��
    Base::$ViewPath = '/view';  //HTML����·��
    Logger::$Level = 'warn';   //��־��¼����
    Logger::$Path = '/log';   //��־�ļ�Ŀ¼

    //������
    class Dispatcher {
        public static $htmlCache = false;
        public static $htmlCachePath = '';
        public static $controllerPath = '/controller';

        //ִ�е���
        static public function Run($controller, $routerParams = array()) {
            //�жϲ���ȡHTML�����ļ�
            if (self::$htmlCache == true) {
                self::getHTMLCache();
            }

            //������������Ϣ
            $info = self::parseController($controller);
            //�жϿ������ļ��Ƿ����
            if (!file_exists($info['path'])) {
                echo '�������ļ�' . $info['path'] . '������';
                exit();
            }
            //����������ļ�
            require_once($info['path']);
            //�жϿ������༰�����Ƿ����
            if (!method_exists($info['class'], $info['func'])) {
                echo '�������ļ�' . $info['path'] . '��' . $info["class"] . '�����' . $info["func"] . '����������';
                exit();
            }

            //��·�ɲ������뵽
            Base::$RouteParams =& $routerParams;

            //���������
            ob_start();

            //ִ�п�����
            $info['class']::$info['func']();

            //�Զ�ִ����ͼ
            switch (Base::$ViewType) {
                case 'html':
                    if (Base::$ViewFile != '') {
                        Base::ShowHTML(Base::$ViewFile, Base::$ViewData);
                    }
                    break;
                case 'json';
                    return json_encode(Base::$ViewData);
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
            $content = ob_get_contents();//ȡ��phpҳ�������ȫ������
            $fp = fopen(ROOT_PATH . '/' . Dispatcher::$htmlCachePath . '/' . $code . '.html', 'w'); //����һ���ļ������򿪣�׼��д��
            fwrite($fp, $content); //��phpҳ�������ȫ��д��output00001.html��Ȼ�󡭡�
            fclose($fp);
        }

        //�����������ļ�·����������������
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

    //�ɱ��������̳еĻ��࣬ʵ�� ��ȡ·�ɲ���ֵ��ִ����ͼ������ģ�͡��������ȹ���
    class Base {
        //����·�ɲ����ı���
        public static $RouteParams = array();
        public static $ModelPath = '/model';

        //��ͼ�ļ�Ŀ¼
        public static $ViewPath = '';

        //��ͼ���ͣ���Ϊ��������ͼ��HTML������ͼ�ļ���JSON���JSON��ʽ
        public static $ViewType = '';
        //��ͼ����
        public static $ViewData = array();
        //��ͼ�ļ�������$viewType='html'ʱ��Ч
        public static $ViewFile = '';

        //�ֶ�����HTML��ͼ
        public static function ShowHTML($viewFile, $viewData = array()) {
            $viewFile = ROOT_PATH . '/view/' . $viewFile;
            if (file_exists($viewFile)) {
                if (!empty($viewData)) {
                    extract($viewData, EXTR_OVERWRITE);
                }
                include($viewFile);
            } else {
                echo '��ͼ�ļ�' . $viewFile . '������';
                exit();
            }
        }

        //�ֶ�����JSON��ͼ
        public static function ShowJSON($viewData = array()) {
            echo json_encode($viewData);
        }

        //����ģ��
        public static function Model($quarkModelFile) {
            $quarkModelInfo = self::parsePath($quarkModelFile);
            $quarkModelFile = ROOT_PATH . '/model/' . $quarkModelInfo['path'] . '/' . $quarkModelInfo["class"] . '.php';
            if (file_exists($quarkModelFile)) {
                require_once($quarkModelFile);
            } else {
                echo 'ģ���ļ�' . $quarkModelFile . '������';
                exit();
            }
        }

        //�����ļ�·��������
        private static function parsePath($path) {
            $pathinfo = pathinfo($path);
            $return["path"] = ($pathinfo["dirname"] == DIRECTORY_SEPARATOR || $pathinfo["dirname"] == '.') ? "" : $pathinfo["dirname"];
            $return["class"] = ($pathinfo["filename"] == "") ? "" : $pathinfo["filename"];
            return $return;
        }
    }

    //��־��¼��
    class Logger {

        //��־��¼���������򲻼�¼��
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

        //д��־�ļ�
        private static function output($info = array()) {
            $fp = fopen(ROOT_PATH . '/' . self::$Path . '/' . date('Y-m-d') . '.txt', 'a');
            flock($fp, LOCK_EX | LOCK_NB);
            $content = date('Y-m-d H:i') . ' | ' . $info['level'] . ' | ' . $info['file'] . ' | ' . $info['line'] . "\n" . $_SERVER['REQUEST_URI'] . "\n" . $info['msg'] . "\n\n";
            fwrite($fp, $content);
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    //��������
    class Connect {
        //MySQL���Ӳ���
        public static $MySQLconfig = array(
            0 => array(
                'host' => '127.0.0.1',      //����
                'port' => 3306,             //�˿�
                'user' => 'root',           //�˺�
                'pwd' => 'password',        //����
                'database' => 'test',       //���ݿ�
                'charset' => 'utf-8',       //����
                'timeout' => 10,            //��ʱ
                'persistent' => false      //�־�����
            )
        );

        //PostgreSQL���Ӳ���
        public static $PGSQLconfig = array(
            0 => array(
                'host' => '127.0.0.1',      //����
                'port' => 5432,             //�˿�
                'user' => 'postgres',       //�˺�
                'pwd' => 'password',        //����
                'database' => 'test',       //���ݿ�
                'timeout' => 10,            //��ʱ
                'persistent' => false      //�־�����
            )
        );

        //Redis���Ӳ���
        public static $RedisConfig = array(
            0 => array(
                'host' => '127.0.0.1',      //����
                'port' => 5432,             //�˿�
                'pwd' => 'password',        //����
                'database' => 0,            //���ݿ����
                'timeout' => 10             //��ʱ
            )
        );

        //MongoDB���Ӳ���
        public static $MongodbConfig = array(
            0 => array(
                'host' => '127.0.0.1',      //����
                'port' => 5432,             //�˿�
                'user' => 'user',           //�˺�
                'pwd' => 'password',        //����
                'database' => ''           //���ݿ����
            )
        );

        //Memcached���Ӳ���
        public static $MemcachedConfig = array(
            0 => array(
                'host' => '127.0.0.1',      //����
                'port' => 5432,             //�˿�
                'user' => 10                //��ʱ
            )
        );

        //����MySQL���ݿ����Ӳ��������Ӷ���
        public static function MySQL($configIndex = 0) {
            if (!class_exists('pdo')) {
                Logger::Error('��֧��PDO��չ');
                return false;
            }

            try {
                $dsn = 'mysql:host=' . self::$MySQLconfig[$configIndex]['host'] . ';port=' . self::$MySQLconfig[$configIndex]['port'] . ';dbname=' . self::$MySQLconfig[$configIndex]['database'] . ';charset=' . self::$MySQLconfig[$configIndex]['charset'];
                $obj = new PDO($dsn, self::$MySQLconfig[$configIndex]['user'], self::$MySQLconfig[$configIndex]['pwd'], array(PDO::ATTR_TIMEOUT => self::$MySQLconfig[$configIndex]['timeout'], PDO::ATTR_PERSISTENT => self::$MySQLconfig[$configIndex]['persistent']));
                //�رձ��ر���ֵ������mysql��ת���󶨲����ı���ֵ���ͣ���ֹSQLע��
                $obj->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                return $obj;
            } catch (PDOException $e) {
                Logger::Error($e->getMessage());
                return false;
            }
        }

        //����PostgreSQL���ݿ����Ӳ��������Ӷ���
        public static function PGSQL($configIndex = 0) {
            if (!class_exists('pdo')) {
                Logger::Error('PDO���������');
                return false;
            }

            try {
                $dsn = 'pgsql:host=' . self::$PGSQLconfig[$configIndex]['host'] . ';port=' . self::$PGSQLconfig[$configIndex]['port'] . ';dbname=' . self::$PGSQLconfig[$configIndex]['database'];
                $obj = new PDO($dsn, self::$PGSQLconfig[$configIndex]['user'], self::$PGSQLconfig[$configIndex]['pwd'], array(PDO::ATTR_TIMEOUT => self::$PGSQLconfig[$configIndex]['timeout'], PDO::ATTR_PERSISTENT => self::$PGSQLconfig[$configIndex]['persistent']));
                //�رձ��ر���ֵ������mysql��ת���󶨲����ı���ֵ���ͣ���ֹSQLע��
                $obj->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                return $obj;
            } catch (PDOException $e) {
                Logger::Error($e->getMessage());
                return false;
            }
        }

        //����Redis���Ӳ��������Ӷ���
        public static function Redis($configIndex = 0) {
            if (!class_exists('Redis')) {
                Logger::Error('��֧��Redis��չ');
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
                Logger::Error('�޷�����Redis������' . self::$RedisConfig[$configIndex]['host']);
                return false;
            }
        }

        //����Mongodb���Ӳ��������Ӷ���
        public static function Mongodb($configIndex = 0) {
            if (!class_exists('Mongo')) {
                Logger::Error('��֧��Mongo��չ');
                return false;
            }

            $dsn = 'mongodb://' . self::$MongodbConfig[$configIndex]['user'] . ':' . self::$MongodbConfig[$configIndex]['pwd'] . '@' . self::$MongodbConfig[$configIndex]['host'] . ':' . self::$MongodbConfig[$configIndex]['port'] . '/' . self::$MongodbConfig[$configIndex]['database'];
            $conn = new Mongo($dsn);

            try {
                $obj = $conn->self::$MongodbConfig[$configIndex]['database'];
                return $obj;
            } catch (MongoConnectionException $e) {
                Logger::Error($e->getMessage());
                return false;
            }

        }

        //����Memcached���Ӳ��������Ӷ���
        public static function Memcached($configIndex = 0) {
            if (!class_exists('memcache')) {
                Logger::Error('��֧��memcache��չ');
                return false;
            }

            $obj = new Memcache();

            if ($obj->connect(self::$RedisConfig[$configIndex]['host'], self::$RedisConfig[$configIndex]['port'], self::$RedisConfig[$configIndex]['timeout'])) {
                return $obj;
            } else {
                Logger::Error('�޷�����Mmecached������' . self::$MemcachedConfig[$configIndex]['host']);
                return false;
            }
        }
    }

    class Verifycode {
        //-------------- ��֤����Ʋ��� -------------------
        public static $ImageWidth = 85; //ͼƬ���
        public static $ImageHeight = 25; //ͼƬ�߶�
        public static $ImageBgcolor = array(255, 255, 255); //ͼƬ�ı�����ɫ
        public static $StrCount = 4; //��ʾ�ַ�����
        public static $FontFace = '/data/www/simhei.ttf'; //�����ļ��ľ���·��
        public static $FontSize = 18; //���ִ�С(����)
        public static $FontRotate = 30; //������ת�Ƕ�(0-180)
        public static $FontSpace = 3; //���ּ��
        public static $DisturbLine = 15; //������������
        public static $DisturbPixel = 100; //�����������
        public static $VarName = 'vcode';   //��֤��session����������
        //������ֵ��ַ��������Ǻ���
        public static $AllowStr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'M', 'P', 'R', 'S', 'U', 'W', 'X', 'Y', 'Z');

        public static function show() {
            //�������
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Content-type: image/jpeg');

            $vcode = '';
            //�������еĿ��������һ��ͼ��
            $image = imagecreatetruecolor(self::$ImageWidth, self::$ImageHeight);
            //����ͼ��ı���ɫ
            $bgColor = imagecolorallocate($image, self::$ImageBgcolor[0], self::$ImageBgcolor[1], self::$ImageBgcolor[2]);
            //���߿����һ������
            imagerectangle($image, 1, 1, self::$ImageWidth, self::$ImageHeight, $bgColor);
            //��䱳����ɫ
            imagefill($image, 0, 0, $bgColor);

            //ѭ�����Ƹ�������
            for ($i = 0; $i < self::$DisturbLine; $i++) {
                //�������������ɫ
                $lineColor = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
                //���λ�ã��ǶȺͻ�������������
                imagearc($image, mt_rand(-10, self::$ImageWidth), mt_rand(-10, self::$ImageHeight), mt_rand(30, 300), mt_rand(20, 200), 55, 44, $lineColor);
            }

            //ѭ�����Ƹ������
            for ($i = 0; $i < self::$DisturbPixel; $i++) {
                //������������ɫ
                $pixelColor = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
                //�������
                imagesetpixel($image, mt_rand(0, self::$ImageWidth), mt_rand(0, self::$ImageHeight), $pixelColor);
            }

            //��Ԥ�����ַ������������ȡ��ָ�������ļ����������Ƶ�һ���µ�����
            $randKey = array_rand(self::$AllowStr, self::$StrCount);

            //�������ֵĶ���
            $topSpace = self::$ImageHeight - ((self::$ImageHeight - self::$FontSize) / 2);
            $i = 0;
            //ѭ�������ַ�
            foreach ($randKey as $key) {
                $i++;
                //ƴ���ַ�
                $vcode .= self::$AllowStr[$key];
                //��������ַ���ɫ
                $fontColor = imagecolorallocate($image, mt_rand(0, 170), mt_rand(0, 170), mt_rand(0, 170));
                //д�����֣�ͼ�����ִ�С����ת�Ƕȣ����ּ�࣬���ֶ��࣬������ɫ�����壬�ַ���
                imagettftext($image, self::$FontSize, mt_rand(-self::$FontRotate, self::$FontRotate), (self::$FontSize + self::$FontSpace) * ($i - 0.8), $topSpace, $fontColor, self::$FontFace, self::$AllowStr[$key]);
            }
            //д��session
            $_SESSION[self::$VarName] = $vcode;
            //���ͼ��
            imagejpeg($image);
            //�ͷ��ڴ�
            imagedestroy($image);
        }
    }

    class Upload {
        //�ϴ�����
        static public $option = array(
            'inputName' => '',        //�ϴ��ؼ���nameֵ
            'allowMIME' => array(),    //�����ϴ����ļ�MIMEֵ
            'allowSize' => 1024,        //�����ϴ����ļ���С��KB��
            'convertName' => 1,        //�Ƿ�ת���ļ�����ĸ��Сд��[0��ת��/1Сд/2��д]
            'savePath' => '',            //�ϴ��󱣴�ľ���·�������� ROOT_PATH ����
            'saveName' => '',            //�ϴ��󱣴���ļ�����������׺��
        );

        //ִ���ϴ�
        static function start() {
            //���û�ж����ļ���С����
            if (self::$option['allowSize'] == 0) {
                return 'allowSize����ֵ��Ч(' . self::$option['allowSize'] . ')';
            }
            //���û�ж��屣���ļ���
            if (self::$option['saveName'] == '') {
                return 'saveName����ֵ��Ч(' . self::$option['saveName'] . ')';
            }
            //���û���ϴ�����
            if (!isset($_FILES[self::$option['inputName']])) {
                return '��ѡ��Ҫ�ϴ����ļ�';
            }

            //��ȡԭʼ�ļ���
            $srcName = $_FILES[self::$option['inputName']]['name'];
            //��ȡԭʼ�ļ���չ��
            $srcSuffix = pathinfo($srcName, PATHINFO_EXTENSION);
            //��ȡԭʼ�ļ���С
            $srcSize = $_FILES[self::$option['inputName']]['size'];
            //��ȡԭʼ�ļ�MIMEֵ
            $srcMIME = $_FILES[self::$option['inputName']]['type'];

            //����ļ���С
            if (self::$option['allowSize'] < $srcSize) {
                return '�ļ���С��������(' . $srcSize . ')';
            }
            //����ļ�MIMEֵ
            if (empty(self::$option['allowMIME']) == false && in_array($srcMIME, self::$option['allowMIME'], true) == false) {
                return '�������ϴ������͵��ļ�(' . $srcMIME . ')';
            }

            //���Ŀ¼������
            if (!is_dir(self::$option['savePath'])) {
                //����Ŀ¼
                if (!mkdir(self::$option['savePath'])) {
                    return '�޷������ļ�����Ŀ¼(' . self::$option['savePath'] . ')';
                }
            }

            //ת���ļ�����Сд
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

            //��ȡ�ϴ������ʱ�ļ���
            $tmpName = $_FILES[self::$option['inputName']]['tmp_name'];
            //�����ļ�
            if (!move_uploaded_file($tmpName, self::$option['savePath'] . '/' . self::$option['saveName'] . '.' . $srcSuffix)) {
                return '�ļ��ϴ�ʧЧ������Ŀ¼Ȩ��';
            }

            //��ȡ�ϴ����
            $filesError = $_FILES[self::$option['inputName']]['error'];

            //�ж��ϴ����
            switch ($filesError) {
                case 0:
                    return self::$option['saveName'] . '.' . $srcSuffix;
                    break;
                case 1:
                    return '�ļ���С(' . $srcSize . ')����PHP������';
                    break;
                case 2:
                    return '�ļ���С����HTML����ָ��������';
                    break;
                case 3:
                    return '�ļ�δ�����ϴ�';
                    break;
                case 4:
                    return '�ļ��ϴ�ʧ��';
                    break;
                case 5:
                    return '�ϴ��ļ��Ĵ�СΪ0';
                    break;
            }
            return true;
        }
    }
}
?>