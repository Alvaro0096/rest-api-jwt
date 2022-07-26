<?php
    class Api extends Rest{

        public function __construct(){
            parent::__construct();
        }

        public function generateToken(){
            $email = $this->validateParameter('email', $this->param['email'], STRING);
            $pass = $this->validateParameter('pass', $this->param['pass'], STRING);

            try {
                $stmt = $this->dbConn->prepare("SELECT * FROM users WHERE email = :email AND password = :pass");
                $stmt->bindParam(":email", $email);
                $stmt->bindParam(":pass", $pass);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
                if(!is_array($user)){
                    $this->returnResponse(INVALID_USER_PASS, 'Email or Password is invalid.');
                }

                if($user['active'] == 0){
                    $this->returnResponse(USER_NOT_ACTIVE, 'User is not activated. Please contact to admin.');
                }

                //JWT.php line 183
                //encode function parameters($payload, $key, $alg, $keyId = null, $head = null);
                //SECRET_KEY FROM constants.php

                $payload = [
                    'iat' => time(),                //current time in timestamp
                    'iss' => 'localhost',
                    'exp' => time() + (60 * 60),         //valid for 60 minutes
                    'userId' => $user['id']       //public claim
                ];
            
                $token = JWT::encode($payload, SECRET_KEY, 'HS256');
                $data = array('token' => $token);
                $this->returnResponse(SUCCESS_RESPONSE, $data);
            } catch (Exception $e) {
                $this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());
            }
        }

        public function addCustomer(){
            $name = $this->validateParameter('name', $this->param['name'], STRING, false);
            $email = $this->validateParameter('email', $this->param['email'], STRING, false);
            $addr = $this->validateParameter('addr', $this->param['addr'], STRING, false);
            $mobile = $this->validateParameter('mobile', $this->param['mobile'], INTEGER, false);

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

                $cust = new Customer;
                $cust->setName($name);
                $cust->setEmail($email);
                $cust->setAddress($addr);
                $cust->setMobile($mobile);
                $cust->setCreatedBy($payload->userId);
                $cust->setCreatedOn(date('Y-m-d'));
                
                $booStatus = true;

                if(!$cust->insert()){
                    $message = 'Failed to insert.';
                    $booStatus = false;
                } else {
                    $message = 'Inserted successfully.';
                }

                $this->returnResponse(SUCCESS_RESPONSE, $message);
            } catch (Exception $e) {
                $this->returnResponse(ACCESS_TOKEN_ERRORS, $e->getMessage());
            }
        }
    }
?>