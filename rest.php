<?php
require_once('constants.php');

class Rest {
    protected $request;
    protected $serviceName;
    protected $param;

    public function __construct(){
        if($_SERVER['REQUEST_METHOD'] !== 'POST'){
            $this->throwError(REQUEST_METHOD_NOT_VALID, 'Request method is not valid.');
        }
        $handler = fopen('php://input', 'r');
        $this->request = stream_get_contents($handler);
        $this->validateRequest();
    }

    public function validateRequest(){
        if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
            $this->throwError(REQUEST_CONTENT_TYPE_NOT_VALID, 'Request content type is not valid.');
        }
        $data = json_decode($this->request, true);

        if(!isset($data['name']) || $data['name'] == ""){
            $this->throwError(API_NAME_REQUIRED, "API name required.");
        }
        $this->serivceName =$data['name'];

        if(!is_array($data['param'])){
            $this->throwError(API_PARAM_REQUIRED, "API PARAM is required.");
        }
        $this->param =$data['param'];
    }

    public function processApi(){

    }

    public function validateParameter($fieldName, $value, $dataType, $requered){

    }

    public function throwError($code, $message){
        header("content-type: application/json");
        $errorMsg = json_encode(array('error' => array('status' => $code, 'message' => $message)));
        echo $errorMsg; exit;
    }

    public function returnResponse(){

    }
}
?>