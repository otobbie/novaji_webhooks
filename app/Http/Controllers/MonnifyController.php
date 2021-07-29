<?php

namespace App\Http\Controllers;
use Log;
use PDO;

date_default_timezone_set('Africa/Lagos');

class MonnifyController extends Controller
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

    public function getUsers($phone){
        $conn  = $this->pdoConn();
        // var_dump($conn); exit;
        $stmt = $conn->prepare("SELECT * FROM monnify_users WHERE msisdn = ? LIMIT 1");
        // $stmt->bindValue(":ref", $re);
        $stmt->execute([$phone]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (object)$result;

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

    public function index(){
        $body = json_decode(@file_get_contents("php://input"));   
        if(isset($body)){

            // Log::info('Showing user: '.@file_get_contents("php://input"));

            $bank_name =$body->bank_name;
            $amount =$body->amount;;
            $phone = $body->phone;
            $customer_name = $body->customer_name;
            $apikey = $body->apikey;
            $secretkey = $body->secretkey;
            $contractcode = $body->contractcode;
            $type = $body->type;
            $merchant = $body->merchant;
            $user = $this->getUsers($phone);

            
            if(isset($user)){
                $accountReference = rand(10000000000000, 99999999999999);
                if(isset($user->reference)){
                    $accountReference = $user->reference;
                }
                
                
            }
            
            if($type == "invoice"){
                return $this->topup($bank_name, $amount, $phone, $customer_name, $apikey, $secretkey, $contractcode);
            }elseif($type == "reserve"){
                return $this->reservedAccounts($apikey, $secretkey, $contractcode, $phone, $merchant, $accountReference);
            }
                     
        }
    }

    public function topup($bank_name, $amount, $phone, $customer_name, $apikey, $secretkey, $contractcode){
        $acc_num = $this->get_invoice($amount, $phone, $customer_name, $apikey, $secretkey, $contractcode); 
        // var_dump($acc_num, isset($acc_num['acc_no'])); exit;
        if(isset($acc_num['acc_no'])){
            $details = $this->get_details($bank_name, $amount, $acc_num['acc_no'], $apikey, $secretkey);
            
            if(isset($details->status_code) == 1){
                $response = [
                    'status_code'=> 1,
                    'status'=>'failed',
                ];
                // echo json_encode($response);
                return json_encode($response);
            }
            
    
            if($details){
                $response = [
                    'status_code'=> 0,
                    'status'=>'success',
                    'customer_name'=> $customer_name,
                    'reference'=>$acc_num['ref'],
                    'trans_ref'=>$acc_num['trans_ref'],
                    'bank_name'=>$acc_num['bank_name'],
                    'amount'=>$amount,
                    'phone'=>$phone,
                    'ussdTemplate'=>$details
                ];
    
                // echo json_encode($response);
                return json_encode($response);
            }else{
                $response = [
                    'status_code'=> 1,
                    'status'=>'failed',
                ];
                // echo json_encode($response);
                return json_encode($response);
            }
        }else{
            $response = [
                'status_code'=> 2,
                'status'=>'failed',
            ];
            // echo json_encode($response);
            return json_encode($response);
        }
        
    
    }
    
    
    public function get_details($bank_name, $amount, $Accountnumber, $apikey, $secretkey){
            $handle = curl_init();
            $url = 'https://api.monnify.com/api/v1/sdk/transactions/banks';
            //'https://sandbox.monnify.com/api/v1/sdk/transactions/banks';
    
            $authorization = $this->authenticate($apikey, $secretkey);
            if(isset($authorization->status_code) == 1){
                $response = [
                    'status_code'=> 1,
                    'status'=>'failed',
                ];
                return json_encode($response);
            }
            
    
            $headers = [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer '.$authorization
            ];
             
            // Set the url
            curl_setopt($handle, CURLOPT_URL, $url);
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
            // Set the result output to be a string.
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    
             
            $output = curl_exec($handle);
    
            $result = json_decode($output);
            // var_dump($result->responseBody); 
            // $responses = $result->responseBody;
            // $banks = [];
            // $count = 0;
            // foreach($responses as $response){
            //     $banks[$count++] = $response->name;
            // }
            // var_dump($banks); 
            
            // exit;
            
            $error =  curl_error($handle);
            if($error){
                echo "error: ".$error;
            }
            curl_close($handle);
            
    
            if($result->responseBody){
                //get USSD code
                $bank_codes = $result->responseBody;
                foreach ($bank_codes as $bankcode) {
                    if (strtolower($bankcode->name) == strtolower($bank_name)){
                        $temp = $bankcode->ussdTemplate;
        
                        $first = str_replace("Amount",$amount,$temp);
        
                        $strr = str_replace("AccountNumber",$Accountnumber,$first);
                        return $strr;
                    }
                }
            }
    
            $response = [
                'status_code'=> 1,
                'status'=>'failed',
            ];
            return json_encode($response);
            
          
    }
    
    public function get_invoice($amount, $phone, $customer_name, $apikey, $secretkey, $contractcode){
        $url = 'https://api.monnify.com/api/v1/invoice/create';
        $invoice_no = rand(1000000000, 9999999999);
        $fields = [
            "amount"=>$amount,
            "invoiceReference"=>$invoice_no,
            "description"=>"Monnify invoice",
            "currencyCode"=>"NGN",
            "contractCode"=>$contractcode,
            "customerEmail"=>"tech-support@novajii.com",
            "customerName"=>$customer_name,
            "expiryDate"=> date('Y-m-d h:i:s',strtotime("+1 day"))
        ];
    
        $authorization = base64_encode("$apikey:$secretkey");
    
    
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic '.$authorization
        ];
    
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
    
        $output = curl_exec($curl);
        $result = json_decode($output);
        // var_dump($result); exit;
    
        $err = curl_error($curl);
        if($err){
            echo "error: ".$err;
        }
    
        curl_close($curl);
    
        if($result->responseCode == 0){
            $response = [
                'ref' => $invoice_no,
                'acc_no' => $result->responseBody->accountNumber,
                'bank_name' => $result->responseBody->bankName,
                'trans_ref' => $result->responseBody->transactionReference,
            ];
            return $response;
        }else{
            $response = [
                'status_code'=> 1,
                'status'=>'failed',
            ];
            return json_encode($response);
        }
    
    }
    
    public function authenticate($apikey, $secretkey){
        $handle = curl_init();
        $url = 'https://api.monnify.com/api/v1/auth/login';
    
        $authorization = base64_encode("$apikey:$secretkey");
    
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic '.$authorization
        ];
         
        // Set the url
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        // Set the result output to be a string.
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    
         
        $output = curl_exec($handle);
    
        $result = json_decode($output);
        $error =  curl_error($handle);
        if($error){
            echo "error: ".$error;
        }
        curl_close($handle);
    
        if($result->responseBody->accessToken){
            $response = $result->responseBody->accessToken;
            return $response;
        }else{
            $response = [
                'status_code'=> 1,
                'status'=>'failed',
            ];
            return json_encode($response);
        }
    }

    public function reservedAccounts($apikey, $secretkey, $contractcode, $phone, $merchant, $accountReference){

        $handle = curl_init();
        $url = 'https://api.monnify.com/api/v2/bank-transfer/reserved-accounts';
    
        // $authorization = base64_encode("$apikey:$secretkey");
        $authorization = $this->authenticate($apikey, $secretkey);
    
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer '.$authorization
        ];

        $fields = [
            "accountReference"=> "$accountReference",
            "accountName"=> "$merchant/$phone",
            "currencyCode"=> "NGN",
            "contractCode"=> "$contractcode",
            "customerEmail"=> "tech-support@novajii.com",
            // "bvn"=> "",
            "customerName"=> "$phone",
            "getAllAvailableBanks"=> false,
            "preferredBanks"=> ["232"]
        ];
         
        // Set the url
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($fields));
        // Set the result output to be a string.
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    
         
        $output = curl_exec($handle);
    
        $result = json_decode($output);
        $error =  curl_error($handle);
        if($error){
            return "error: ".$error;
        }
        curl_close($handle);
    
        return response(['data'=>$result]);

    }

}