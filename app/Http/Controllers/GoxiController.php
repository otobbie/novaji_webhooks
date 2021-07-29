<?php

namespace App\Http\Controllers;

use PDO;
use mysqli;

class GoxiController extends Controller
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

    public function getTransactionGoxi($reference)
    {
        
        $conn  = $this->pdoConn();
        // var_dump($conn); exit;
        $stmt = $conn->prepare("SELECT * FROM goxi_transactions WHERE reference = ? LIMIT 1");
        // $stmt->bindValue(":ref", $re);
        $stmt->execute([$reference]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (object)$result;

    }

    public function updateTransactionGoxi($reference)
    { 

        $conn  = $this->pdoConn();
        // var_dump($conn); exit;
        $stmt = $conn->prepare("UPDATE goxi_transactions SET status = 'Successful' WHERE reference = ?");
        // $stmt->bindValue(":ref", $re);
        $result = $stmt->execute([$reference]);
        return $result;
    }

    public function soapApi($endpoint, $string){
        
        $location = $url = $endpoint;
    
        $soapUrl = $location;
    
        $xml_post_string = $string; 
                
                
        $headers = array(
            'Content-type: text/xml;charset="utf-8"',
            'Accept: text/xml',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Content-length: ' . strlen($xml_post_string),
        );

        $url = $soapUrl;



        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        
        // curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // converting
        $response = curl_exec($ch);
        curl_close($ch);


        $response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response);
        $xmlObject = simplexml_load_string($response);
        

        return $xmlObject;      
    

    }

    public function createCustomer($apikey,$value, $endpoint){

        $string = '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
        <soap:Body>
        <CreateSinglePolicy xmlns="http://tempuri.org/">
            <Apikey>'.$apikey.'</Apikey>
            <Reference_Num>'.$value['ref'].'</Reference_Num>
            <TransDate>'.$value['transdate'].'</TransDate>
            <ProductID>'.$value['product_id'].'</ProductID>
            <ProductName>'.$value['product_name'].'</ProductName>
            <ClientID>'.$value['clientid'].'</ClientID>
            <Surname>'.$value['surname'].'</Surname>
            <OtherNames>'.$value['othername'].'</OtherNames>
            <Address>'.$value['address'].'</Address>
            <State>'.$value['state'].'</State>
            <MobilePhone>'.$value['phonenumber'].'</MobilePhone>
            <Email>'.$value['email'].'</Email>
            <sex>'.$value['sex'].'</sex>
            <MaritalStatus>'.$value['maritalStatus'].'</MaritalStatus>
            <AgencyID>'.$value['agentid'].'</AgencyID>
            <AgencyName>'.$value['agentname'].'</AgencyName>
            <StartDate>'.$value['transdate'].'</StartDate>
            <MaturityDate>'.$value['maturitydate'].'</MaturityDate>
            <frequencyofPayment>'.$value['frequency'].'</frequencyofPayment>
            <DateofBirth>'.$value['dob'].'</DateofBirth>
            <AssuredValue>'.$value['assuredvalue'].'</AssuredValue>
            <AmountPayable>'.$value['amount'].'</AmountPayable>
        </CreateSinglePolicy>
        </soap:Body>
    </soap:Envelope>';
        $result = $this->soapApi($endpoint, $string);
        return $result;
    }

    public function policyPayment($apikey,$value, $endpoint){
    
        $string = '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
        <soap:Body>
        <PolicyPayments xmlns="http://tempuri.org/">
            <Apikey>'.$apikey.'</Apikey>
            <DOc_Num>'.$value['docno'].'</DOc_Num>
            <PolicyNo>'.$value['policyno'].'</PolicyNo>
            <InsSurname>'.$value['lastname'].'</InsSurname>
            <InsFirstname>'.$value['firstname'].'</InsFirstname>
            <TransDate>'.$value['transdate'].'</TransDate>
            <CoverCode>'.$value['covercode'].'</CoverCode>
            <BrokerName>'.$value['brokername'].'</BrokerName>
            <InsAddress>'.$value['address'].'</InsAddress>
            <InsMobilePhone>'.$value['phone'].'</InsMobilePhone>
            <InsEmail>'.$value['email'].'</InsEmail>
            <StartDate>'.$value['transdate'].'</StartDate>
            <AmountPaid>'.$value['amount'].'</AmountPaid>
        </PolicyPayments>
        </soap:Body>
    </soap:Envelope>';
        $result = $this->soapApi($endpoint, $string);
        return $result;
    }

    public function makedeposit(){
        $body = json_decode(@file_get_contents("php://input"));
            // if(isset($_POST['ResponseCode']) && $_POST['Message'] && $_POST['Amount'] && $_POST['TraceId'] && $_POST['PhoneNumber'] && $_POST['Refrence'] && $_POST['InstitutionCode'] && $_POST['TransactionId'] && $_POST['TraceId']){
            if($body){
                $ResponseCode = $body->ResponseCode;
                $Message = $body->Message;
                $amount = $body->Amount;
                $TraceId = $body->TraceId;
                $PhoneNumber = $body->PhoneNumber;
                $Refrence = $body->Refrence;
                $InstitutionCode = $body->InstitutionCode;
                $TransactionId = $body->TransactionId;
                $dummy = "";

                $getUser = $this->getTransactionGoxi($TransactionId);
                
                if($getUser){
                    if ($ResponseCode == "00"){

                        $apikey = "F62FEED2-548F-4EC5-909F-3CAAD8039F03";
                        $endpoint = "http://goxilife.gibsonline.com/webservice/gibs.asmx";

                        //Api to register customers
                        $value['ref'] = rand(1000000000,9999999999);
                        $value['transdate'] = date('Y-m-d');
                        $value['product_id'] =  $getUser->covercode;
                        $value['product_name'] =  $getUser->product_name;
                        $value['clientid'] = $getUser->msisdn;
                        $value['surname'] = $getUser->lastname;
                        $value['othername'] = $getUser->firstname;
                        $value['address'] = "";
                        $value['state'] = "";
                        $value['phonenumber'] = $getUser->msisdn;
                        $value['email'] = "";
                        $value['sex'] = "";
                        $value['maritalStatus'] = "";
                        $value['maturitydate'] = date('Y-m-d',strtotime(date("Y-m-d", time()) . " + 365 day"));
                        $value['frequency'] = "monthly";
                        $value['dob'] = date('Y-m-d');
                        $value['amount'] = $getUser->amount;
                        $value['assuredvalue'] = $getUser->assured;
                        $value['agentid'] = $getUser->agentid;
                        $value['agentname'] = $getUser->agentname;
                        $policyno = "";
                        
                        $productList = $this->createCustomer($apikey,$value,$endpoint);

                        if($productList->soapBody->CreateSinglePolicyResponse->CreateSinglePolicyResult){
                            $result = $productList->soapBody->CreateSinglePolicyResponse->CreateSinglePolicyResult;
                            $number_array = explode('for ', $result);
                            $policyno = (string)$number_array[1]; 
                        }


                        //pay for policy
                        $value = array(
                            "docno"=>$getUser->docno,
                            "policyno"=>$policyno,
                            "lastname"=>$getUser->lastname,
                            "firstname"=>$getUser->firstname,
                            "transdate"=>$getUser->transdate,
                            "covercode"=>$getUser->covercode,
                            "address"=>$getUser->address,
                            "phone"=>$getUser->msisdn,
                            "email"=>$getUser->email,
                            "amount"=>$getUser->amount,
                            "brokername"=>$getUser->agentname
                        );
                        // var_dump($value); exit;
                        // $helper =  new Helpers;
                        $results = $this->policyPayment($apikey,$value, $endpoint);
                        // var_dump($results); exit;
                        if($results){
                            $this->updateTransactionGoxi($TransactionId);
                            return response(['response'=>$results, 200]);
                        }
                    }
                    
                }else{
                    return response(['response'=>"Some error occurred", 401]);
                }

                
            }
    }




}