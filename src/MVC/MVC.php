<?php
namespace MVC;

use Dotenv\Dotenv;
use MVC\View;
use MVC\DB;
use MVC\Redis;

class MVC
{
    const MVC_TOKEN_PREFIX = 'mvc_token_';
    protected $settings = [];
    public $db;

    public function __construct(array $settings = [])
    {
        //TODO cache settings.
        if (!empty($settings)) {
            $this->settings = $settings;
        } else {
            $configPaths = [
                './',
                '../',
            ];
            foreach ($configPaths as $path) {
                $file = $path . 'config.php';
                if (is_file($file)) {
                    $dotenv = new Dotenv($path);
                    $dotenv->overload();
                    $this->settings = include $file;
                    $this->settings['configFile'] = $file;
                    $dotenv = null;
                    break;
                }
            }
        }
        $this->defaults();
    }

    public function defaults()
    {
        //TODO ?? rename settings
        if (!isset($this->settings['appPath'])) {
            $this->settings['appPath'] = '../app/';
        }
        if (!isset($this->settings['viewPath'])) {
            $this->settings['viewPath'] = '../views/';
        }
        if (!isset($this->settings['dbs'])) {
            $this->settings['dbs'] = [
                'db' => [
                    'dsn' => 'mysql:host=localhost;dbname=database',
                    'user' => 'mvc',
                    'pass' => 'password',
                ]
            ];
        }
        if (!isset($this->settings['url'])) {
            $this->settings['url'] = 'http://localhost';
        }
    }

    public function get($name)
    {
        if (isset($this->settings[$name])) {
            return $this->settings[$name];
        } else {
            return null;
        }
    }

    public function set($name, $value)
    {
        $this->settings[$name] = $value;
    }

    protected function routes()
    {
        $paths = [
            './',
            '../',
        ];
        foreach ($paths as $path) {
            $file = $path . 'routes.php';
            if (is_file($file)) {
                return include $file;
            }
        }
    }

    public function page404()
    {
        $this->errorPage('404');
    }

    public function page405()
    {
        $this->errorPage('405');
    }

    public function pageTimeout()
    {
        $this->errorPage('timeout');
    }

    private function errorPage($type = '404', $vars = [])
    {
        if ($type === '404' || $type === '405')
        {
            http_response_code($type);
        }
        $view = new View($this);
        ob_start();
        echo $view->render('errors/' . $type . '.php', $vars);
        session_write_close();
        die(ob_get_clean());
    }

    protected function route($httpMethod = null, $uri = null)
    {
        //TODO cache this.
        $dispatcher = \FastRoute\simpleDispatcher($this->routes());
        if ($httpMethod === null) {
            $httpMethod = $_SERVER['REQUEST_METHOD'];
        }
        if ($uri === null) {
            $uri = $_SERVER['REQUEST_URI'];
        }
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);
        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
        case \FastRoute\Dispatcher::NOT_FOUND:
            $this->page404();
            break;
        case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $this->page405();
            break;
        case \FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $params = $routeInfo[2];
            $parts = explode('@', $handler);
            if(
                isset($parts[0])
                && isset($parts[1])
                && class_exists('\\App\\Controllers\\' . $parts[0])
                && method_exists('\\App\\Controllers\\' . $parts[0], $parts[1])
            ) {
                if (!array_key_exists('flash', $_SESSION)) {
                    $_SESSION['flash'] = [];
                }
                $class = '\\App\\Controllers\\' . $parts[0];
                $method = $parts[1];
                $flash = $_SESSION['flash'];
                $spirit = new $class($this, $params);
                if ($httpMethod != 'CAST') {
                    ob_start();
                }
                echo $spirit->$method();
                if ($flash == $_SESSION['flash']) {
                    $_SESSION['flash'] = [];
                }
                session_write_close();
                if ($httpMethod != 'CAST') {
                    echo ob_get_clean();
                }
            } else {
                $this->page404();
            }
            break;
        }
    }

    public function exception($e)
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        $vars = ['e' => $e];
        $this->errorPage('exp', $vars);
    }

    public function error($no, $str, $file, $line)
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        $vars = ['no'=>$no, 'str' =>$str, 'file'=>$file, 'line'=>$line];
        $this->errorPage('err', $vars);
    }

    public function sessionDestroy()
    {
        unset($_SESSION['momento']);
        $_SESSION = [];
        session_unset();
        if (ini_get('session.use_cookies')) {
            $prms = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $prms['path'], $prms['domain'], $prms['secure'],
                $prms['httponly']);
        }
        session_destroy();
    }

    public function session()
    {
        if (getenv('SESS_OFF') != "true") {
            session_name((getenv('SESS_NAME') ?: 'MVC'));
            session_start();
            if (
                getenv('SESS_TIMEOUT_OFF') != "true"
            ) {
                if (!isset($_SESSION['death'])) {
                    $_SESSION['death'] = time()
                        + (getenv('SESS_TIMEOUT_SECS') ?: 1800);
                } else {
                    if ($_SESSION['death'] < time()) {
                        $this->sessionDestroy();
                        $sr = getenv('SESS_TIMEOUT_REDIRECT');
                        if (!empty($sr)) {
                            header('Location: ' . $sr);
                            exit();
                        } else {
                            $this->pageTimeout();
                        }
                    } else {
                        $_SESSION['death'] = time()
                            + (getenv('SESS_TIMEOUT_SECS') ?: 1800);
                    }
                }
            }
            if (
                getenv('SESS_TOKENS_OFF') != "true"
                && getenv("REDIS_OFF") != "true"
            ) {
                $currentToken = Redis::obj()->get(
                    static::MVC_TOKEN_PREFIX . session_id());
                if (
                    !empty($currentToken)
                    && isset($_SESSION['momento'])
                    && $_SESSION['momento'] != $currentToken
                ) {
                    $this->sessionDestroy();
                } else {
                    if(
                        empty($currentToken)
                        || !isset($_SESSION['momento'])
                    ) {
                        session_regenerate_id();
                    }
                    $newToken = bin2hex(random_bytes(50));
                    $_SESSION['momento'] = $newToken;
                    Redis::obj()->setEx(
                        static::MVC_TOKEN_PREFIX . session_id(),
                        (getenv('SESS_TTL_SECS') ?: 2700),
                        $newToken
                    );
                }
            }
        }
    }

    public function redis()
    {
        if (getenv('REDIS_OFF') != "true") {
            Redis::connect();
        }
    }

    public function run($method = null, $uri = null)
    {
        $this->redis();
        $this->session();
        set_exception_handler([$this, 'exception']);
        set_error_handler([$this, 'error']);
        if (getenv('DB_OFF') != "true") {
            $this->db = new DB($this);
        }
        $this->route($method, $uri);
    }
}
