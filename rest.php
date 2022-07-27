<?php
require_once('constants.php');

class Rest {
    protected $request;
    protected $serviceName;
    protected $param;
    protected $dbConn;
    protected $userId;

    public function __construct(){
        if($_SERVER['REQUEST_METHOD'] !== 'POST'){
            $this->throwError(REQUEST_METHOD_NOT_VALID, 'Request method is not valid.');
        }
        $handler = fopen('php://input', 'r');
        $this->request = stream_get_contents($handler);
        $this->validateRequest();

        $db = new DbConnect;
        $this->dbConn = $db->connect();

        if('generatetoken' != strtolower($this->serviceName)){
            $this->validateToken();
        }
    }

    //========================================
    // VALIDATE REQUEST
    //========================================

    public function validateRequest(){
        if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
            $this->throwError(REQUEST_CONTENT_TYPE_NOT_VALID, 'Request content type is not valid.');
        }
        $data = json_decode($this->request, true);

        if(!isset($data['name']) || $data['name'] == ''){
            $this->throwError(API_NAME_REQUIRED, 'API name required.');
        }
        $this->serviceName = $data['name'];

        if(!is_array($data['param'])){
            $this->throwError(API_PARAM_REQUIRED, 'API PARAM is required.');
        }
        $this->param = $data['param'];
    }

    //========================================
    // VALIDATE PARAMETER
    //========================================

    public function validateParameter($fieldName, $value, $dataType, $required = true){
        if($required == true && empty($value) == true){
            $this->throwError(VALIDATE_PARAMETER_REQUIRED, $fieldName . ' Parameter is required.');
        }

        //case coming from constants.php

        switch ($dataType) {
            case BOOLEAN:
                if(!is_bool($value)){
                    $this->throwError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for " . $fieldName . '. It should be boolean');
                }
                break;

            case INTEGER:
                if(!is_numeric($value)){
                    $this->throwError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for " . $fieldName . '. It should be numeric');
                }
                break;

            case STRING:
                if(!is_string($value)){
                    $this->throwError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for " . $fieldName . '. It should be string');
                }
                break;
            
            default:
                $this->throwError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for " . $fieldName);
                break;
        }

        return $value;
    }

    //========================================
    // PROCESS API
    //========================================

    public function processApi(){
        $api = new API;
        $rMethod = new reflectionMethod('API', $this->serviceName);
        if(!method_exists($api, $this->serviceName)){
            $this->throwError(API_DOES_NOT_EXIST, 'API does not exist.');
        }
        $rMethod->invoke($api);
    }

    //========================================
    // THROW ERROR
    //========================================

    public function throwError($code, $message){
        header('content-type: application/json');
        $errorMsg = json_encode(array('error' => array('status' => $code, 'message' => $message)));
        echo $errorMsg; exit;
    }

    //========================================
    // RETURN RESPONSE
    //========================================

    public function returnResponse($code, $data){
        header('content-type: application/json');
        $response = json_encode(array('response' => array('status' => $code, 'result' => $data)));
        echo $response; exit;
    }

    //========================================
    // GET AUTHORIZATION HEADER
    //========================================

    public function getAuthorizationHeader(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
    
    //========================================
    // GET BEARER TOKEN 
    //========================================

    public function getBearerToken() {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        $this->throwError(AUTHORIZATION_HEADER_NOT_FOUND, 'Access Token Not found.');
    }

    //========================================
    // VALIDATE TOKEN
    //========================================

    public function validateToken(){
        try {
            $token = $this->getBearerToken();
            $payload = JWT::decode($token, SECRET_KEY, ['HS256']);

            $stmt = $this->dbConn->prepare("SELECT * FROM users WHERE id = :userId");
            $stmt->bindParam(":userId", $payload->userId);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
            if(!is_array($user)){
                $this->returnResponse(INVALID_USER_PASS, 'This user is not found in our database.');
            }

            if($user['active'] == 0){
                $this->returnResponse(USER_NOT_ACTIVE, 'This user may be deactive. Please contact to admin.');
            }

            $this->userId = $payload->userId;
        } catch (Exception $e) {
            $this->returnResponse(ACCESS_TOKEN_ERRORS, $e->getMessage());
        }
    }
}
?>