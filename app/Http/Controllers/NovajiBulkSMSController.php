<?php

namespace App\Http\Controllers;

use PDO;
use mysqli;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NovajiBulkSMSController extends Controller
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

    public function send(Request $request)
    {
        $username = $request->username;
        $password = $request->password;
        $sender = $request->sender;
        $message = $request->message;
        $phones = $request->phone;

        // SMS Endpoint check
        $url1 ="https://novajii.com/sendsms";
        $url2 ="https://novajii.com/ords/sms/api/sms";
        $url3 ="https://novajii.com/api2/sms/send";

        $response = [];
        foreach($phones as $destination) {
            $msg = Http::get("$url3?username=$username&sender=$sender&password=$password&destination=$destination&message=$message");
            $string = $msg->body();
            $result = explode(":",$string);
            $success = $result[0];
            array_push($response, [$destination => $result[1]]);

        }
        return \response(["message"=> "Sent", "messageId" => $response]);
    }

    public function getBalanceRouteMobile()
    {
        $response = Http::get("http://ngn.rmlconnect.net:8080/CreditCheck/checkcredits?username=NovajiCor&password=tTvywwRO");
        return $response->body();
    }

    public function getBalance()
    {
        $response = Http::get("http://ngn.rmlconnect.net:8080/CreditCheck/checkcredits?username=NovajiCor&password=tTvywwRO");
        return $response->body();
    }

    public function makeRequest()
    {
        $response = Http::get('https://jsonplaceholder.typicode.com/users');
        return $response->json();
    }

    public function testEnpoint(Request $request)
    {
        $response = Http::post('https://ojtb8cju7x9rtms-ajdb1.adb.uk-london-1.oraclecloudapps.com/ords/sms/api/received-payments', [
            "message" => "Success",
            "username" => "tony.okafor@universalinsuranceplc.com",
            "phone_number" => "09154208438",
            "amount" => 5000,
            "trace_id" => "30162109013",
            "reference" => "0415",
            "payment_type" => "TEST",
            "status" => "000",
            "payment_gateway" => "coralpay",
            "currency" => "NGN"
         ]);
        return $response->body();
    }

    public function getUserDetails($phone)
    {
        $conn  = $this->pdoConn();
        $sql = "SELECT * FROM novajii_easpay_users WHERE phone = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$phone]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $row = $stmt->rowCount();

        if (!$result) {
            $msg ="No User Found";
            $code = 404;
            return response(["statusCode"=>$code, "message"=>$msg, "count"=>0], 404);
        }
        $code = 201;
        return response(["statusCode"=>$code, "customer"=>$result, "count"=>$row], 201);
    }

    public function createNewCustomer(Request $request)
    {
        // Database connection
        $conn  = $this->pdoConn();

        //Validation
        $sql = "SELECT * FROM novajii_easpay_users WHERE phone = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$request->phone]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $msg = "This Phone is already registered";
            return response()->json(["message"=>$msg], 400);
        }
        $this->validate($request, [
            "name"=>'required',
            "email"=>'required',
            'phone'=>'required',
            "account_number"=>'required',
            'account_name'=>'required',
            'account_reference'=>'required',
            "bank"=>'required',
            'pin'=>'required'
        ]);

        //Request body
        $name = $request->name;
        $phone = $request->phone;
        $email = $request->email;
        $account_number = $request->account_number;
        $account_name = $request->account_name;
        $account_reference = $request->account_reference;
        $bank = $request->bank;
        $pin = $request->pin;

        // Insert into database
        $sql = "INSERT INTO novajii_easpay_users(name,phone,email,account_number,account_name,account_reference, bank, pin) VALUES(:name, :phone, :email, :account_number, :account_name, :account_reference,:bank, :pin)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            "name"=>$name,
            "phone"=>$phone,
            "email"=>$email,
            "account_number"=>$account_number,
            "account_name"=>$account_name,
            "account_reference"=>$account_reference,
            "bank"=>$bank,
            "pin"=>$pin
        ]);

        $sql = "SELECT * FROM novajii_easpay_users WHERE phone = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$request->phone]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $msg = "Customer Created Successfully";

        return response(["message"=>$msg,"response"=>$result], 200);
    }

    public function fetchUser($phone)
    {
        $conn  = $this->pdoConn();
        $sql = "SELECT * FROM novajii_easpay_users WHERE phone = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$phone]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $row = $stmt->rowCount();
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


}
