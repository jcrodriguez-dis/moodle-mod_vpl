<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// VPL for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with VPL for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class to find and show clusters of similar files
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/similarity_base.class.php';
class vpl_clusters{
    var $clusters; //Array of clusters
    /**
     * @param $selected array of cases (similar files pairs)
     */
    function __construct($selected){
        $this->clusters = array();
        //Set all files to not clustered
        foreach($selected as $case){
            $case->first->cluster = -1;
            $case->second->cluster = -1;
        }
    }
    /**
     * Assign cluster
     * @param $pair object with similar file information
     */
    function process($pair){
        if($pair->first->cluster == $pair->second->cluster){ //Not assigned or same cluster
            if($pair->first->cluster == -1){ //New cluster
                $cluster_id = count($this->clusters);
                $pair->first->cluster = $cluster_id;
                $pair->second->cluster = $cluster_id;
                $this->clusters[] = array($pair);
            }else{
                $this->clusters[$pair->first->cluster][] = $pair;
            }
        }else{ //Diferent clusters or one file not assigned
            $min_cluster = min($pair->first->cluster,$pair->second->cluster);
            $max_cluster = max($pair->first->cluster,$pair->second->cluster);
            if($min_cluster > -1){ //Diferent clusters => Need clusters fusion
                //Select minimum cost fusion
                if(count($this->clusters[$min_cluster])<count($this->clusters[$max_cluster])){
                    $aux=$min_cluster;
                    $min_cluster=$max_cluster;
                    $max_cluster=$aux;
                }
                foreach($this->clusters[$min_cluster] as $pairmove){ //Fusion
                    $pairmove->first->cluster = $max_cluster;
                    $pairmove->second->cluster = $max_cluster;
                    $this->clusters[$max_cluster][] = $pairmove;
                }
                $this->clusters[$min_cluster] = array(); //Remove cluster
            }
            //Assign new pair
            $pair->first->cluster = $max_cluster;
            $pair->second->cluster = $max_cluster;
            $this->clusters[$max_cluster][] = $pair;
        }
    }
    function assign_file_id(&$file, &$id){
        if(!isset($file->id)){
            $file->id = $id++;
        }
    }

    function assign_number(){
        $cluster_number=1;
        foreach($this->clusters as $cluster){
            if(count($cluster)>1){ //2 or more pairs => 3 or more files
                foreach($cluster as $pair){
                    $pair->set_cluster($cluster_number);
                }
                $cluster_number++;
            }
        }
    }

    function print_clusters(){
        $cluster_number=0;
        foreach($this->clusters as $cluster){
            if(count($cluster)>1){ //3 or more files
                //Assign ids (0..num_files-1) to files
                $id=0;
                foreach($cluster as $pair){
                    $this->assign_file_id($pair->first,$id);
                    $this->assign_file_id($pair->second,$id);
                }
                //Build matrix
                $num_files=$id;
                $files = array();
                $matrix = array();
                for($i = 0; $i <$num_files; $i++){
                    $matriz[]=array();
                }
                foreach($cluster as $pair){
                    $files[$pair->first->id]= $pair->first;
                    $files[$pair->second->id]= $pair->second;
                    $matrix[$pair->first->id][$pair->second->id] = $pair;
                    $matrix[$pair->second->id][$pair->first->id] = $pair;
                }
                //Reorder files
                $auxorder = array();
                for($i = 0; $i <$num_files; $i++){
                    $value=PHP_INT_MAX;
                    foreach($matrix[$i] as $pair){
                        $value = min($value,$pair->get_level());
                    }
                    $auxorder[] = $value;
                }
                asort($auxorder);
                $first_order = array();
                foreach($auxorder as $file => $nothing){
                    $first_order[]=$file;
                }
                $order = array();
                $center = (int)($num_files/2);
                $order[$center] = $first_order[0];
                $pos=1;
                for($i = 1; $pos<$num_files; $i++){
                    if($center-$i >=0 && $pos < $num_files){
                        $order[$center-$i]=$first_order[$pos++];
                    }
                    if($center+$i < $num_files && $pos < $num_files){
                        $order[$center+$i]=$first_order[$pos++];
                    }
                }
                //Fill matrix
                for($i = 0; $i <$num_files; $i++){
                    for($j = 0; $j <$num_files; $j++){
                        if($i != $j && !isset($matrix[$i][$j])){
                            $s1 = $files[$i]->similarity1($files[$j]);
                            $s2 = $files[$i]->similarity2($files[$j]);
                            $s3 = $files[$i]->similarity3($files[$j]);
                            $matrix[$i][$j] = new vpl_files_pair($files[$i],$files[$j],$s1,$s2,$s3);
                            $matrix[$j][$i] = $matrix[$i][$j];
                        }
                    }
                }

                //generate table
                $table = new html_table();
                $table->head  = array ('info','#');
                $table->align = array ('right','right');
                $table->size = array ('60','5');
                for($i = 0; $i <$num_files; $i++){
                    $table->head[]  = $i+1;
                    $table->align[] = 'right';
                    $table->size[] = '10';
                }
                for($pi = 0; $pi <$num_files; $pi++){
                    $i = $order[$pi];
                    $row = array($files[$i]->show_info(),$pi+1);
                    for($pj = 0; $pj <$num_files; $pj++){
                        $j = $order[$pj];
                        if($i == $j){
                            $row[] = '';
                        }else{
                            $row[] = $matrix[$i][$j]->get_link();
                        }
                    }
                    $table->data[]=$row;
                }
                $cluster_number++;
                echo '<a name="clu'.$cluster_number.'"></a>';
                echo '<b>'.s(get_string('numcluster',VPL,$cluster_number)).'</b>';
                echo html_writer::table($table);
            }
        }
    }
}

