<?php
    $curl = curl_init();

    $request = '{
                    "name": "generateToken",
                    "param": {
                        "email": "admin@gmail.com",
                        "pass": "admin123"
                    }
                }';

    curl_setopt($curl, CURLOPT_URL, 'http://localhost/restapijwt/');
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['content-type: application/json']);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);
    $err = curl_error($curl);

    if($err){
        echo 'Curl Error: ' . $err;
    } else {
        header('content-type: application/jon');
        $response = json_decode($result, true);
        $token = $response['response']['result']['token'];
        curl_close($curl);

        // CALL SECOND API
        $curl = curl_init();
        $request = '{
                        "name": "getCustomerDetails",
                        "param": {
                            "customerId": 4
                        }
                    }';
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://localhost/restapijwt/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $request,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "Authorization: Bearer $token"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
        curl_close($curl);
    }

?>