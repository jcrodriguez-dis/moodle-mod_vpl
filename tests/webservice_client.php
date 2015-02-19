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
 * Example of VPL web service client
 *
 * @package mod_vpl
 * @copyright 2014 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/*********** DOCUMENTATION ************
* WARNING *
  You need to add manually to the URL of this script
the id of one VPL activity to be tested
(../mod/vpl/tests/webservice_client.php?id=1234)
THE ACTIVITY WILL BE MODIFIED

* INSTALLATION *
  The VPL webservice is installed or update with the VPL module.
To be available in a Moodle server, need be enabled:
 1) The external web service
 2) The REST protocol
 3) The VPL web service
 (in Home / ► Site administration / ► Plugins / ► Web services)

* USE *
  If all is OK, at the bottom of the activity's description page
will appear a  link to "Web service". Clicking this link will be
shown a page that gives us a URL to the VPL web service. This
URL must be copy and paste into the client by the user. This URL
content the token and the id of the VPL activity to be accessed.

* DEVELOPMENT OF WEB SERVICE CLIENTS *
  The service has been developed using the web service API of Moodle.
All the documentation of the Moodle Web service may be apply to
the VPL web service with some remarks: the service is using
a session token and the documentation refers in many cases to
permanent tokens. This mean that the access to the service requires
that the user session is open. If the user close the session
then the token is revoked.
  To adapt and simplify the use of the service, the URL generated
in "show_webservice.php" contents the session token and the id
of the activity. The URL also contents the parameter
"moodlewsrestformat=json" that set that the service response
is in JSON format. This parameter may be change to other formats
as xml. The URL end with a parameter without the value
"wsfunction=", the client must add the service function name as value.
To learn about the use of the available functions you may activate
the API documentation
(Home / ► Site administration / ► Plugins / ► Web services / ► Manage protocols)
The current implementation is a REST service with session tokens.
It is easy to switch to other protocols as SOAP or XML-RPC but you
need to change the class used in "webservice.php". Also you can use
a permanent token, in this case, you need to generate the
correct URL to the standard protocol server.
*********** END DOCUMENTATION ************/

require_once dirname(__FILE__).'/../../../config.php';

require_login();

require_once dirname(__FILE__).'/../vpl.class.php';

function vpl_call_service($url,$fun,$request=''){
    if(!function_exists('curl_init')){
        throw new Exception('PHP cURL requiered');
    }
    $plugincfg = get_config('mod_vpl');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url.$fun);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, 1);
    //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/urlencode;charset=UTF-8'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    if( @$plugincfg->acceptcertificates )
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $raw_response=curl_exec($ch);
    if($raw_response === false){
        $error='request failed: '.s(curl_error($ch));
        curl_close($ch);
        return $error;
    }else{
        curl_close($ch);
        return json_decode($raw_response);
    }
}

function vpl_call_print($res){
    echo '<pre>';
    s(print_r($res,false));
    echo '</pre>';
}

$id = required_param('id',PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->require_capability(VPL_MANAGE_CAPABILITY);
$vpl->prepare_page('tests/webservice_client.php', array('id' => $id));
$vpl->print_header('Web service test client');
echo '<h1>Web service test client</h1>';
echo '<h3>Session token generated (or reused)</h3>';
echo s(vpl_get_webservice_token($vpl));
$service_url = vpl_get_webservice_urlbase($vpl);
echo '<h3>Base URL for web service</h3>';
echo s($service_url);
echo '<h3>Get info from activity</h3>';
$res=vpl_call_service($service_url,'mod_vpl_info');
vpl_call_print($res);
echo '<h3>Get last submission</h3>';
$res=vpl_call_service($service_url,'mod_vpl_open');
vpl_call_print($res);
echo '<h3>Modify and save last submission</h3>';
if(isset($res->files)){
    $files=$res->files;
}
if(count($files)==0){
    $file = new stdClass();
    $file->name = 'test.c';
    $file->data = 'int main(){printf("hello");}';
    $files = array($file);
}else{
    foreach($files as $file){
        $file->data="Modification ".time()."\n".$file->data;
    }
}
$res->files=$files;
$body='';
foreach($files as $key => $file){
    if($key>0)
        $body.='&';
    $body.="files[$key][name]=".urlencode($file->name).'&';
    $body.="files[$key][data]=".urlencode($file->data);
}

$newres=vpl_call_service($service_url,'mod_vpl_save',$body);
vpl_call_print($newres);
echo '<h3>Reread file to test saved files</h3>';
$newres=vpl_call_service($service_url,'mod_vpl_open');
if(!isset($res->files) or !isset($newres->files) or $res->files != $newres->files){
    echo "Error";
}else{
    echo "OK";
}
vpl_call_print($newres);

echo '<h3>Call evaluate (unreliable test)</h3>';
echo '<h4>It may be unavailable</h4>';
echo '<h4>The client don\'t use websocket then the jail server may timeout</h4>';
$res=vpl_call_service($service_url,'mod_vpl_evaluate');
vpl_call_print($res);
sleep(5);
echo '<h3>Call get result of last evaluation (unreliable test)</h3>';
$res=vpl_call_service($service_url,'mod_vpl_get_result');
vpl_call_print($res);
$vpl->print_footer();
