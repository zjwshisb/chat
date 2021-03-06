<?php

namespace server\http\controller;
use common\lib\exception\ForbiddenException;
use Swoole\Http\Request;
use Swoole\Http\Response;
use common\lib\CResponse;

/**
 * Class Controller
 * @package server\http\controller
 * 控制器基类
 */
abstract class Controller
{
    /**
     * @var null
     */
    protected $user = null;

    /**
     * @var Request
     */
    protected $request;
    /**
     * @var Response
     */
    protected $response;
    /**
     * @var string
     */
    protected $responseType  = CResponse::JSON;
    /**
     * @var string
     */
    protected $responseContent = false;
    /**
     * @var
     */
    protected $params;


    /**
     * Controller constructor.
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return mixed|string
     * @throws ForbiddenException
     */
    public function run($params)
    {
        $this->params = $params;
        if($this->beforeAction()){
            switch ($this->request->server['request_method']) {
                case "GET":
                    $this->responseContent =  $this->view();
                    break;
                case 'POST':
                    $this->responseContent =  $this->create();
                    break;
                case 'PUT':
                    $this->responseContent = $this->update();
                    break;
                case 'DELETE':
                    $this->responseContent = $this->delete();
                    break;
                case 'OPTIONS':
                    $this->responseContent = $this->options();
            }
            $this->formatter();
            $this->afterAction();
            return $this->responseContent;
        }else{
            throw new ForbiddenException();
        }
    }

    public function options()
    {
        return "";
    }

    /**
     * @return mixed
     * get method
     */
    public  function view(){
        return false;
    }

    /**
     * @return mixed
     * put method
     */
    public function update(){
        return false;
    }

    /**
     * @return mixed
     * post method
     */
    public  function create(){
        return false;
    }

    /**
     * @return mixed
     * delete method
     */
    public function delete(){
        return false;
    }

    /**
     * content formatter
     */
    public function formatter(){
        if($this->responseContent !== false ){
            $this->response->header("Content-Type",$this->responseType.";charset=UTF-8");
            if($this->responseType === CResponse::JSON ){
                $this->responseContent = json_encode($this->responseContent,JSON_UNESCAPED_UNICODE);
            }
        }
    }

    /**
     * afterAction
     */
    public function afterAction(){
        //do something here
    }

    /**
     * @return bool
     * beforeAction
     * if return false,the action will not be executed
     */
    public function beforeAction() : bool {
        return true;
    }
}