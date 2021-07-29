<?php

namespace App\Http\Controllers;
use PDO;

class BetdbController extends Controller
{

    public function updateTransaction1xbet($reference)
    {

        $conn  = $this->pdoConn();
        $stmt = $conn->prepare("UPDATE bet1x_transactions SET status = 'Successful' WHERE reference = ?");
        $result = $stmt->execute([$reference]);
        return $result;

    }

    public function getNumberdb($reference)
    {

        $conn  = $this->pdoConn();
        $stmt = $conn->prepare("SELECT `msisdn`,`reference` FROM bet1x_transactions WHERE reference = ? LIMIT 1");
        $stmt->execute([$reference]);
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

   public function number() {
    $body = json_decode(@file_get_contents("php://input"));   
    if(isset($body)){
        $reference = $body->reference;

        $result = $this->getNumberdb($reference);
        return response(['data'=>$result]);
    }
   }

   public function update(){
    $body = json_decode(@file_get_contents("php://input"));   
    if(isset($body)){
        $reference = $body->reference;
        $update = $this->updateTransaction1xbet($reference);
        if($update){
            return response(['data'=>'updated successfully']);
        }else{
            return response(['data'=>'updated failed']);
        }
        
    }
   }

}