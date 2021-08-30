<?php

namespace App\Http\Controllers;

use PDO;
use mysqli;

class Bet1xController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function makeDeposit(){
        $body = json_decode(@file_get_contents("php://input"));
      
        $this->saveData(json_decode(@file_get_contents("php://input"), true));
        // var_dump($data); exit;
        // if(isset($_POST['ResponseCode']) && $_POST['Message'] && $_POST['Amount'] && $_POST['TraceId'] && $_POST['PhoneNumber'] && $_POST['Refrence'] && $_POST['InstitutionCode'] && $_POST['TransactionId'] && $_POST['TraceId']){
        if($body){

            $ResponseCode = $body->paymentStatus;
            $msisdn = "";
            // $Message = $body->Message;
            $amount = $body->amountPaid;
            // $TraceId = $body->TraceId;
            // $PhoneNumber = $body->PhoneNumber;
            $Reference = $body->transactionReference;
            $account_ref = $body->product->reference;
            // $InstitutionCode = $body->InstitutionCode;
            // $TransactionId = $body->TransactionId;
            // if($body->msisdn){
            //     $msisdn = $body->msisdn;
            // }

            $getUser = $this->getTransactionBet($account_ref);
            // var_dump($getUser); exit;
            if($getUser){
                $msisdn = $getUser->msisdn;
                // $amount = $getUser->amount;
            }else{
                return response(['response'=>"Some error occurred", 200]);
            }


            if ($ResponseCode == "PAID"){
                // var_dump("here"); exit;
                 //API to send payment request to 1xbet
                $url = 'https://aux-one.com/api/ussd/deposit/';
                $key = 'hjASWbhdkiqa3w3n32542nNASDda';
                $login = 'ussd_nigeria_1x';
                $password = 'GO3zHcAeFySmyUKl';
                $phone = $msisdn;
                $phone_code = '234';

                // $helper =  new Helpers;
                $result = $this->callApi($url, $key, $login, $password, $phone, $phone_code, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,$amount);
                // var_dump($result);  exit;
                if($result){
                    if($result->success === true){
                        //update status in db
                        $this->updateTransaction1xbet($account_ref);
                    }
                    return response(['response'=>$result, 200]);
                }
            }
        }
    }

    public function saveData($data){
        // var_dump($data); exit;
        $url = "https://ojtb8cju7x9rtms-adb1.adb.uk-london-1.oraclecloudapps.com/ords/onexbet/api/monnify-webhook";
        //"https://ore.ng/ords/onexbet/api/monnify-webhook";
        // https://ojtb8cju7x9rtms-adb1.adb.uk-london-1.oraclecloudapps.com/ords/onexbet/api/monnify-webhook
        // $hash = Sha256($Username+$Password+$MerchantID+$BatchID+|+Key);  
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ]; 
        
        $response = $this->callApi2($data, $url, $headers);
        return $response;
    }

    public function getTransactionBet($reference){
        // $conn  = $this->mysqliConn();
        // $query = "SELECT * FROM bet1x_transactions WHERE 'reference' = $reference";
        // $result = $conn -> query($query) -> fetch_assoc();
        // var_dump($result);
        // return (object)$result;

        $conn  = $this->pdoConn();
        // var_dump($conn); exit;
        $stmt = $conn->prepare("SELECT * FROM monnify_users WHERE reference = ? LIMIT 1");
        // $stmt->bindValue(":ref", $re);
        $stmt->execute([$reference]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return (object)$result;

    }

    public function mysqliConn(){
        $conn = new mysqli("152.228.212.181","novaji_introserve","Zh7mWr4i0A98L1mX","novaji_introserve","3306");

        // Check connection
        if ($conn -> connect_errno) {
            return "Failed to connect to MySQL: " . $conn -> connect_error;
        }

        return $conn;
    }

    public function pdoConn(){
         $dsn = "mysql:host=152.228.212.181;dbname=novaji_introserve";
             $dbuser = "novaji_introserve";
             $dbpass = "Zh7mWr4i0A98L1mX";
        try {
            // $conn = new PDO("mysql:host=152.228.212.181;dbname=novaji_introserve", "novaji_introserve", "Zh7mWr4i0A98L1mX");
            $conn = new PDO($dsn,$dbuser,$dbpass);
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            return $conn;
          } catch(PDOException $e) {
            return "Connection failed: " . $e->getMessage();
          }
    }

    public function updateTransaction1xbet($reference)
    {

        $conn  = $this->pdoConn();
        // var_dump($conn); exit;
        $stmt = $conn->prepare("UPDATE bet1x_transactions SET status = 'Successful' WHERE reference = ?");
        // $stmt->bindValue(":ref", $re);
        $result = $stmt->execute([$reference]);
        return $result;

    }

    public function callApi2(array $params, $url, array $headers = NULL)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        if(!is_null($headers)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        $err = curl_error($ch);
        if ($err){
            return $err;
        }
        curl_close($ch);
        if ($result) {
            $response = json_decode($result);
            return $response;
        }
    }

    public function callApi($url, $key, $login, $password, $phone, $phone_code, $user_pass = NULL, $betcode = NULL, $sport_id = NULL, $page = NULL, $show_all = NULL, $is_live = NULL, $info = NULL, $match = NULL, $show_desc = NULL, $coupon = NULL, $from = NULL, $to = NULL, $amount = NULL)
        {

            $params = [
                'login' => $login,
                'pass' => $password,
                'phone' => $phone,
                'phone_code' => $phone_code,
            ];

            if(!empty($user_pass) || !is_null($user_pass) ){
                $params['user_pass'] = $user_pass;
            }
            if(!empty($betcode) || !is_null($betcode) ){
                $params['betcode'] = $betcode;
            }
            if(!empty($sport_id) || !is_null($sport_id) ){
                $params['sport_id'] = $sport_id;
            }
            if(!empty($page) || !is_null($page) ){
                $params['page'] = $page;
            }
            if(!empty($show_all) || !is_null($show_all) ){
                $params['show_all'] = $show_all;
            }
            if(!empty($is_live) || !is_null($is_live) ){
                $params['is_live'] = $is_live;
            }
            if(!empty($info) || !is_null($info) ){
                $params['info'] = $info;
            }
            if(!empty($match) || !is_null($match) ){
                $params['match'] = $match;
            }
            if(!empty($show_desc) || !is_null($show_desc) ){
                $params['show_desc'] = $show_desc;
            }
            if(!empty($coupon) || !is_null($coupon) ){
                $params['coupon'] = $coupon;
            }

            if(!empty($from) || !is_null($from) ){
                $params['from'] = $from;
            }
            if(!empty($to) || !is_null($to) ){
                $params['show_desc'] = $show_desc;
            }
            if(!empty($amount) || !is_null($amount) ){
                $params['amount'] = $amount;
            }

            ksort($params);
            $params['hash'] = base64_encode(hash_hmac('sha1', http_build_query($params), $key));

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
            $result = curl_exec($ch);
            // var_dump($result); exit;
            $err = curl_error($ch);
            if($err){
                return $err;
            }
            curl_close($ch);
            if ($result) {
                $response = json_decode($result);
                return $response;
            }
        }


}
