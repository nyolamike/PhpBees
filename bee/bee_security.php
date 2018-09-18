<?php
    function bee_security_modules($bee){
        $whole_honey = array(
            "modules" => $bee["BEE_GARDEN_STRUCTURE"]["_modules"]
        );
        return $whole_honey;
    }

    function bee_security_permissions($bee){
        $combs = $bee["BEE_HIVE_STRUCTURE"]["combs"];
        $whole_honey = array(
            "permissions" => array()
        );
        foreach ($combs as $combs_name => $combs_def) {
            $plural_name = Inflect::pluralize($combs_name);
            $perm = array(
                "name" => $combs_name,
                "plural_name" => $plural_name,
            );
            array_push($whole_honey["permissions"],$perm);
        }
        return $whole_honey;
    }

    function bee_security_extract_targets($found,$node,$hive_combs){
        foreach ($node as $node_key => $node_value) {
            if(tools_startsWith($node_key,"_")){
                //nyd
                //validate _fx_ nodes
                //validate _xtu_ nodes
                //and others
                continue;
            }
            $keysingle = Inflect::singularize($node_key);
            //whats validated must be in the hive combs
            if(array_key_exists($keysingle,$hive_combs) && !array_key_exists($keysingle,$found)){
                array_push($found,$keysingle);
            }
            $childs = bee_security_extract_targets($found,$node_value,$hive_combs);
            $found = array_merge($found, $childs); 
        }
        return $found;
    }

    function bee_security_authorise($token_user,$nectoroid,$hive_combs,$can_create=false,$can_read=false,$can_update=false,$can_delete=false){
        $res = array(true,array());
        $user_roles = $token_user["user_roles"];
        $nector_combs = bee_security_extract_targets(array(),$nectoroid,$hive_combs);
        $user_perms = array();
        foreach ($user_roles as $ind => $user_role) {
            if(array_key_exists("role",$user_role)){
                $role = $user_role["role"];
                if(array_key_exists("role_permisiions",$role)){
                    $perms = $role["role_permisiions"];
                    foreach ($perms as $pi => $perm) {
                        $nem = $perm["permission"];
                        if(!array_key_exists($nem,$user_perms)){
                            $user_perms[$nem] = array(0,0,0,0);
                        }
                        $pcc = intval($perm["can_create"]);
                        $pcr = intval($perm["can_read"]);
                        $pcu = intval($perm["can_update"]);
                        $pcd = intval($perm["can_delete"]);
                        $user_perms[$nem][0] = ($pcc>0)?$pcc:0;
                        $user_perms[$nem][1] = ($pcr>0)?$pcr:0;
                        $user_perms[$nem][2] = ($pcu>0)?$pcu:0;
                        $user_perms[$nem][3] = ($pcd>0)?$pcd:0;
                    }
                }
            }
        }
        //all the $nector_combs must pass
        $str = json_encode($nectoroid);
        foreach ($nector_combs as $nector_comb) {
            if(array_key_exists($nector_comb,$user_perms)){
                $p = $user_perms[$nector_comb];
                if($can_create == true && $p[0] == 0){
                    array_push($res[BEE_EI],"Not authorised to create this resource " . $nector_comb . " in " . $str);
                }
                if($can_read == true && $p[1] == 0){
                    array_push($res[BEE_EI],"Not authorised to read from this resource " . $nector_comb . " in " . $str);
                }
                if($can_update == true && $p[2] == 0){
                    array_push($res[BEE_EI],"Not authorised to edit this resource " . $nector_comb . " in " . $str);
                }
                if($can_delete == true && $p[3] == 0){
                    array_push($res[BEE_EI],"Not authorised to delete this resource " . $nector_comb . " in " . $str);
                }
            }else{
                array_push($res[BEE_EI],"Not authorised to access this resource " . $nector_comb . " in " . $str);
            }
        }
        if(count($res[BEE_EI])>0){
            $res[BEE_RI] = false;
        }
        return $res;
    }
?>