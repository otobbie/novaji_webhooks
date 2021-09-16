<?php

namespace App\Http\Controllers;
use PDO;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Spatie\Async\Pool;

class BillingController extends Controller
{
    //

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
           $conn = new PDO($dsn,$dbuser,$dbpass);
           $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
           $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
           $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
           return $conn;
         } catch(PDOException $e) {
           return "Connection failed: " . $e->getMessage();
         }
    }

    public function getCronUsers()
    {
        
        $conn  = $this->pdoConn();
        $stmt = $conn->prepare("SELECT * FROM bet1x_users WHERE cron = 'active'");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;

    }

    public function removeCron($phone)
    {
        
        $conn  = $this->pdoConn();
        $stmt = $conn->prepare("UPDATE bet1x_users SET cron = NULL, plan_mark = NULL WHERE msisdn = ?");
        return $stmt->execute([$phone]);

    }

    public function index(Request $request)
    {
        $users  = $this->getCronUsers();
        if(empty($users)){
            return response(['code'=>'1','message'=>'no users found']);
        }
        $pool = Pool::create();
        $newUsers = [];
        $count = 0;
        
        foreach ($users as $user) {
            $pool->add(function () use ($user){
                // ...
                
                return ['number'=>(string)$user['msisdn'], 'mark'=>(string)$user['plan_mark']];
            })->then(function ($output){
            // On success, `$output` is returned by the process or callable you passed to the queue.
                
                if($output['mark'] == '1'){
                    $oneoff = $this->oneOff($output['number']);
                    $this->removeCron($output['number']);

                }elseif($output['mark'] == '2'){
                    $sub = $this->sub($output['number']);
                    $this->removeCron($output['number']);
                }
                              
            });
        }

        $pool->wait();
    }

    public function logMi($msisdn, $field, $payload)
    {

        $conn  = $this->pdoConn();
        $query = "INSERT INTO mi_payload("
        . "msisdn, field, payload) "
        . "VALUES (?,?,?)";
        try {
            $stmt = $conn->prepare($query);
            $stmt->execute([$msisdn,$field,$payload,]);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function oneOff($msisdn){
        $url = "https://nigeria.mob-intelligence.com/ng/ma/api/external/v1/charge/dob/3446";
        $auth = $this->aesEnc("NLQ1JDDtJBJvE0gg","3693");
        $uuid = Uuid::uuid4(); // ToDo: save this value
        $params = [
            "productId"=>19427,
            "pricepointId"=>55702,
            "mcc"=> "621",
            "mnc"=> "30",
            "msisdn"=>'234'.$msisdn,
            "context"=> "STATELESS",
            "largeAccount"=>"6069",
            "entryChannel"=>"WEB",
            "totalDesired"=> "1000"
        ];
        $headers = [
            "Content-Type:application/json",
            "apikey:6471035d78da4c5889c4c3dc6c77a78a",
            "authentication:$auth",
            "external-tx-id:$uuid"
        ];


        // var_dump($headers); 
        $response = $this->callApi2($msisdn, $params, $url, $headers);
        return $response;
    }

    public function sub($msisdn){
        $url = "https://nigeria.mob-intelligence.com/ng/ma/api/external/v1/subscription/optin/3446";
        $auth = $this->aesEnc("Hd5Bj4TVF4KYTI9k","3236");
        $uuid = Uuid::uuid4();
        $params = [
            "productId"=> 19428,
            "userIdentifier"=>'234'.$msisdn,
            "userIdentifierType"=>"MSISDN",
            "mcc"=> "621",
            "mnc"=> "30",
            "msisdn"=> '234'.$msisdn,
            "largeAccount"=>"6069",
            "entryChannel"=>"WEB"
        ];
        $headers = [
            "Content-Type:application/json",
            "apikey:075ba23b048a4dec9c72e62fe05381f9",
            "authentication:$auth",
            "external-tx-id:$uuid"           
        ];

        $response = $this->callApi2($msisdn, $params, $url, $headers);
        return $response;
    }

    public function callApi2($msisdn, array $params, $url, array $headers = NULL)
    {
     
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        if(!is_null($headers)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);      
        }  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $this->logMi($msisdn, "sent_payload", 'headers: '.json_encode($headers)."\n\n".'request: '.json_encode($params)."\n\nresponse: ".$result);
      
        $err = curl_error($ch);
        // $info  = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        // var_dump($result, $err, $info); exit;
        if ($err){
            return $err;
        }
      
        curl_close($ch);
        if ($result) {
            $response = json_decode($result);
            return $response;
        }
    }

    public function aesEnc($key,$SID)
    {
        $milliseconds = substr(microtime(true) * 1000, 0, 13);//round(microtime(true) * 1000); 
        $content = "$SID"."#".$milliseconds;
        $cipher = "aes-128-ecb"; 
        // $key = "ytynePkg5N7oBG1a";
        $iv = "";
        

        if (in_array($cipher, openssl_get_cipher_methods())) {
            return openssl_encrypt($content, $cipher, $key, 0, $iv);
        }
    }

}
