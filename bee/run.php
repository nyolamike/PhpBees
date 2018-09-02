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
    
    $BEE_ERRORS = array();
    //the garden structure
    //every hive will have its structure e.g _hive.json
    //but this is the structure of the master hive
    $tj_res = tools_jsonify(file_get_contents(BEE_GARDEN_STUCTURE_FILE_NAME));
    $BEE_GARDEN_STRUCTURE = $tj_res[0]; 
    $BEE_ERRORS = array_merge($BEE_ERRORS,$tj_res[BEE_EI]);

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
    
?>