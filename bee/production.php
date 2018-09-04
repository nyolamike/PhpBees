<?php

    //to convert sqls into raw honey
    function production_run($sqls,$connection){
        $res = array(array(),array());
        foreach ($sqls as $sql_index => $sql_group) {
            //the sql_group contains
            //sql, paths_to_clean, children
            $sql = $sql_group["sql"];
            //tools_dumpx("sql ",__FILE__,__LINE__,$sql);
            $hr_res = hive_run($sql,$connection);
            $res[BEE_EI] = array_merge($res[BEE_EI],$hr_res[BEE_EI]);
            array_push($res[BEE_RI],array(
                "raw_honey" => $hr_res[BEE_RI]["data"],
                "paths_to_clean" =>  $sql_group["paths_to_clean"],
                "children" => $sql_group["children"]
            ));
        }
        return $res;
    }

    
    //to convert sqls into raw honey
    function production_post($sqls,$connection){
        $res = array(array(),array());
        foreach ($sqls as $sql_index => $sql) {
            //tools_dumpx("sql ",__FILE__,__LINE__,$sql);
            $hr_res = hive_run($sql,$connection);
            //tools_dumpx("hr_res",__FILE__,__LINE__,$hr_res);
            $res[BEE_EI] = array_merge($res[BEE_EI],$hr_res[BEE_EI]);
            $res[BEE_RI][$sql_index] = $hr_res[BEE_RI];
        }
        return $res;
    }

?>