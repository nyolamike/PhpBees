<?php
    
    function universe_get_connection($username, $password,$servername="localhost",$databasename="",$is_test=false){
        try {
            if($is_test){
                $conn = new PDO("mysql:host=$servername", $username, $password);
            }else{
                $conn = new PDO("mysql:host=$servername;dbname=$databasename", $username, $password);
            }
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return array($conn,array());
        }catch(PDOException $e)
        {
            return array(null, array($e->getMessage()));
        }
    }

    

    
?>