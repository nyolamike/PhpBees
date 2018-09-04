<?php

    function tools_chain($array_to_be_chained, $template = ""){
        if(is_array($array_to_be_chained) && count($array_to_be_chained) > 0){
            $str = "";
            for ($i=0; $i < count($array_to_be_chained); $i++) { 
                // _dump($array_to_be_chained[$i]);
                $str_temp = ($template != "")?(str_ireplace("{}",$array_to_be_chained[$i],$template)) : ($array_to_be_chained[$i]);
                    // _dump($str_temp);
                    $str = $str . " " . $str_temp . ",";
            }
            $str = trim($str);
            $str = substr($str,0,strlen($str)-1);
            return $str;
        }else if(is_array($array_to_be_chained) && count($array_to_be_chained) == 0){
            return $template;
        }else{
            return str_ireplace("{}",$array_to_be_chained,$template);
        }
    }

    function tools_dump($mark,$file,$line,$item){
        echo "<br/><br/>...............................<br/>Start: ".$mark." <br/>In: &nbsp;&nbsp;&nbsp;&nbsp;" . $file . " <br/>On: &nbsp;&nbsp;&nbsp;" . $line ."<br/>...............................<br/><pre><code>";
        var_dump($item);
        echo "</code></pre><br/>...............................<br/>End: ".$mark."<br/>...............................<br/><br/>";
    }

    function tools_dumpx($mark,$file,$line,$item){
        tools_dump($mark,$file,$line,$item);
        exit(0);
    }

    //adopted from
    //https://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
    function tools_startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    function tools_get_app_folder_name(){
        $parts = explode(DIRECTORY_SEPARATOR,__DIR__);
        $folder_name = strtolower(((count($parts)>1)?$parts[count($parts)-2]:$parts[1]));
        return $folder_name;
    }

    function tools_jsonify($json){
        $temp = null;
        if(is_string($json)){
            $temp = json_decode($json, true);
        }else{
            $temp = json_encode($json);
        }
        $error = json_last_error();
        $msg = "";
        switch ($error) {
             case JSON_ERROR_NONE:
                $msg = "";
                break;
             break;
             case JSON_ERROR_DEPTH:
                $msg = "JSON_ERROR_DEPTH - Maximum stack depth exceeded";
                break;
             break;
             case JSON_ERROR_STATE_MISMATCH:
                $msg = "JSON_ERROR_STATE_MISMATCH - Underflow or the modes mismatch";
                break;
             break;
             case JSON_ERROR_CTRL_CHAR:
                $msg = "JSON_ERROR_CTRL_CHAR - Unexpected control character found";
                break;
             break;
             case JSON_ERROR_SYNTAX:
                $msg = "JSON_ERROR_SYNTAX - Syntax error, malformed JSON";
                break;
             break;
             case JSON_ERROR_UTF8:
                $msg = "JSON_ERROR_UTF8 - Malformed UTF-8 characters, possibly incorrectly encoded";
                break;
             break;
             default:
                $msg = "JSON - Unknown error";
                break;
             break;
        }
        return array($temp,(strlen($msg)>0?array($msg):array()));
    }

    
    function tools_sanitise_name($val){
        //nyd
        //take out any special character that is not alpha numeric
        $str = preg_replace("/[^A-Za-z0-9 ]/", '', strval($val));
        $str = strtolower(str_replace(" ", "_", $str));
        return $str;
    }
   

    

    function tools_create($node){
        $res = array(null,array());
        $res_data = array();
        $errors = array();
        if(array_key_exists("_hive",$node)){
            
            //nyd
            //authorised to access this resource

            //creating a new hive for this user in this garden
            //nyd
            //validate data
            //hive name has to be unique
            //email has to be unique etc
            //and phone number

            $res_data["_hive"] = $node["_hive"];
            $hive_name = strtolower($node["_hive"]);
            //_using what
        }

        $res[0] = $res_data;
        if(count($errors)>0){
            $res[0] = null; //nullify results if there are any errors
            $res[1] = $errors;
        }
        return $res;
    }

    function tools_respond($data,$errors){
        global $conn;
        global $processes_route;
        global $foo;
        $processes_route = TRUE;
        $conn = null;
        //The response
        $data["errors"] = $errors;
        $data["foo"] = $foo;
		//include the header 
        header("Content-type: application/json"); 
        $res =  json_encode($data);
		echo $res;
		exit(0);
    }

    function tools_reply($data,$errors,$connections){
        //close all connnections
        foreach ($connections as $conn) {
            $conn = null;
        }
        //The response
        $data["_errors"] = $errors;
		//include the header 
        header("Content-type: application/json"); 
        $res =  json_encode($data);
        echo $res;
		exit(0);
    }
?>