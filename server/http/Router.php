<?php

namespace server\http;

use server\http\controller\Controller;
use Swoole\Http\Request;
use Swoole\Http\Response;
use common\lib\exception\FileNotExistException;

class Router
{
    public $map = [];
    public $contentType = [];

    /**
     * Router constructor.
     * @throws FileNotExistException
     */
    public function __construct()
    {
        // 初始化路由
        $routerFile = BASE_ROOT . '/server/http/config/router.php';
        if (is_file($routerFile)) {
            $router = require_once $routerFile;
            foreach ($router as $path => $call) {
                $this->set($path, $call);
            }
        } else {
            throw new FileNotExistException('router file');
        }
        // contentType
        $contentTypeFile = BASE_ROOT . '/server/http/config/contentType.php';
        if (is_file($contentTypeFile)) {
            $this->contentType = require_once $contentTypeFile;
        } else {
            throw new FileNotExistException('content-type file');
        }

    }

    /**
     * @param string $path
     * @param $call
     */
    public function set(string $path, $call)
    {
        $this->map[$path] = $call;
    }

    /**
     * @param string $path
     * @return mixed|string
     */
    public function get(string $path)
    {
        return isset($this->map[$path]) ? $this->map[$path] : '';
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return bool
     * @throws \common\lib\exception\ForbiddenException
     */
    public function dispatch(Request $request, Response $response)
    {
        $response->header("Access-Control-Allow-Origin", "*");
        $response->header("Access-Control-Allow-Methods", "PUT,POST,DELETE,OPTIONS,GET");
        $response->header("Access-Control-Allow-Headers","*");
        $path = $request->server['request_uri'];
        $pathArr = explode("/", substr($path, 1));
        // 如果是静态资源
        if ($pathArr[0] === STATIC_DIR) {
            $file = BASE_ROOT . $path;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (is_file($file)) {
                if (isset($this->contentType[$ext])) {
                    $response->header("Content-Type", $this->contentType[$ext]);
                }
                $response->sendfile($file);
                return true;
            }
        }
        // 动态请求
        if ($callBack = $this->get($path)) {
            if (is_callable($callBack)) {
                $result = call_user_func($callBack, $request, $response);
            }
            if (isset($result) && $result !== false) {
                $response->end($result);
                return true;
            }
        } else { //如果在路由配置中没有找到,则按照默认规则匹配
            $param = [];
            if (isset($pathArr[1])) {
                $param['id'] = $pathArr[1];
            }
            $arr = array_map(function ($val) {
                return ucfirst(strtolower($val));
            }, explode("-", $pathArr[0]));
            $controller = "server\http\controller\\" . implode("", $arr);
            if (class_exists($controller)) {
                $controller = new $controller($request, $response);
                if ($controller instanceof Controller) {
                    $result = $controller->run($param);
                    if($result !== false ){
                        $response->end($result);
                        return true;
                    }
                }
            }
        }
        $response->status(404);
        $response->end('');
    }

}
