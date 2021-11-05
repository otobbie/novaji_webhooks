<?php

namespace App\Http\Controllers;
use PDO;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Spatie\Async\Pool;

class SportsController extends Controller
{

    const BASE = 'https://aux-one.com';
    const KEY = 'hjASWbhdkiqa3w3n32542nNASDda';
    const LOGIN = 'ussd_nigeria_1x';
    const PASSWORD = 'GO3zHcAeFySmyUKl';
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

    public function index(){
        $url = self::BASE.'/api/ussd/sport/';
        // '/events/top/live/ /api/ussd/sport/';
        
        $phone = '8107282467';
        $phone_code = '234';

        $params = [
            'login' => self::LOGIN,
            'pass' => self::PASSWORD,
            'phone' => $phone,
            'phone_code' => $phone_code,
            'sport_id' => 1,
            'page' => 0,
            'show_all' => 1
        ];

        // $url = "https://aux-one.com/api/ussd/users/exists";
        // $payload = [
        //     "data" => [
        //           "type" => "TopEvents", 
        //           "attributes" => [
        //              "login" => self::LOGIN, 
        //              "password" => self::PASSWORD 
        //           ] 
        //        ] 
        //  ];

        // $result = $this->api2($url,$payload);
        // var_dump($result); exit;

        $result = $this->callApi($params, $url, self::KEY);
        $matches = $result->description->events;
        
        foreach($matches as $key => $match){
            if($key < 6){
                $matches = $match[1]." V ".$match[2].". Match code: ".$match[0];
                //save each to the db
                $this->addSports($matches);
            }       
            
        }

    }

    public function addSports($matches){

        $conn  = $this->pdoConn();
        $sql = "INSERT INTO bet1x_sports(matches) VALUES(?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$matches]);
    }

    public function truncateSports(){
        $conn  = $this->pdoConn();
        $sql = "TRUNCATE TABLE bet1x_sports";
        $stmt = $conn->prepare($sql);
        return $stmt->execute();
    }

    public function api2($requestUrl, $payload){

        $requestHash = 'W+Uz8&IdzGgVS>r>@^ts5I,wh7:K+r`*@XMy6nG23@N6qt,~PSd?n(te=qQr>P!';

        $payloadJson = json_encode($payload);

        $signature = hash_hmac('sha256', $payloadJson, $requestHash);

        $headers = [
            "request-signature: {$signature}",
            'Content-Type: application/vnd.api+json',
            'Accept: application/vnd.api+json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $result = json_decode($result);
        $err = curl_error($ch);
        if ($err){
            return $err;
        }
        curl_close($ch);
        return $result;

    }

    public function callApi(array $params, $url, $key)
    {

        ksort($params);
        $params['hash'] = base64_encode(hash_hmac('sha1', http_build_query($params), $key));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
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

}
