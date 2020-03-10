<?php
require_once(PLUGIN_PATH . "/sossdata/SOSSData.php");
require_once(PLUGIN_PATH . "/phpcache/cache.php");

class FBMessangerPlatform {
    
    function __construct(){
        
    }

    public function postWebhook($req,$res){
        //$appsecret = 'EAADLGyJrZBTcBALGRs2SLzP3WkhtZBsrLCpMSCYCpUrAjNZCellpch9UR0oxHygc4VCWjopOklNkfTOPE4h4vsKYBxYKAcVJM1ZBHeZAHmpZB32XPUkr8Aj0tWV3GaBdzYUpZCzrCZCyAGsvd16R5L9fYBauKYjWnfIQpR9RMfwg8gZDZD';
        $raw_post_data = $req->Body();
       //echo $req->Header("X-Hub-Signature");
        //var_dump($req->Headers()->{"X-Hub-Signature"});
        $header_signature = $req->Headers()->{"X-Hub-Signature"};
        //echo $raw_post_data;
        if($this->varify($raw_post_data,$header_signature)){
            //preg_replace('/"id":(\d+)/', '"id":"$1"', $raw_post_data);
            $messageBody=json_decode($raw_post_data);
            //var_dump($raw_post_data);
            CacheData::setObjects(md5($header_signature),"fb_msg",$messageBody);
            if($messageBody->object=="page"){
                foreach ($messageBody->entry as $value) {
                    $pageid= $value->id;
                    if(isset($value->messaging))
                    foreach ($value->messaging as $msg) {
                        $m=new stdClass();
                        $m->msgid=$msg->message->mid;
                        $m->psid=$msg->sender->id;
                        $m->pageid=$pageid;
                        $m->message=$msg->message->text;
                        //$m->message=$msg->message->text;
                        $result=SOSSData::Insert ("fb_messages", $m,$tenantId = null);
                        CacheData::setObjects($m->msgid,"fb_msg_received",$m);
                        $res=$this->sendMessage($msg->sender->id,"received");
                        return $result;
                        //$res=$this->sendMessage(c,"received");
                    }
                }
            }

        }else{
            return "Error varifying ";
        }
        return "not processed";
    }

    private function sendMessage($recipient, $textMessage) {
        
         $m =array("messaging_type"=>"RESPONSE","recipient"=>array("id"=>$recipient),"message"=>array("text"=>$textMessage));

         //echo json_encode($m);
         $url = "https://graph.facebook.com/v6.0/me/messages?access_token=" . FB_MSG_APP_S;
         
         //$context = stream_context_create($options);
         $options = array(
            'http' => array(
            'method' => 'POST',
            'content' => json_encode($m),
            'header' => "Content-Type: application/json\r\n" .
            "Accept: application/json\r\n"
            ));
            $context = stream_context_create($options);

            $result = file_get_contents($url, false, $context);
         echo $result;
         //$json=json_($result);
         //return $json;
    }

    private  function callRest($url, $jsonObj = null, $method="GET", $headerArray=null){
        $ch = curl_init();
        //$url 
        curl_setopt ($ch, CURLOPT_URL, $url);
       if (!isset($headerArray))
            $headerArray = array("Content-Type: application/json");

        curl_setopt ($ch, CURLOPT_HTTPHEADER, $headerArray);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (isset($jsonObj)){
            //curl_setopt ($ch, CURLOPT_POST, 1);
            curl_setopt ($ch, CURLOPT_POSTFIELDS, json_encode($jsonObj));
        }

        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        if(curl_errno($ch)){
            echo 'Curl error: ' . curl_error($ch);
        }
        //echo $response;
        curl_close($ch);
        return $response;
    }

    private function varify($raw_post_data,$header_signature){
        $expected_signature = hash_hmac('sha1', $raw_post_data, FB_MSG_APP_S);
        //echo $expected_signature;
        $signature = '';
        if(
            strlen($header_signature) == 45 &&
            substr($header_signature, 0, 5) == 'sha1='
        ) {
        $signature = substr($header_signature, 5);
        }
        if (hash_equals($signature, $expected_signature)) {
            return true;
        }else{
            return true;
        }
    }

    public function getWebhook($req,$res){
         $appsecret = '123';
         
         //var_dump($_GET);
         $mode = $_GET['hub_mode'];
         $token = $_GET["hub_verify_token"];
         $challenge = $_GET["hub_challenge"];


         if (isset($mode) && isset($token)) {
            if ($mode === 'subscribe' && $token === FB_CHALLENGE) {
                echo  $challenge;
                exit();
            }else{
                $res->SetError("Error");
                exit();
            }
         }else{
             $res->SetError("Error");
             exit();
         }
        /*
        $appsecret = 'EAADLGyJrZBTcBALGRs2SLzP3WkhtZBsrLCpMSCYCpUrAjNZCellpch9UR0oxHygc4VCWjopOklNkfTOPE4h4vsKYBxYKAcVJM1ZBHeZAHmpZB32XPUkr8Aj0tWV3GaBdzYUpZCzrCZCyAGsvd16R5L9fYBauKYjWnfIQpR9RMfwg8gZDZD';
        $raw_post_data = file_get_contents('php://input');
        $header_signature = $headers['X-Hub-Signature'];

        // Signature matching
        $expected_signature = hash_hmac('sha1', $raw_post_data, $appsecret);

        $signature = '';
        if(
            strlen($header_signature) == 45 &&
            substr($header_signature, 0, 5) == 'sha1='
        ) {
        $signature = substr($header_signature, 5);
        }
        if (hash_equals($signature, $expected_signature)) {
            echo('SIGNATURE_VERIFIED');
        }*/
    }

}
?>