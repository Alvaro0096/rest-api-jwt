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

            $cust = new Customer;
            $cust->setName($name);
            $cust->setEmail($email);
            $cust->setAddress($addr);
            $cust->setMobile($mobile);
            $cust->setCreatedBy($this->userId);
            $cust->setCreatedOn(date('Y-m-d'));
                
            if(!$cust->insert()){
                $message = 'Failed to insert.';
            } else {
                $message = 'Inserted successfully.';
            }

            $this->returnResponse(SUCCESS_RESPONSE, $message);
        }

        public function getCustomerDetails(){
            $customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);

            $cust = new Customer;
            $cust->setId($customerId);
            $customer = $cust->getCustomerDetailsById();
            
            if(!is_array($customer)){
                $this->returnResponse(SUCCESS_RESPONSE, array('message' => 'Customer details not found.'));
            } 

            $response['customerId']         = $customer['id'];
            $response['customerName']       = $customer['name'];
            $response['customerEmail']      = $customer['email'];
            $response['customerMobile']     = $customer['mobile'];
            $response['customerAddress']    = $customer['address'];
            $response['createdBy']          = $customer['created_user'];
            $response['lastUpdatedBy']      = $customer['updated_user'];

            $this->returnResponse(SUCCESS_RESPONSE, $response);
        }

        public function updateCustomer(){
            $customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);
            $name = $this->validateParameter('name', $this->param['name'], STRING, false);
            $addr = $this->validateParameter('addr', $this->param['addr'], STRING, false);
            $mobile = $this->validateParameter('mobile', $this->param['mobile'], INTEGER, false);

            $cust = new Customer;
            $cust->setId($customerId);
            $cust->setName($name);
            $cust->setAddress($addr);
            $cust->setMobile($mobile);
            $cust->setUpdatedBy($this->userId);
            $cust->setUpdatedOn(date('d-m-Y'));
                
            if(!$cust->update()){
                $message = 'Failed to update user.';
            } else {
                $message = 'User updated successfully.';
            }

            $this->returnResponse(SUCCESS_RESPONSE, $message);
        }

        public function deleteCustomer(){
            $customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);

            $cust = new Customer;
            $cust->setId($customerId);

            if(!$cust->delete()){
                $message = 'Failed to delete user.';
            } else {
                $message = 'User delete successfully.';
            }
            
            $this->returnResponse(SUCCESS_RESPONSE, $message);
        }
    }
?>