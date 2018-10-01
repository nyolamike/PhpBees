<?php
    /* error reportring */
    error_reporting(E_ALL ^ E_WARNING);
    /*end error reporting*/

    /*cors */
    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }
    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])){
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");      
        }   

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])){
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }
        exit();
    }
    /*end cors */

    require __DIR__ . '/vendor/autoload.php';
    use Emarref\Jwt\Claim;

    //load layers
    include("tools.php"); //utility layer
    include("Inflect.php"); //pluralisation layer
    include("countries_data.php"); //countries data
    include("bee_security.php"); //security layer
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
    //$BEE_JWT_ALGORITHM = new Emarref\Jwt\Algorithm\Rs256(BEE_APP_SECRET);
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
    $BEE_GLOBALS = array(
        "is_login_call" => false
    );
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
        $BEE_GARDEN_STRUCTURE = $hrgg_res[2];
        //tools_reply($hrgg_res[BEE_RI],$BEE_ERRORS,array($BEE_GARDEN_CONNECTION));
        $BEE_GARDEN = $hrgg_res[BEE_RI];
    }
    define(BEE_ENFORCE_RELATIONSHIPS,false); //nyd get value from hive structure
    $BEE = array(
        "BEE_HIVE_STRUCTURE" => $BEE_HIVE_STRUCTURE,
        "BEE_GARDEN_STRUCTURE" => $BEE_GARDEN_STRUCTURE,
        "BEE_GARDEN_CONNECTION" => $BEE_GARDEN_CONNECTION,
        "BEE_HIVE_CONNECTION" => null,
        "BEE_GARDEN" => $BEE_GARDEN,
        "BEE_ERRORS" => $BEE_ERRORS,
        "BEE_JWT_ENCRYPTION" => $BEE_JWT_ENCRYPTION,
        "BEE_USER" => array("id"=>0)
    );
    define(BEE_SUDO_DELETE,$BEE_HIVE_STRUCTURE["sudo_delete"]);

    function bee_run_register_hive($registration_nector,$bee){
        $hrrh_res = hive_run_register_hive($registration_nector, $bee);
        return $hrrh_res;
    }

    function bee_run_post($nectoroid,$bee,$user_id){
        $res = array(null,array(),null);

        
        
        //tools_dumpx("here in post",__FILE__,__LINE__,$nectoroid);

        //go through the entire nectorid processing
        //node by node on the root
        $whole_honey = array();
        foreach ($nectoroid as $root_node_name => $root_node) {
            
            if(tools_startsWith($root_node_name,"_")){
                continue;
            }
            //tools_dumpx("here in post foreach loop",__FILE__,__LINE__,$root_node);
            //nyd
            //check if user is authorised to post data here
            $nector = array();
            $nector[$root_node_name] = $root_node;
            $brp_res = bee_hive_post(
                $nector,
                $bee["BEE_HIVE_STRUCTURE"]["combs"],
                $bee["BEE_HIVE_CONNECTION"],
                $bee["BEE_USER"]["id"],
                $whole_honey
            );
            //tools_dumpx("here brp_res",__FILE__,__LINE__,$brp_res);
            $whole_honey[$root_node_name] = $brp_res[BEE_RI][$root_node_name];
            $res[BEE_EI] = array_merge($res[BEE_EI],$brp_res[BEE_EI]);
        }

        $res[BEE_RI] = $whole_honey;
        $res[2] = $bee;
        return $res; 
    }

    function bee_run_update($nectoroid,$bee,$user_id){
        $res = array(null,array(),null);

        //go through the entire nectorid processing
        //node by node on the root
        $whole_honey = array();
        foreach ($nectoroid as $root_node_name => $root_node) {
            
            if(tools_startsWith($root_node_name,"_")){
                continue;
            }
            //tools_dumpx("here in post foreach loop",__FILE__,__LINE__,$root_node);
            //nyd
            //check if user is authorised to post data here
            $nector = array();
            $nector[$root_node_name] = $root_node;
            $brp_res = bee_hive_update(
                $nector,
                $bee["BEE_HIVE_STRUCTURE"]["combs"],
                $bee["BEE_HIVE_CONNECTION"],
                $bee["BEE_USER"]["id"],
                $whole_honey
            );
            //tools_dumpx("here brp_res",__FILE__,__LINE__,$brp_res);
            $whole_honey[$root_node_name] = $brp_res[BEE_RI][$root_node_name];
            $res[BEE_EI] = array_merge($res[BEE_EI],$brp_res[BEE_EI]);
        }

        $res[BEE_RI] = $whole_honey;
        $res[2] = $bee;
        return $res; 
    }


    function bee_run_delete($nectoroid,$bee,$user_id){
        $res = array(null,array(),null);

        $is_restricted = false;
        if(isset($bee["BEE_HIVE_STRUCTURE"]["is_restricted"])){
            $is_restricted = $bee["BEE_HIVE_STRUCTURE"]["is_restricted"];
        }
        

        //go through the entire nectorid processing
        //node by node on the root
        $whole_honey = array();
        foreach ($nectoroid as $root_node_name => $root_node) {
            
            if(tools_startsWith($root_node_name,"_")){
                continue;
            }
            //tools_dumpx("here in post foreach loop",__FILE__,__LINE__,$root_node);
            //nyd
            //check if user is authorised to delete data here
            $nector = array();
            $nector[$root_node_name] = $root_node;
            
            $brp_res = bee_hive_delete(
                $nector,
                $bee["BEE_HIVE_STRUCTURE"]["combs"],
                $bee["BEE_HIVE_CONNECTION"],
                $bee["BEE_USER"]["id"],
                $is_restricted
            );
            
            //tools_dumpx("here brp_res",__FILE__,__LINE__,$brp_res);
            $whole_honey[$root_node_name] = $brp_res[BEE_RI][$root_node_name];
            $res[BEE_EI] = array_merge($res[BEE_EI],$brp_res[BEE_EI]);
        }

        $res[BEE_RI] = $whole_honey;
        $res[2] = $bee;
        return $res; 
    }

    
    function bee_run_get($nectoroid,$structure,$connection){
        $res = array(null,array(),$structure);
        
        //tools_dump("@0 == ",__FILE__,__LINE__,$nectoroid);
        $sr_res = segmentation_run($nectoroid,$structure,$connection);
        //tools_dump("segmentation_run",__FILE__,__LINE__,$sr_res[BEE_RI]);
        $hasr_res = hive_after_segmentation_run($sr_res,$nectoroid,$structure,$connection);
        $res[BEE_RI] = $hasr_res[BEE_RI];
        $res[BEE_EI] = array_merge($res[BEE_EI],$hasr_res[BEE_EI]);
        $res[2] = $hasr_res[2];
        return $res; 
    }

    if(array_key_exists("drone_security_enabled",$BEE_HIVE_STRUCTURE)){
        $dse = $BEE_HIVE_STRUCTURE["drone_security_enabled"];
        define(BEE_DRONE_SECURITY_ENABLED,$dse);
    }


    


    //nyd
    //get the children_tree from the hive structure

    function bee_handle_requests($bee){
        global $BEE_GLOBALS;
        $res = array(null,array(),null);
        $res[BEE_EI] = array_merge($bee["BEE_ERRORS"],$res[BEE_EI]);
        $method = "get";

        $token_string = null;
        $headers = apache_request_headers();
        if($headers == null){
            array_push($res[BEE_EI],"Request missing headers");
            return $res;
        }
        if(isset($headers["Authorization"]) && stripos($headers["Authorization"],"Bearer ") > -1){
            $token_string = str_ireplace("Bearer ","",$headers["Authorization"]);
        }else if(isset($headers["authorization"]) && stripos($headers["authorization"],"Bearer ") > -1){
            $token_string = str_ireplace("Bearer ","",$headers["Authorization"]);
        }else if(isset($headers["AUTHORIZATION"]) && stripos($headers["authorization"],"Bearer ") > -1){
            $token_string = trim(str_ireplace("Bearer ","",$headers["Authorization"]));
        }
        //tools_dumpx("token_string",__FILE__,__LINE__,$headers["Authorization"]);
        if($token_string != null){
            $jwt = new Emarref\Jwt\Jwt();
            $token = $jwt->deserialize($token_string);
            $context = new Emarref\Jwt\Verification\Context($bee["BEE_JWT_ENCRYPTION"]);
            $context->setAudience('audience_1');
            $context->setIssuer('your_issuer');
            $context->setSubject('api');
            try {
                $jwt->verify($token, $context);
                $payload = $token->getPayload();
                $current_user_id = $payload->findClaimByName("user")->getValue();
                $an = $payload->findClaimByName("app_name")->getValue();
                $user_nector = array(
                    "users" => array(
                        "_w" => array(
                            array(
                                "id","=",$current_user_id
                            )
                        ),
                        "user_roles" => array(
                            "role" => array(
                                "role_permisiions" => array(),
                                "role_modules" => array()
                            )
                        )
                    )
                );
                $brg_res = bee_run_get($user_nector,$bee["BEE_HIVE_STRUCTURE"]["combs"],$bee["BEE_HIVE_CONNECTION"]);
                $res[BEE_EI] = array_merge($res[BEE_EI],$brg_res[BEE_EI]);
                $current_user = $brg_res[BEE_RI]["users"][0];
                //tools_dumpx("foo",__FILE__,__LINE__,$current_user);
                

                $bee["BEE_USER"] = $current_user;
                $bee["BEE_APP_NAME"] = $an;
                //get connection
            } catch (Emarref\Jwt\Exception\VerificationException $e) {
                $msg = $e->getMessage();
                array_push($res[BEE_EI],$msg);
            }
        }

        
        
    
        if($_SERVER["REQUEST_METHOD"] == "GET"){
            if(isset($_GET["q"])){
                $query_base64 = $_GET["q"];
                //tools_dumpx("_GET",__FILE__,__LINE__,$query_base64);
                $query = base64_decode($query_base64);
                //tools_dump("query",__FILE__,__LINE__,$query);
                $tsji_res = tools_suck_json_into($query, array());
                $res[BEE_EI] = array_merge($tsji_res[BEE_EI],$res[BEE_EI]);
                if(count($res[BEE_EI])==0){//no errors
                    $querydata = $tsji_res[BEE_RI];
                    //get system modules
                    if(array_key_exists("_f_modules",$querydata)){
                        $res[BEE_RI] = bee_security_modules($bee);
                    }elseif(array_key_exists("_f_permissions",$querydata)){
                        $res[BEE_RI] =  bee_security_permissions($bee);
                    }elseif(array_key_exists("_f_countries",$querydata)){
                        $res[BEE_RI] =  $countries_list;
                    }elseif(array_key_exists("_f_bee",$querydata)){
                        $res[BEE_RI] =  array(
                            "bee" => array(
                                "date" => date("Y-m-d")
                            )
                        );
                    }else{
                        //authorise
                        $bsv_res = bee_security_authorise(
                            $bee["BEE_USER"],
                            $querydata,
                            $bee["BEE_HIVE_STRUCTURE"]["combs"],
                            false, //create
                            true, //read
                            false, //update
                            false //delete
                        );
                        $res[BEE_EI] = array_merge($res[BEE_EI],$bsv_res[BEE_EI]);
                        if(count($res[BEE_EI])==0){//no errors
                            //tools_dumpx("querydata",__FILE__,__LINE__,$querydata);
                            $brp_res = bee_run_get($querydata,$bee["BEE_HIVE_STRUCTURE"]["combs"],$bee["BEE_HIVE_CONNECTION"]);
                            //tools_dump("@a tracing null errors",__FILE__,__LINE__,$brp_res[BEE_EI]);
                            $res[BEE_EI] = array_merge($res[BEE_EI],$brp_res[BEE_EI]);
                            //tools_dump("tracing null errors ",__FILE__,__LINE__,$res[BEE_EI]);
                            $res[BEE_RI] = $brp_res[BEE_RI];
                        }
                    }  
                }
            }else{
                //there is nothing to process
                array_push($res[BEE_EI],"Missing query parameter q in the url");
            }
        }else if($_SERVER["REQUEST_METHOD"] == "POST"){
            //check if there is a file upload
            if(count($_FILES) > 0){
                $bhru_res = bee_hive_run_uploads($bee);
                $res[BEE_EI] = array_merge($res[BEE_EI],$bhru_res[BEE_EI]);
                $res[BEE_RI] = $bhru_res[BEE_RI];
            }else{
                //do a normal file processing
                $temp_postdata = file_get_contents("php://input");
                //tools_dumpx("temp_postdata",__FILE__,__LINE__,$temp_postdata);
                $tsji_res = tools_suck_json_into($temp_postdata, array());
                $res[BEE_EI] = array_merge($tsji_res[BEE_EI],$res[BEE_EI]);
                if(count($res[BEE_EI])==0){//no errors
                    $postdata = $tsji_res[BEE_RI];
                    //the login 
                    //it has to be the only thing in its request
                    if(array_key_exists("_f_login",$postdata)){
                        $whole_honey = array();
                        $login_nector = array(
                            "_f_login" => $postdata["_f_login"]
                        );
                        $BEE_GLOBALS["is_login_call"] = true;
                        $hrl_res = bee_hive_run_login($login_nector, $bee);
                        $BEE_GLOBALS["is_login_call"] = false;
                        $whole_honey["_f_login"] = $hrl_res[BEE_RI];
                        $res[BEE_RI] = $whole_honey["_f_login"];
                        $res[BEE_EI] = array_merge($res[BEE_EI],$hrl_res[BEE_EI]); 
                    }else{
                        //authorise
                        $bsv_res = bee_security_authorise(
                            $bee["BEE_USER"],
                            $postdata,
                            $bee["BEE_HIVE_STRUCTURE"]["combs"],
                            true, //create
                            false, //read
                            false, //update
                            false //delete
                        );
                        $res[BEE_EI] = array_merge($res[BEE_EI],$bsv_res[BEE_EI]);
                        if(count($res[BEE_EI])==0){//no errors
                            //tools_dumpx("postdata",__FILE__,__LINE__,$postdata);
                            $brp_res = bee_run_post($postdata,$bee,$bee["BEE_USER"]["id"]);
                            //tools_dumpx("brp_res post ",__FILE__,__LINE__,$brp_res);
                            $res[BEE_EI] = array_merge($res[BEE_EI],$brp_res[BEE_EI]);
                            $res[BEE_RI] = $brp_res[BEE_RI];
                        }
                    }
                }
            }
        }else if($_SERVER["REQUEST_METHOD"] == "PUT"){
            //check if there is a file upload
            if(count($_FILES) > 0){
                $bhru_res = bee_hive_run_uploads($bee);
                $res[BEE_EI] = array_merge($res[BEE_EI],$bhru_res[BEE_EI]);
                $res[BEE_RI] = $bhru_res[BEE_RI];
            }else{
                //do a normal file processing
                $temp_postdata = file_get_contents("php://input");
                //tools_dumpx("temp_postdata",__FILE__,__LINE__,$temp_postdata);
                $tsji_res = tools_suck_json_into($temp_postdata, array());
                $res[BEE_EI] = array_merge($tsji_res[BEE_EI],$res[BEE_EI]);
                if(count($res[BEE_EI])==0){//no errors
                    $postdata = $tsji_res[BEE_RI];
                    //authorise
                    $bsv_res = bee_security_authorise(
                        $bee["BEE_USER"],
                        $postdata,
                        $bee["BEE_HIVE_STRUCTURE"]["combs"],
                        false, //create
                        false, //read
                        true, //update
                        false //delete
                    );
                    $res[BEE_EI] = array_merge($res[BEE_EI],$bsv_res[BEE_EI]);
                    if(count($res[BEE_EI])==0){//no errors
                        //tools_dumpx("postdata",__FILE__,__LINE__,$postdata);
                        $brp_res = bee_run_update($postdata,$bee,$bee["BEE_USER"]["id"]);
                        //tools_dumpx("brp_res put ",__FILE__,__LINE__,$brp_res);
                        $res[BEE_EI] = array_merge($res[BEE_EI],$brp_res[BEE_EI]);
                        $res[BEE_RI] = $brp_res[BEE_RI];
                    }
                }
                
            }
        }else if($_SERVER["REQUEST_METHOD"] == "UPDATE"){
            $method = "put";
            $temp_postdata = file_get_contents("php://input");
            tools_dumpx("temp_postdata: ",__FILE__,__LINE__,$temp_postdata);
        }else if($_SERVER["REQUEST_METHOD"] == "DELETE"){
            //do a normal file processing
            $temp_postdata = file_get_contents("php://input");
            //tools_dumpx("temp_postdata: ",__FILE__,__LINE__,$temp_postdata);
            $tsji_res = tools_suck_json_into($temp_postdata, array());
            $res[BEE_EI] = array_merge($tsji_res[BEE_EI],$res[BEE_EI]);
            if(count($res[BEE_EI])==0){//no errors
                $postdata = $tsji_res[BEE_RI];
                //authorise
                $bsv_res = bee_security_authorise(
                    $bee["BEE_USER"],
                    $postdata,
                    $bee["BEE_HIVE_STRUCTURE"]["combs"],
                    false, //create
                    false, //read
                    false, //update
                    true //delete
                );
                $res[BEE_EI] = array_merge($res[BEE_EI],$bsv_res[BEE_EI]);
                if(count($res[BEE_EI])==0){//no errors
                    //tools_dumpx("postdata",__FILE__,__LINE__,$postdata);
                    $brd_res = bee_run_delete($postdata,$bee,$bee["BEE_USER"]["id"]);
                    //tools_dumpx("brd_res delete ",__FILE__,__LINE__,$brd_res);
                    $res[BEE_EI] = array_merge($res[BEE_EI],$brd_res[BEE_EI]);
                    $res[BEE_RI] = $brd_res[BEE_RI];
                }
            }
        }

        tools_reply($res[BEE_RI],$res[BEE_EI],array(
            $bee["BEE_GARDEN_CONNECTION"],
            $bee["BEE_HIVE_CONNECTION"]
        ));
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
            //roles,permissions, modules
            $security_nector = array(
                "roles" => array(
                    "role_permisiions" => array(),
                    "role_modules" => array()
                )
            );
            $brg_res = bee_run_get($security_nector,$BEE_HIVE_STRUCTURE["combs"],$BEE_HIVE_CONNECTION);
            $BEE_ERRORS = array_merge($BEE_ERRORS,$brg_res[BEE_EI]);
            $BEE_ROLES = $brg_res[BEE_RI]["roles"];
            //tools_dumpx("brg_res",__FILE__,__LINE__,$brg_res[BEE_RI]);

            $BEE = array(
                "BEE_ROLES" => $BEE_ROLES,
                "BEE_HIVE_STRUCTURE" => $BEE_HIVE_STRUCTURE,
                "BEE_GARDEN_STRUCTURE" => $GARDEN_STRUCTURE,
                "BEE_GARDEN_CONNECTION" => $BEE_GARDEN_CONNECTION,
                "BEE_HIVE_CONNECTION" => $BEE_HIVE_CONNECTION,
                "BEE_GARDEN" => $BEE_GARDEN,
                "BEE_ERRORS" => $BEE_ERRORS,
                "BEE_JWT_ENCRYPTION" => $BEE_JWT_ENCRYPTION,
                "BEE_USER" => array("id"=>0)
            );
        }    
    }
    
    

?>