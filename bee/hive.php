<?php

function hive_run_get_connection($username, $password,$servername="localhost",$databasename="",$is_test=false){
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

//run config
//sql, hive name, show sql on error
function hive_run($sql,$connection){
    $hive_response = array(
        array(
            "hive_res" => null,
            "id" => 0, 
            "data" => array()
        ),
        array()
    );
    try{  
        if(tools_startsWith($sql,"INSERT") == TRUE){
            $res = $connection->exec($sql);
            //get the last insert id
            $liid = $connection->lastInsertId();
            $hive_response[BEE_RI]["id"] = $liid;
            $hive_response[BEE_RI]["hive_res"] = $res;
        }else if(tools_startsWith($sql,"SELECT") == TRUE){
            $res = $connection->query($sql);
            $data = array();
            foreach ($res as $row) {
                array_push($data, $row);
            }
            $hive_response[BEE_RI]["hive_res"] = $res;
            $hive_response[BEE_RI]["data"] = $data;
        }else if(tools_startsWith($sql,"UPDATE") == TRUE){
            $res = $connection->exec($sql);
            //nyd
            //get the number of affected records
            $hive_response[BEE_RI]["num"] = 1;
            $hive_response[BEE_RI]["hive_res"] = $res;
        }else{
            $res = $connection->exec($sql);
            $hive_response[BEE_RI]["hive_res"] = $res;
        }
    }catch(PDOException $e){
        $msg = $e->getMessage() . " " . ((SHOW_SQL_ON_ERRORS == TRUE)? $sql : "");
        array_push($hive_response[BEE_EI], $msg);
    }catch(Exception $e){
        $msg = $e->getMessage() . " " . ((SHOW_SQL_ON_ERRORS == TRUE)? $sql : "");
        array_push($hive_response[BEE_EI], $msg);
    }
    return $hive_response;
}

function hive_run_create_hive($hive_name,$connection){
    $sql = "CREATE DATABASE IF NOT EXISTS " . $hive_name;
    $hr_res = hive_run($sql,$connection);
    return $hr_res;
}

function hive_run_inn($col_name,$num){ return tools_chain($col_name, "`{}` int(".$num.") NOT NULL"); }

function hive_run_fk($col_name){ return tools_chain($col_name, "`{}` int(30) NOT NULL"); }

function hive_run_inn_d($col_name,$num, $default){ 
    return tools_chain($col_name, "`{}` int(".$num.") NOT NULL DEFAULT '". $default. "'");
}

function hive_run_in($col_name,$num){ return tools_chain($col_name, "`{}` int(".$num.") NULL "); }

function hive_run_dnn($col_name){ return tools_chain($col_name, "`{}` DOUBLE NOT NULL DEFAULT '0.0' "); }

function hive_run_vcnn($col_name,$num){ return tools_chain($col_name, "`{}` varchar(".$num.") NOT NULL"); }

function hive_run_vcn($col_name,$num){ return tools_chain($col_name, "`{}` varchar(".$num.") NULL"); }

function hive_run_vcnn_d($col_name,$num,$default){ 
    return tools_chain($col_name, "`{}` varchar(".$num.") NOT NULL DEFAULT '".$default."' "); 
}

function hive_run_tn($col_name){ return tools_chain($col_name, "`{}` text NULL"); }

function hive_run_tnn($col_name){ return tools_chain($col_name, "`{}` text NOT NULL"); }

function hive_run_tsnn($col_name){ return tools_chain($col_name, "`{}` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP "); }

function hive_run_dtnn($col_name){ return tools_chain($col_name, "`{}`  DATE NOT NULL "); }

function hive_run_default_secture(){
    return array("tn");
}

//the process of converting section structures
//e.g ["vcnn",60],["vcnn",60]
//and converting them to temp sqls
//a secture is a section structure ["vcnn",60]
//sectures usually is an object from the app.json file
function hive_run_secture_sqlization($sectures){
    $section_sqls = array();
    foreach ($sectures as $section_name => $secture) {
        //e.g hidden fields flag like "_hidden":["code","password"]
        //must not be interpreted from here
        if(tools_startsWith($section_name,"_") || tools_startsWith($secture,"_")){
            continue;
        }
        //apply a default definition if no definition is found
        //this could happen when just the section name is provided
        //in this case the secture is the value while section_name is a numeric index
        //thats how php does its things
        if(is_numeric($section_name)){
            array_push($section_sqls,hive_run_tn($secture));
            continue;
        }

        if(tools_startsWith($secture[0],"inn")){ array_push($section_sqls,hive_run_inn($section_name,$secture[1])); continue;}
        if(tools_startsWith($secture[0],"fk")){ array_push($section_sqls,hive_run_fk($section_name)); continue;}
        if(tools_startsWith($secture[0],"inn_d")){ array_push($section_sqls,hive_run_inn_d($section_name,$secture[1],$secture[2])); continue;}
        if(tools_startsWith($secture[0],"in")){ array_push($section_sqls,hive_run_in($section_name,$secture[1])); continue;}
        if(tools_startsWith($secture[0],"dnn")){ array_push($section_sqls,hive_run_dnn($section_name)); continue;}
        if(tools_startsWith($secture[0],"vcnn")){ array_push($section_sqls,hive_run_vcnn($section_name,$secture[1])); continue;}
        if(tools_startsWith($secture[0],"vcn")){ array_push($section_sqls,hive_run_vcn($section_name,$secture[1])); continue;}
        if(tools_startsWith($secture[0],"vcnn_d")){ array_push($section_sqls,hive_run_vcnn_d($section_name,$secture[1],$secture[2]));  continue;}
        if(tools_startsWith($secture[0],"tn")){ array_push($section_sqls,hive_run_tn($section_name)); continue;}
        if(tools_startsWith($secture[0],"tnn")){ array_push($section_sqls,hive_run_tnn($section_name)); continue;}
        if(tools_startsWith($secture[0],"tsnn")){ array_push($section_sqls,hive_run_tsnn($section_name)); continue;}
        if(tools_startsWith($secture[0],"dtnn")){ array_push($section_sqls,hive_run_dtnn($section_name)); continue;}
    }
    return array($section_sqls,array());
}



function hive_run_sql_ct($comb_name,$sections_sql){
    $sql = "CREATE TABLE IF NOT EXISTS `" . $comb_name . "` (";
    $sql = $sql . " `id` int(30) NOT NULL AUTO_INCREMENT,";
    for ($i=0; $i < count($sections_sql); $i++) { 
        $sql = $sql . " " . $sections_sql[$i] . ",";
    }
    $sql = $sql . " `time_inserted` int(30) NOT NULL,";
    $sql = $sql . " `inserted_by` varchar(100) DEFAULT NULL,";
    $sql = $sql . " `time_last_modified` int(30) NOT NULL,";
    $sql = $sql . " `last_modified_by` varchar(100) DEFAULT NULL,";
    $sql = $sql . " `is_deleted` int(30) NOT NULL DEFAULT 0,";
    $sql = $sql . " `guid` text NOT NULL,";
    $sql = $sql . " PRIMARY KEY (`id`) );";
    return array($sql,array());
}

function hive_run_ct($connection,$comb_name, $sections_sqls){
    $sql = hive_run_sql_ct($comb_name,$sections_sqls)[BEE_RI];
    $hr_res = hive_run($sql,$connection);
    return $hr_res;
}

//db add column
function hive_run_ac($connection,$comb_name,$sections_sql){
    $sql  = "ALTER ".$comb_name." ADD " . $sections_sql;
    $hr_res = hive_run($sql,$connection);
    return $hr_res;
}

function db_read_indexed($conn,$sql,$show_errors){
    $config = array(
        "sql" => $sql,
        "conn" => $conn,
        "show_sql_on_errors" => $show_errors
    );
    $db_res = db_run($config);
    if(count($db_res[1])==0){
        $indexed = array();
        $temp = $db_res[0]["data"];
        foreach ($temp as $index => $row) {
            $indexed[$row["id"]] = $row;
        }
        return array($indexed,array());
    }else{
        return array(null,$db_res[1]);
    }
}


//checkts if the garden for this application exists
//if not it creates it
//the garden id like the master db
//this will return the connection to the master hive/db/garden
//nyd
//we will need to create a caching solution here so that no every request
//that comes to the server actually has to chech for this
function hive_run_ensure_garden_exists($garden_structrue){
    $res = array(true,array());
    $hrgc_res = hive_run_get_connection(BEE_USER_NAME, BEE_PASSWORD,BEE_SERVER_NAME,"",true);
    $res[BEE_EI] = array_merge($res[BEE_EI],$hrgc_res[BEE_EI]);
    $connection = $hrgc_res[BEE_RI];
    if(count($hrgc_res[BEE_EI]) == 0){
        $hrch_res = hive_run_create_hive(BEE_GARDEN,$connection);
        //var_dump($hrch_res);
        $res[BEE_EI] = array_merge($res[BEE_EI],$hrch_res[BEE_EI]);
        //if there are no errors continue to create combs here
        if(count($hrch_res[BEE_EI]) == 0){
            //get the connection to the created hive
            //close connection to test db
            $connection = null;
            unset($connection);
            $hrgc_res = hive_run_get_connection(BEE_USER_NAME, BEE_PASSWORD,BEE_SERVER_NAME,BEE_GARDEN,false);
            $res[BEE_EI] = array_merge($res[BEE_EI],$hrgc_res[BEE_EI]);
            $connection = $hrgc_res[BEE_RI];
            if($hrch_res[BEE_RI]["hive_res"] == 1){ //if the hive has just been created
                foreach ($garden_structrue as $comb_name => $sectures) {
                    if(tools_startsWith($comb_name,"_")){
                        continue;
                    }
                    $hrss_res = hive_run_secture_sqlization($sectures);
                    $sections_sqls = $hrss_res[BEE_RI];
                    $hrct_res =  hive_run_ct($connection,$comb_name, $sections_sqls);
                    $res[BEE_EI] = array_merge($res[BEE_EI],$hrct_res[BEE_EI]);
                }
            }
            
        }
    }
    if(count($res[BEE_EI])>0){
        $res[BEE_RI] = false;
    }else{
        $res[BEE_RI] = $connection;
    }
    return $res;
}

//original query
/*"sections"  => array(
    "_a" => array(),
    "comb" => array(
        "hive" => array(
            "modules" => array(
                "hive" => array(
                    "modules" => array()  
                )
            )  
        )
    ),
    "section_fragiles" => array(
        "section" => array(
            "comb" => array(
                "hive" => array(
                    "modules" => array()  
                )
            ),
        )
    )
)*/

/*
"sections"  => array(
            "_a" => array(),
            "comb" => array(
                "hive" => array(
                    "modules" => array(
                        "hive" => array(
                            "modules" => array()  
                        )
                    )
                )
            ),
            "section_fragiles" => array(
            )
        )
*/
/*
"combs" => array(),
        "section_fragiles" => array(
            "section" => array(
                "comb" => array(
                    "hive" => array(
                        "modules" =>  array()
                    )
                )
            )
        )
*/
function hive_run_get_garden($structure,$connection){
    $res = array(null,array(),$structure);
    $nectoroid = array(
        "hives" => array(
            "combs" => array(
                "sections" => array(
                    "section_fragiles" => array()
                )
            ),
            "modules" => array()
        )
    );
    $sr_res = segmentation_run($nectoroid,$structure,$connection);
    //tools_dump("@1 segmentation_run res: ",__FILE__,__LINE__,$sr_res[BEE_RI]);
    $hasr_res = hive_after_segmentation_run($sr_res,$nectoroid,$structure,$connection);
    $res[BEE_RI] = $hasr_res[BEE_RI];
    $res[BEE_EI] = array_merge($res[BEE_EI],$hasr_res[BEE_EI]);
    $res[2] = $hasr_res[2];
    return $res; 
}

function hive_after_segmentation_run($segmentation_run_res,$nectoroid,$structure,$connection){
    $res = array(null,array(),$structure);
    $sr_res = $segmentation_run_res;
    //tools_dump("hive segmentation results",__FILE__,__LINE__,$sr_res[BEE_RI]);
    $res[BEE_EI] = array_merge($res[BEE_EI],$sr_res[BEE_EI]);
    $res[2] = $sr_res[2];//the structure
    if(count($sr_res[BEE_EI]) == 0){//when we dont have any errors
        $sr_res = sqllization_run($sr_res[BEE_RI]);
        //tools_dump("@2 sqllization_run res: ",__FILE__,__LINE__,$sr_res[BEE_RI]);
        //convert these queries into raw honey
        $pr_res = production_run($sr_res[BEE_RI],$connection);
        //tools_dump("@3 production_run res: ",__FILE__,__LINE__,$pr_res[BEE_RI]);
        $res[BEE_EI] = array_merge($res[BEE_EI],$pr_res[BEE_EI]);
        if(count($sr_res[BEE_EI]) == 0){//when we dont have any errors
            $pr_res = packaging_run($pr_res[BEE_RI],$nectoroid,$structure,$connection);
            $res[BEE_EI] = array_merge($res[BEE_EI],$pr_res[BEE_EI]);
            $res[BEE_RI] = $pr_res[BEE_RI];
        }
    }
    return $res;
}
?>