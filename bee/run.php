<?php
    /* error reportring */
    error_reporting(E_ALL ^ E_WARNING);
    /*end error reporting*/

    require __DIR__ . '/vendor/autoload.php';
    use Emarref\Jwt\Claim;

    //load layers
    include("tools.php"); //utility layer
    include("Inflect.php"); //pluralisation layer
    include("hive.php"); //database layer
    include("segmentation.php"); //interpretation layer
    include("sqllization.php"); //interpretation layer
    include("production.php"); //production layer
    include("packaging.php"); //production layer

    include("tracers.php"); //debuging layer
    include("register.php"); //debuging layer

    define(BEE_IS_IN_PRODUCTION,false);
    define(BEE_GARDEN,tools_get_app_folder_name());
    define(BEE_BASE_URI, "/".GARDEN."/"."bee/");
    define(BEE_SERVER_NAME, (BEE_IS_IN_PRODUCTION ? "" : "localhost"));
    define(BEE_USER_NAME, (BEE_IS_IN_PRODUCTION ? "" : "root"));
    define(BEE_PASSWORD, (BEE_IS_IN_PRODUCTION ? "" : ""));
    define(BEE_SHOW_SQL_ON_ERRORS, true);
    define(BEE_APP_SECRET,"mysupersecuresecret");
    define(BEE_JWT_AUDIENCE,"mysuperapp");
    define(BEE_JWT_ISSUER,"mysuperapp");
    define(BEE_STRICT_HIVE,false);
    $BEE_JWT_ALGORITHM = new Emarref\Jwt\Algorithm\Hs256(BEE_APP_SECRET);
    $BEE_JWT_ENCRYPTION = Emarref\Jwt\Encryption\Factory::create($BEE_JWT_ALGORITHM);
    define(BEE_RI,0);//RESULTS INDEX
    define(BEE_EI,1);//ERROR INDEX
    define(BEE_SI,2);//STRUCTURE INDEX
    define(BEE_SEP,"__");//
    define(BEE_ANN,"_a");//attribute node name
    define(BEE_WNN,"_w");//where node name
    define(BEE_FNN,"_for");//for node name used in indicating the structure file name
    define(BEE_GARDEN_STUCTURE_FILE_NAME,"bee/_garden.json");
    define(BEE_HIVE_STUCTURE_FILE_NAME,"_hive.json");
    define(BEE_DEFAULT_PASSWORD,"qwerty");
    
    $BEE_ERRORS = array();

    //get the hive of the application
    $tjx_res = tools_jsonify(file_get_contents(BEE_HIVE_STUCTURE_FILE_NAME));
    $BEE_HIVE_STRUCTURE = $tjx_res[0];
    $BEE_ERRORS = array_merge($BEE_ERRORS,$tjx_res[BEE_EI]);
    define(BEE_HIVE_OF_A,$BEE_HIVE_STRUCTURE["hive_of_a"]);
    $BEE_HIVE_CONNECTION = null;

    //the garden structure
    //every hive will have its structure e.g _hive.json
    //but this is the structure of the master hive
    $tj_res = tools_jsonify(file_get_contents(BEE_GARDEN_STUCTURE_FILE_NAME));
    $BEE_GARDEN_STRUCTURE = $tj_res[0]; 
    $BEE_ERRORS = array_merge($BEE_ERRORS,$tj_res[BEE_EI]);

    /*
    //check if we have a hive of a thing
    if(!array_key_exists(BEE_HIVE_OF_A,$BEE_GARDEN_STRUCTURE) && BEE_STRICT_HIVE == false ){
        //insert it into the garden structure
        $BEE_GARDEN_STRUCTURE[BEE_HIVE_OF_A] = array(
            "client_id" => array("fk"),
            "name" => array("vcnn",30),
            "db_name" => array("vcnn",30),
            "status" =>  array("vcnn",30),
            "logo" => array("tn"),
            "banner" => array("tn"),
            "country" => array("tn"),
            "city" => array("tn"),
            "email" => array("tn"),
            "phone_number" => array("tn"),
            "website" => array("tn"),
            "lat_long" => array("tn"),
            "location" => array("tn"),
            "physical_address" => array("tn"),
            "location" => array("tn"),
            "postal_address" => array("tn"),
            "mantra" => array("tn"),
            "description" => array("tn"),
        );

        $BEE_GARDEN_STRUCTURE["client"] = array(
            "name" => array("vcnn",30),
            "email" => array("vcnn",30),
            "phone_number" => array("vcnn",30),
            "country" => array("vcnn",30),
            "code" => array("vcnn",30),
            "password" => array("tn"),
            "status" => array("vcnn",30)
        );

        $BEE_GARDEN_STRUCTURE["token"] = array(
            "json_data" => array("tn"),
            "hashed" => array("tn"),
            "user_id" => array("inn",30)
        );
        $BEE_GARDEN_STRUCTURE["token"][BEE_HIVE_OF_A . "_" . "name"] =  array("vcnn",30);
        
        //var_dump($hive_structure);
        file_put_contents("bee/".$BEE_GARDEN_STRUCTURE[BEE_FNN], json_encode($BEE_GARDEN_STRUCTURE));
        $tj_res = tools_jsonify(file_get_contents(BEE_GARDEN_STUCTURE_FILE_NAME));
        $BEE_GARDEN_STRUCTURE = $tj_res[0]; 
        $BEE_ERRORS = array_merge($BEE_ERRORS,$tj_res[BEE_EI]);
    }
    */

    $hrege_res = hive_run_ensure_garden_exists($BEE_GARDEN_STRUCTURE);
    $BEE_ERRORS = array_merge($BEE_ERRORS,$hrege_res[BEE_EI]);
    $BEE_GARDEN_CONNECTION = $hrege_res[BEE_RI];
    $BEE_GARDEN = null;
    //get in the current state of the garden
    if(count($BEE_ERRORS)==0){
        $hrgg_res = hive_run_get_garden($BEE_GARDEN_STRUCTURE,$BEE_GARDEN_CONNECTION);
        $BEE_ERRORS = array_merge($BEE_ERRORS,$hrgg_res[BEE_EI]);
        $GARDEN_STRUCTURE = $hrgg_res[2];
        //tools_reply($hrgg_res[BEE_RI],$BEE_ERRORS,array($BEE_GARDEN_CONNECTION));
        $BEE_GARDEN = $hrgg_res[BEE_RI];
    }
    $BEE = array(
        "BEE_HIVE_STRUCTURE" => $BEE_HIVE_STRUCTURE,
        "BEE_GARDEN_STRUCTURE" => $GARDEN_STRUCTURE,
        "BEE_GARDEN_CONNECTION" => $BEE_GARDEN_CONNECTION,
        "BEE_HIVE_CONNECTION" => null,
        "BEE_GARDEN" => $BEE_GARDEN,
        "BEE_ERRORS" => $BEE_ERRORS,
        "BEE_JWT_ENCRYPTION" => $BEE_JWT_ENCRYPTION
    );

    function bee_run_register_hive($registration_nector,$bee){
        $hrrh_res = hive_run_register_hive($registration_nector, $bee);
        return $hrrh_res;
    }

    function bee_run_post($nectoroid,$bee,$user_id){
        $res = array(null,array(),null);

        //the login 
        //it has to be the only thing in its request
        if(array_key_exists("_f_login",$nectoroid)){
            $whole_honey = array();
            $login_nector = array(
                "_f_login" => $nectoroid["_f_login"]
            );
            $hrl_res = bee_hive_run_login($login_nector, $bee);
            $whole_honey["_f_login"] = $hrl_res[BEE_RI];
            $res[BEE_RI] = $whole_honey;
            $res[BEE_EI] = array_merge($res[BEE_EI],$hrl_res[BEE_EI]);
            return $res; 
        }

        //go through the entire nectorid processing
        //node by node on the root
        $whole_honey = array();
        foreach ($nectoroid as $root_node_name => $root_node) {
            
            if(tools_startsWith($root_node_name,"_")){
                continue;
            }
            
            //nyd
            //check if user is authorised to post data here

            
            //$srp_res = segmentation_run_process($root_node,$config,$connection);
            //$res[2] = $srp_res[2];//structure
            //var_dump($srp_res);
        }

        $res[BEE_RI] = $whole_honey;
        $res[2] = $hasr_res[2];
        return $res; 
    }
    
    function bee_run_get($nectoroid,$structure,$connection){
        $res = array(null,array(),$structure);
        $sr_res = segmentation_run($nectoroid,$structure,$connection);
        //tools_dump("@1 segmentation_run res: ",__FILE__,__LINE__,$sr_res[BEE_RI]);
        $hasr_res = hive_after_segmentation_run($sr_res,$nectoroid,$structure,$connection);
        $res[BEE_RI] = $hasr_res[BEE_RI];
        $res[BEE_EI] = array_merge($res[BEE_EI],$hasr_res[BEE_EI]);
        $res[2] = $hasr_res[2];
        return $res; 
    }


    //this is the last in this file
    //register my application
    //returns the connection to the hive
    if($BEE_HIVE_STRUCTURE["is_registration_public"] == false){
        $brrh_res = bee_run_register_hive(array(
            "_f_register" => $BEE_HIVE_STRUCTURE["_f_register"]
        ), $BEE);
        $BEE_HIVE_CONNECTION = $brrh_res[BEE_RI];
        $BEE["BEE_HIVE_CONNECTION"] = $BEE_HIVE_CONNECTION;
        //nyd
        //get in the current state of the garden only if there was creation of new
        //hive, the current code  below will run allways 
        if(count($BEE_ERRORS)==0){
            $hrgg_res = hive_run_get_garden($BEE_GARDEN_STRUCTURE,$BEE_GARDEN_CONNECTION);
            $BEE_ERRORS = array_merge($BEE_ERRORS,$hrgg_res[BEE_EI]);
            $GARDEN_STRUCTURE = $hrgg_res[2];
            //tools_reply($hrgg_res[BEE_RI],$BEE_ERRORS,array($BEE_GARDEN_CONNECTION));
            $BEE_GARDEN = $hrgg_res[BEE_RI];
            $BEE = array(
                "BEE_HIVE_STRUCTURE" => $BEE_HIVE_STRUCTURE,
                "BEE_GARDEN_STRUCTURE" => $GARDEN_STRUCTURE,
                "BEE_GARDEN_CONNECTION" => $BEE_GARDEN_CONNECTION,
                "BEE_HIVE_CONNECTION" => $BEE_HIVE_CONNECTION,
                "BEE_GARDEN" => $BEE_GARDEN,
                "BEE_ERRORS" => $BEE_ERRORS,
                "BEE_JWT_ENCRYPTION" => $BEE_JWT_ENCRYPTION
            );
        }    
    }

    function bee_handle_requests($bee){
        $res = array(null,array(),null);
        $res[BEE_EI] = array_merge($bee["BEE_ERRORS"],$res[BEE_EI]);
        $method = "get";
    
        if($_SERVER["REQUEST_METHOD"] == "GET"){
            $method = "get";
        }else if($_SERVER["REQUEST_METHOD"] == "POST"){
            $temp_postdata = file_get_contents("php://input");
            //tools_dumpx("temp_postdata",__FILE__,__LINE__,$temp_postdata);
            $tsji_res = tools_suck_json_into($temp_postdata, array());
            $res[BEE_EI] = array_merge($tsji_res[BEE_EI],$res[BEE_EI]);
            if(count()==0){//no errors
                $postdata = $tsji_res[BEE_RI];
                //tools_dumpx("postdata",__FILE__,__LINE__,$postdata);
                $brp_res = bee_run_post($postdata,$bee,0);
                //tools_dumpx("brp_res post ",__FILE__,__LINE__,$brp_res);
                $res[BEE_EI] = array_merge($res[BEE_EI],$brp_res[BEE_EI]);
                $res[BEE_RI] = $brp_res[BEE_RI];
            }
        }else if($_SERVER["REQUEST_METHOD"] == "PUT"){
            $method = "put";
        }else if($_SERVER["REQUEST_METHOD"] == "DELETE"){
            $method = "delete";
        }

        tools_reply($res[BEE_RI],$res[BEE_EI],array(
            $bee["BEE_GARDEN_CONNECTION"],
            $bee["BEE_HIVE_CONNECTION"]
        ));
    }
    
    

?>