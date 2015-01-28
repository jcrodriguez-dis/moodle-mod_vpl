<?php
/**
 * @version		$Id: clusters.class.php,v 1.6 2012-06-05 23:22:11 juanca Exp $
 * @package		VPL. Class to find and show clusters of similar files
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/similarity_base.class.php';
class vpl_clusters{
	var $clusters; //Array of clusters
	var $cmembers; //Array with number of cluster members
	var $adj_matrix; //Adjacency list access by fid
	const max_members = 5;
	/**
	 * @param $selected array of cases (similar files pairs)
	 */
	function __construct($selected){
		$this->clusters = array();
		$this->cmembers = array();
		//Identify every file 
		//Set all files to not clustered
		$fid=0;
		foreach($selected as $case){
			if(!isset($case->first->fid)){
				$case->first->cluster = -1;
				$case->first->fid= $fid++;
			}
			if(!isset($case->second->fid)){
				$case->second->cluster = -1;
				$case->second->fid= $fid++;
			}
		}
		//
		$this->adj_list = array();
		for($i=0; $i<$fid; $i++){
			$row = array();
/*			for($j=0; $j < $fid; $j++){
				$row[$j] =false;
			}*/
			$this->adj_list[$i]=$row;
		}
		foreach($selected as $case){
			$one = $case->first->fid;
			$other = $case->second->fid;
			$this->adj_list[$one][$other]= true;
			$this->adj_list[$other][$one]= true;
		}
		foreach($selected as $case){
			$this->process($case);
		}
		$this->assign_number();
	}
	/**
	 * Assign cluster
	 * @param $pair object with similar file information
	 */
	function process($pair){
		$c1 = $pair->first->cluster;
		$c2 = $pair->second->cluster;
		if($c1 == $c2){ //Not assigned or same cluster
			if($pair->first->cluster == -1){ //New cluster
				$new_id = count($this->clusters);
				$pair->first->cluster = $new_id;
				$pair->second->cluster = $new_id;
				$this->clusters[$new_id] = array($pair);
				$this->cmembers[$new_id] = 2;
			}else{
				$this->clusters[$c1][] = $pair;
				$this->cmembers[$c1]++;
			}
		}elseif($c1 == -1 || $c2 == -1){ //One file not assigned
			if($c1 == -1){
				$cluster = $c2;
				$next = $pair->first->fid;
			}else{
				$cluster = $c1;
				$next = $pair->second->fid;
			}
			if(count($this->adj_list[$next]) < $this->cmembers[$cluster]/2){
				return;
			}  
			$this->cmembers[$cluster]++;
			$pair->first->cluster = $cluster;
			$pair->second->cluster = $cluster;
			$this->clusters[$cluster][] = $pair;
			//echo "<h3>Añadimos".(count($this->adj_list[$next]))." ".($this->cmembers[$cluster]/2)."</h3>";
			//$this->print_cluster($this->clusters[$cluster]);
		}else{ //Diferent clusters
			$one = $pair->first->fid;
			$other = $pair->second->fid;
			$min_cluster = min($c1,$c2);
			$max_cluster = max($c1,$c2);
			//Need clusters fusion?
			if(count($this->adj_list[$one]) <= $this->cmembers[$c2]/2
			  || count($this->adj_list[$other]) <= $this->cmembers[$c1]/2){
				return;
			}  
			$cmax = $this->cmembers[$max_cluster];
			$cmin = $this->cmembers[$min_cluster];
			//Select minimum cost fusion
			if($cmin > $cmax){
				$aux=$min_cluster;
				$min_cluster=$max_cluster;
				$max_cluster=$aux;
			}
			//echo "<h3>Unimos</h3>";
			//$this->print_cluster($this->clusters[$min_cluster]);
			//$this->print_cluster($this->clusters[$max_cluster]);
			foreach($this->clusters[$min_cluster] as $pairmove){ //Fusion
				$pairmove->first->cluster = $max_cluster;
				$pairmove->second->cluster = $max_cluster;
				$this->clusters[$max_cluster][] = $pairmove;
			}
			//$this->print_cluster($this->clusters[$max_cluster]);
			$this->cmembers[$max_cluster] += $this->cmembers[$min_cluster];
			$this->cmembers[$min_cluster] = 0;
			$this->clusters[$min_cluster] = array(); //Remove cluster
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
	function print_cluster($cluster,$cluster_number){
		//Assign ids (0..num_files-1) to files
		foreach($cluster as $pair){
			unset($pair->first->id);
			unset($pair->second->id);
		}
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
		$table->align = array ('left','right');
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
		echo '<a name="clu'.$cluster_number.'"></a>';
		echo '<b>'.s(get_string('numcluster',VPL,$cluster_number)).'</b>';
		echo html_writer::table($table);
	}
	function print_clusters(){
		$cluster_number=1;
		foreach($this->clusters as $cluster){
			if(count($cluster)>1){ //3 or more files
				$this->print_cluster($cluster,$cluster_number);
				$cluster_number++;
			}
		}
	}
}
?>
