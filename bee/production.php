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
    function production_post($sqls,$connection,$prev_res=array()){
        $res = array(array(),array());
        foreach ($sqls as $sql_index => $sql) {
            //tools_dump("prev_res",__FILE__,__LINE__,$prev_res);
            if(is_array($sql)){
                $res[BEE_RI][$sql_index] = array(); 
                foreach ($sql as $index => $cmd) {
                    //nyd
                    //look for fk replacements if any
                    //the use of !== is deliberate
                    if (strpos($cmd, "'_fk_") !== false && strpos($cmd, "_kf_'") !== false ) {
                        $foreing_values  = tools_get_in_between_strings("_fk_", "_kf_", $cmd);
                        //tools_dumpx("goot",__FILE__,__LINE__,$foreing_values);
                        for ($i=0; $i < count($foreing_values); $i++) { 
                            $foreing_value = $foreing_values[$i];
                            if(strpos($foreing_value, "@") !== false){
                                //contains an index
                                $ky_indx = explode("@",$foreing_value);
                                $ky = $ky_indx[0];
                                $indx = intval($ky_indx[1]);
                                $val = $prev_res[$ky][$indx];
                                $search = "'_fk_".$foreing_value."_kf_'";
                                $cmd = str_replace($search,$val,$cmd);
                            }else{
                                $search = "'_fk_".$foreing_value."_kf_'";
                                $val = $prev_res[$foreing_value];
                                $cmd = str_replace($search,$val,$cmd);
                            }
                        }
                        //tools_dumpx("cmdx",__FILE__,__LINE__,$cmd);
                    }
                    $hr_res = hive_run($cmd,$connection);
                    //tools_dumpx("hr_res",__FILE__,__LINE__,$hr_res);
                    $res[BEE_EI] = array_merge($res[BEE_EI],$hr_res[BEE_EI]);
                    array_push($res[BEE_RI][$sql_index],$hr_res[BEE_RI]);
                }
            }else{
                //nyd
                //look for fk replacements if any
                //the use of !== is deliberate
                if (strpos($sql, "'_fk_") !== false && strpos($sql, "_kf_'") !== false ) {
                    $foreing_values  = tools_get_in_between_strings("_fk_", "_kf_", $sql);
                    //tools_dumpx("goot",__FILE__,__LINE__,$foreing_values);
                    for ($i=0; $i < count($foreing_values); $i++) { 
                        $foreing_value = $foreing_values[$i];
                        if(strpos($foreing_value, "@") !== false){
                            //contains an index
                            $ky_indx = explode("@",$foreing_value);
                            $ky = $ky_indx[0];
                            $indx = intval($ky_indx[1]);
                            $val = $prev_res[$ky][$indx];
                            $search = "'_fk_".$foreing_value."_kf_'";
                            $sql = str_replace($search,$val,$sql);
                        }else{
                            //tools_dumpx("now res",__FILE__,__LINE__,$prev_res);
                            $search = "'_fk_".$foreing_value."_kf_'";
                            $val = $prev_res[$foreing_value];
                            $sql = str_replace($search,$val,$sql);
                        }
                    }
                }
                //tools_dump("sql",__FILE__,__LINE__,$sql);
                $hr_res = hive_run($sql,$connection);
                //tools_dumpx("hr_res",__FILE__,__LINE__,$hr_res);
                $res[BEE_EI] = array_merge($res[BEE_EI],$hr_res[BEE_EI]);
                $res[BEE_RI][$sql_index] = $hr_res[BEE_RI];
            }
        }
        return $res;
    }

    //to convert sqls into raw honey
    function production_delete($sqls,$connection,$is_restricted=false){
        $res = array(array(),array());
        foreach ($sqls as $sql_index => $sql) {
            //tools_dump("sql_index",__FILE__,__LINE__,array($sql_index,$sql));
            $hr_res = hive_run($sql,$connection);
            //tools_dumpx("hr_res",__FILE__,__LINE__,$hr_res);
            $res[BEE_EI] = array_merge($res[BEE_EI],$hr_res[BEE_EI]);
            $res[BEE_RI][$sql_index] = $hr_res[BEE_RI];
        }
        return $res;
    }

?>