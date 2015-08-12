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
 * Similarity form class
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

global $CFG;
require_once dirname(__FILE__).'/../../../config.php';
require_once $CFG->libdir.'/formslib.php';
class vpl_similarity_form extends moodleform {
    private $vpl;

    function __construct($page,$vpl){
        $this->vpl = $vpl;
        parent::__construct($page);
    }
    function list_activities($vplid){
        global $DB;
        $list=array(''=>'');
        $cn = $this->vpl->get_course()->shortname;
        //Low privilegies
        $courses=get_user_capability_course(VPL_VIEW_CAPABILITY,null,true,'shortname');
        //Reorder courses by name similar to current
        usort($courses, function ($a, $b) use ($cn){
            $na = $a->shortname;
            $nb = $b->shortname;
            $da= levenshtein($na,$cn);
            $db= levenshtein($nb,$cn);
            if($da != $db) return ($da < $db)?-1:1;
            if($na == $cn) return -1;
            if($nb == $cn) return 1;
            if($na != $nb) return ($na < $nb)?-1:1;
            return 0;
        });
        foreach($courses as $course){
            $vpls = $DB->get_records(VPL,array('course' => $course->id));
            foreach($vpls as $vplinstace){
                if($vplinstace->id == $vplid){
                    continue;
                }
                $othervpl = new mod_vpl(false,$vplinstace->id);
                if(! $othervpl->get_course_module()){
                    continue;
                }
                if($othervpl->has_capability(VPL_SIMILARITY_CAPABILITY)){
                    $list[$othervpl->get_course_module()->id]=$othervpl->get_course()->shortname.' '.$othervpl->get_printable_name();
                }
            }
            if(count($list)>1000){
                break; //Stop loading instances
            }
        }
        $list['']=get_string('select');
        return $list;
    }

    function get_directories($dirpath){
        $ret = array();
        $dd = @opendir($dirpath);
        if($dd !== false){
            while ($dir=readdir($dd)) {
                if ($dir!='.' && $dir!='..' && is_dir($dirpath."/".$dir)) {
                    $ret[] = $dir;
                }
            }
            closedir($dd);
        }
        return $ret;
    }

    function list_directories($cid){
        global $CFG;
        $dirs=array();
        $dirs=array('' => get_string('select'));
        $basedir=$CFG->dataroot.'/'.$cid;
        foreach($this->get_directories($basedir) as $dir){
            $dirs[$dir]=$dir;
            foreach($this->get_directories($basedir.'/'.$dir) as $inner){
                $dirs[$dir.'/'.$inner]=$dir.'/'.$inner;
            }
        }
        return $dirs;
    }
    function definition(){
        $mform    =& $this->_form;
        $mform->addElement('hidden','id',required_param('id',PARAM_INT));
        $mform->setType('id', PARAM_INT);
        $filelist = $this->vpl->get_required_fgm()->getFileList();
        if(count($filelist)>0){
            $mform->addElement('header', 'headerfilestoprocess', get_string('filestoscan', VPL));
            $num=0;
            foreach($filelist as $filename){
                $mform->addElement('checkbox','file'.$num,$filename);
                $mform->setDefault('file'.$num, true);
                $mform->disabledIf('file'.$num, 'allfiles','checked');
                $num++;
            }
            $mform->addElement('checkbox','allfiles',get_string('allfiles',VPL));
            $mform->addElement('checkbox','joinedfiles',get_string('joinedfiles',VPL));
            $mform->disabledIf('joinedfiles', 'allfiles','checked');
        }else{
            $mform->addElement('hidden','allfiles','checked');
            $mform->setType('allfiles', PARAM_BOOL);
        }
        $mform->addElement('header', 'headerfilestoprocess', get_string('scanoptions', VPL));
        $defaultlimit=(intval(count($this->vpl->get_submissions_number())/25)+1)*5;
        $options=array();
        $options[$defaultlimit]=$defaultlimit;
        for($i=5; $i<=40; $i+=5){
            $options[$i]=$i;
        }
        for($i=60; $i<=100; $i+=20){
            $options[$i]=$i;
        }
        for($i=150; $i<=400; $i+=50){
            $options[$i]=$i;
        }
        asort ($options);
        $mform->addElement('select', 'maxoutput', get_string('maxsimilarityoutput',VPL), $options);
        $mform->setType('maxoutput', PARAM_INT);
        $mform->setDefault('maxoutput', $defaultlimit);
        $cid=$this->vpl->get_course()->id;
        $mform->addElement('header', 'headerothersources', get_string('othersources', VPL));
        $mform->addElement('select', 'scanactivity', get_string('scanactivity',VPL),$this->list_activities($this->vpl->get_instance()->id));
        $mform->addElement('filepicker', 'scanzipfile0', get_string('scanzipfile', VPL));
        $mform->addElement('checkbox','searchotherfiles',get_string('scanother',VPL));
        $mform->setDefault('searchotherfiles', false);
        $this->add_action_buttons(false,get_string('search'));
    }
}
