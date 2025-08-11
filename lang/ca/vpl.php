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
 * Catalan translation, 06/2013
 * @author Antonio Piedras Morente (Universitat de Barcelona)
 * @copyright 2013 Antonio Piedras Morente (Universitat de Barcelona)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @var array $string
 * @package mod_vpl
 */

$string['VPL_COMPILATIONFAILED'] = 'La compilació o preparació de l\'execució ha fallat';
$string['about'] = 'Quant a';
$string['addfile'] = 'Afegeix fitxer';
$string['advanced'] = 'avançat';
$string['allfiles'] = 'Tots els fitxers';
$string['allsubmissions'] = 'Tots els lliuraments';
$string['anyfile'] = 'Qualsevol fitxer';
$string['attemptnumber'] = 'Intent número {$a}';
$string['automaticevaluation'] = 'Avaluació automàtica';
$string['automaticgrading'] = 'Qualificació automàtica';
$string['basedon'] = 'Basat en';
$string['basic'] = 'Bàsic';
$string['calculate'] = 'calcula';
$string['case_sensitive'] = 'Sensible a majúscules i minúscules';
$string['changesNotSaved'] = 'Canvis no desats';
$string['check_jail_servers'] = 'Comprovació de servidores presó';
$string['check_jail_servers_help'] = '<h1>Comprovar servidors pres&oacute;</h1>
<p>Aquesta p&agrave;gina comprova i mostra l\'estat dels servidors pres&oacute;
utilitzats per aquesta activitat.</p>';
$string['closed'] = 'Tancat';
$string['comments'] = 'Comentaris';
$string['compilation'] = 'Compilació';
$string['connected'] = "connectat";
$string['connecting'] = "connectant";
$string['connection_closed'] = "connexió tancada";
$string['connection_fail'] = "connexió fallida";
$string['console'] = 'Consola';
$string['contextual_help'] = 'Ajuda contextual';
$string['copy'] = 'Copia';
$string['copy_text'] = 'Copia el text seleccionat';
$string['create_new_file'] = 'Crea un nou fitxer per editar';
$string['currentstatus'] = 'Estat actual';
$string['cut'] = 'Talla';
$string['cut_text'] = 'Talla el text seleccionat';
$string['datesubmitted'] = 'Lliurat el';
$string['debug'] = 'Depura';
$string['debugging'] = "Depurant";
$string['defaultexefilesize'] = 'Mida màxima del fitxer executable per defecte';
$string['defaultexememory'] = 'Memòria màxima usada per defecte';
$string['defaultexeprocesses'] = 'Nombre màxim de processos per defecte';
$string['defaultexetime'] = 'Temps màxim d\'execució per defecte';
$string['defaultfilesize'] = 'Mida màxima del fitxer penjat per defecte';
$string['defaultresourcelimits'] = 'Límit de recursos en execució per defecte';
$string['delete'] = 'Esborra';
$string['delete_file_fq'] = "Esteu segur d'esborrar el fitxer '{\$a}'?";
$string['delete_file_q'] = 'Voleu esborrar el fitxer?';
$string['deleteallsubmissions'] = 'Elimina tots els lliuraments';
$string['description'] = 'Descripció';
$string['diff'] = 'diff';
$string['direct_applet'] = 'Desplegament directe de l\'applet';
$string['direct_applet_description'] = 'S\'utilitzen etiquetes HTML per desplegar l\'applet. Establiu aquesta opció si els clients no tenen accés a www.java.com';
$string['discard_submission_period'] = 'Període de descart de lliuraments';
$string['discard_submission_period_description'] = 'Per a cada estudiant i tasca, s\'intenta descartar lliuraments mantenint l\'última i almenys una per a cada període';
$string['download'] = 'Descarrega';
$string['downloadsubmissions'] = 'Baixa totes les trameses';
$string['duedate'] = 'data termini de lliurament';
$string['edit'] = 'Edita';
$string['editing'] = 'Editant';
$string['evaluate'] = 'Avalua';
$string['evaluateonsubmission'] = 'Avalua en lliurar';
$string['evaluating'] = "avaluant";
$string['evaluation'] = 'Avaluació';
$string['examples'] = 'Exemples';
$string['execution'] = 'Execució';
$string['executionfiles'] = 'Fitxers executables';
$string['executionfiles_help'] = '<h1>Fitxers executables</h1>
<h2>Introducci&oacute;</h2>
<p>Aqu&iacute; s\'estableixen els fitxers necessaris per l\'execuci&oacute;,
depurat o avaluaci&oacute; d\'un lliurament.
S\'inclouen fitxers de script, programes de prova i fitxers de dades.</p>
<h2>Script per omissi&oacute; per executar o depurar</h2>
<p>Si no s\'estableixen els script executables o per depuraci&oacute;,
el sistema dedueix el llenguatge empleat a partir de l\'extensi&oacute; dels fitxers lliurats
per utilitzar un script predefinits. La seg&uuml;ent taula mostra els llenguatges suportats, les extensions de fitxers usades, els script disponibles, el compilador/int&egrave;rpret i depurador utilitzat
per aquest i finalment un comentari sobre l\'&uacute;s del llenguatge.
<table>
<tr><th>Llenguatge</th>
<th>Extensions</th>
<th>Executa</th>
<th>Depura</th>
<th>Compilador/ int&egrave;rpret<br>depurador</th>
<th>Comentari</th>
</tr>
<tr>
<td>Ada</td><td>ada, adb, ads</td><td>X</td><td>X</td><td>gnat (Ada 2005)/gdb</td><td>Usa el primer fitxer com principal</td>
</tr>
<tr>
<td>C</td><td>c</td><td>X</td><td>X</td><td>gcc C99/gdb</td><td>Compila tots els fitxers font</td>
</tr>
<tr>
<td>C++</td><td>cpp, C</td><td>X</td><td>X</td><td>g++/gdb</td><td>Compila tots els fitxers font</td>
</tr>
<tr>
<td>C#</td><td>cs</td><td>X</td><td>X</td><td>gmcs+mono/mdb</td><td>Compila tots els fitxers font</td>
</tr>
<tr>
<td>Fortran</td><td>f, f77</td><td>X</td><td>X</td><td>gfortran/gdb</td><td>Compila tots els fitxers font</td>
</tr>
<tr>
<td>Haskell</td><td>hs</td><td>X</td><td></td><td>hugs</td><td>Executa el primer fitxer</td>
</tr>
<tr>
<td>Java</td><td>java</td><td>X</td><td>X</td><td>javac+java/jdb</td><td>Compila tots els fitxers font.<br />Localitza la classe amb main</td>
</tr>
<tr>
<td>Matlab/Octave</td><td>m</td><td>X</td><td>-</td><td>matlab, octave</td><td>Executa el primer fitxer.<br>Useu vpl_replot despr&eacute;s de dibuixar..</td>
</tr>
<tr>
<td>Pascal</td><td>pas, p</td><td>X</td><td>X</td><td>fpc o gpc/gdb</td><td>Compila el primer fihero (fpc) o compila tots els fitxers font.(gpc)</td>
</tr>
<tr>
<td>Perl</td><td>perl, prl</td><td>X</td><td>X</td><td>perl</td><td>Executa el primer fitxer.</td>
</tr>
<tr>
<td>PHP</td><td>php</td><td>X</td><td>-</td><td>php5</td><td>Executa el primer fitxer</td>
</tr>
<tr>
<td>Prolog</td><td>pl, pro</td><td>X</td><td>-</td><td>swipl</td><td>Executa el primer fitxer</td>
</tr>
<tr>
<td>Python</td><td>py</td><td>X</td><td>X</td><td>python</td><td>Executa el primer fitxer</td>
</tr>
<tr>
<td>Ruby</td><td>rb</td><td>X</td><td>X</td><td>ruby</td><td>Executa el primer fitxer</td>
</tr>
<tr>
<td>Scheme</td><td>scm, s</td><td>X</td><td>-</td><td>mzscheme</td><td>Executa el primer fitxer</td>
</tr>
<tr>
<td>Shell script</td><td>sh</td><td>X</td><td>-</td><td>bash</td><td>Executa el primer fitxer</td>
</tr>
<tr>
<td>SQL</td><td>sql</td><td>X</td><td>-</td><td>sqlite3</td><td>Executa tots els fitxers font.<br />Primer els establerts en fitxers executables</td>
</tr>
<tr>
<td>VHDL</td><td>vhd, vhdl</td><td>X</td><td>-</td><td>ghdl</td><td>Compila tots els fitxers font, el primer ha de tenir el m&egrave;tode Main.</td>
</tr>
</table>
<h2>Avaluaci&oacute; autom&agrave;tica</h2>
<p>Si voleu utilitzar les caracter&iacute;stiques d\'avaluaci&oacute; autom&agrave;tica de programes de VPL heu d\'emplenar el fitxer "vpl_evaluate.cases".
Aquest fitxer t&eacute; el seg&uuml;ent format:
<ul>
<li>"<b>case</b> = Descripci&oacute; del cas": Optional. Estableix l\'inici d\'un cas de prova.</li>
<li>"<b>input</b> = text": pot ocupar varies l&iacute;nies. Finalitza quan s\'introdueix una altra instrucci&oacute;.</li>
<li>"<b>output</b> = text": pot ocupar varies l&iacute;nies.  Finalitza amb altra instrucci&oacute;. Un cas de prova pot tenir varies sortides vÃ lides. Existeixen tres tipus de sortides: nom&eacute;s nombres, text i text exacte:
 <ul>
 <li><b>nombres</b>: S\'escriuen nom&eacute;s nombres. Nom&eacute;s es comproven els nombres de la sortida, la resta del text s\'ignora. Els nombres reals es comproven amb certa toler&agrave;ncia</li>
 <li><b>text</b>: Nom&eacute;s es comproven paraules, la comparaci&oacute; no distingeix entre maj&uacute;scules i min&uacute;scules i s\'ignoren la resta de car&agrave;cters.</li>
 <li><b>text exacte</b>: El text s\'escriu entre cometes dobles.</li>
 </ul>
 </li>
<li>"<b>grade reduction</b> = [valor|percentatge%]" : Per defecte quan es produeix un error es descompta de la nota m&agrave;xima  (rang_nota/nombre de casos)
 per&ograve; amb aquesta instrucci&oacute; es pot canviar el descompte per altre valor o percentatge.
</li>
 </ul>
<h2>&Uacute;s general</h2>
<p>Aqu&iacute; s\'estableixen els fitxers necessaris per preparar l\'execuci&oacute;, depuraci&oacute; o avaluaci&oacute; d\'una lliurament. Aix&ograve; inclou fitxers de script, fitxers de proves de programes i fitxers de dades.</p>
<p>Es pot afegir un nou fitxer escrivint el seu nom a la caixa "Afegeix fitxer" i prement despr&eacute;s el bot&oacute; "Afegeix fitxer".</p>
<p>Es pot carregar un fitxer existent usant el control "Carrega fitxer".</p>
<p>Tots els fitxers que s\'afegeixin o es carreguin poden ser editats, i tots, excepte els fitxers de script mencionats a continuaci&oacute;, poden canviar-se de nom o eliminar-se.</p>
<p>Han d\'existir tres fitxers de script per preparar cadascuna de les tres possibles accions. Aquests fitxers tenen noms predefinits: <b>vpl_run.sh</b> (execuci&oacute;), <b>vpl_debug.sh</b>, (depuraci&oacute;) i <b>vpl_evaluate.sh</b>, (avaluaci&oacute;)</p>
<p>L\'execuci&oacute; de qualsevol d\'aquests guions ha de generar un fitxer denominat <b>vpl_execution</b>. Aquest fitxer ha de contenir codi binari executable, o un script que comenci per ""#!/bin/sh". La no generaci&oacute; d\'aquest fitxer fa impossible executar l\'acci&oacute; seleccionada.</p>
<p>Si l\'activitat es basa en altra, els fitxers de l\'activitat base s\'afegeixen autom&agrave;ticament. Els continguts dels fitxers vpl_run.sh, vpl_debug.sh i vpl_evaluate.sh es concatenen per tota la cadena d\'activitats en la que es basi la present</p>
<p>Per &uacute;ltim, s\'afegeix el fitxer <b>vpl_environment.sh</b>, que contÃ© informaci&oacute; sobre el lliurament, el qual es subministra mitjan&ccedil;ant variables d\'entorn:</p>
<ul><li>LANG: el llenguatge empleat.</li>
<li>LC_ALL: el mateix valor que LANG.</li>
<li>VPL_MAXTIME: temps m&agrave;xim d\'execuci&oacute; en segons.</li>
<li>VPL_FILEBASEURL: URL per accedir als fitxers del curs.</li>
<li>VPL_SUBFILE#: nom dels fitxers lliurats per l\'alumne. # va de 0 a nombre de fitxers lliurats.</li>
<li>VPL_SUBFILES: llista de tots els fitxers lliurats.</li>
<li>VPL_VARIATION+id: on id &eacute;s l\'ordre de variaci&oacute; comen&ccedil;ant per 0 i el valor &eacute;s el valor de la variaci&oacute;.<br></li>
</ul>
Si l\'acci&oacute; sol&middot;licitada es &quot;avaluaci&oacute;&quot; s\'afegeixen les seg&uuml;ents variables:
<ul>
	<li>VPL_MAXTIME: m&agrave;xim temps d\'execuci&oacute; en segons.</li>
	<li>VPL_MAXMEMORY: m&agrave;xima mem&ograve;ria usable en bytes.</li>
	<li>VPL_MAXFILESIZE: mida m&agrave;xima en bytes d\'un fitxer.</li>
	<li>VPL_MAXPROCESSES: nombre m&agrave;xim de processos que poden executar-se simult&agrave;niamente.</li>
	<Li>VPL_FILEBASEURL: URL a fitxers del curs.</Li>
	<li>VPL_GRADEMIN: m&iacute;nima nota per aquesta activitat.</li>
	<li>VPL_GRADEMAX: m&agrave;xima nota per aquesta activitat.</li>
</ul>

<h2>Codis del resultat de l\'avaluaci&oacute;</h2>
<p>La sortida de l\'avaluaci&oacute; &eacute;s processada per extreure, si es possible, comentaris sobre l\'avaluaci&oacute; i la nota proposada.
Els comentaris es poden establir de dos maneres: amb un comentari de l&iacute;nia definit amb una l&iacute;nia que comen&ccedil;a per \'Comment :=&gt;&gt;\' o
amb comentaris de blocs que comen&ccedil;a amb una l&iacute;nia que cont&eacute; &uacute;nicament \'&lt;|--\' i termina en una l&iacute;nia que cont&eacute; &uacute;nicament \'--|&gt;\'.
La qualificaci&oacute; es pren de l\'&uacute;ltima l&iacute;nia que comenci per \'Grade :=&gt;&gt;\'.
</p>';
$string['executionoptions'] = 'Opcions d\'execució';
$string['executionoptions_help'] = '<h1>Opcions d\'execuci&oacute;</h1>
<p>En aquesta p&agrave;gina s\'estableixen diferents opcions d\'execuci&oacute;</p>
<ul>
<li><b>Basat en</b>: permet establir altra inst&agrave;ncia VPL de la que es prenen diverses caracter&iacute;stiques:
<ul><li>Fitxers executables (els guions predefinits es concatenen)</li>
<li>L&iacute;mits dels recursos d\'execuci&oacute;.</li>
<li>Variacions, que es concatenen generant variacions m&uacute;ltiples.</li>
<li>Mida m&agrave;xima de cada fitxer a pujar</li>
</ul>
</li>
<li><b>Executar, Depurar i Avaluar</b>: estableixen si es pot usar l\'opci&oacute; corresponent durant l\'edici&oacute; del lliurament. Aix&ograve; nom&eacute;s afecta als estudiants, els usuaris amb capacitat de qualificaci&oacute; poden usar aquestes opcions en qualsevol cas.</li>
<li><b>Avaluar en lliurar</b>: al pujar els fitxers es produeix el proc&eacute;s d\'avaluaci&oacute; autom&agrave;ticament.</li>
<li><b>Qualificaci&oacute; autom&agrave;tica</b>: si el resultat de l\'avaluaci&oacute; cont&eacute; codis de nota autom&agrave;tica aquests es prenen com a nota definitiva.</li>
</ul>';
$string['figure'] = 'Figura';
$string['file'] = 'Fitxer';
$string['fileNotChanged'] = 'Fitxer no modificat';
$string['file_name'] = 'No del fitxer';
$string['fileadded'] = "S'ha afegit el fitxer '{\$a}'";
$string['filedeleted'] = "S'ha esborrat el fitxer '{\$a}'";
$string['filenotadded'] = 'No s\'ha afegit el fitxer';
$string['filenotdeleted'] = 'El fitxer \'{$a}\' NO s\'ha esborrat';
$string['filenotrenamed'] = 'NO s\'ha canviat el nom del fitxer \'{$a}\'';
$string['filerenamed'] = "El fitxer '{\$a->from}' ha canviat de nom a '{\$a->to}'";
$string['filesChangedNotSaved'] = "Fitxers modificats però no desats";
$string['filesNotChanged'] = 'Fitxers no modificats';
$string['filestoscan'] = 'Fitxers a escanejar';
$string['fileupdated'] = "El fitxer '{\$a}' s'ha actualitzat";
$string['find'] = 'Busca';
$string['find_find_replace'] = 'Cerca o Cerca i Reemplaça text';
$string['find_next_search_string'] = 'Següent búsqueda en el text';
$string['find_replace'] = 'Cerca/Reemplaça';
$string['font_size'] = 'Mida de la lletra';
$string['fulldescription'] = 'Descripció completa';
$string['fulldescription_help'] = '<h1>Descripci&oacute; completa</h1>
<p>Escriviu aqu&iacute; la descripci&oacute; completa de la tasca a realitzar en el laboratori de programaci&oacute;.</p>
<p>En cas de que no escriviu res es mostrar&agrave; en el seu lloc la descripci&oacute; curta.</p>
<p>Si desitgeu realitzar una avaluaci&oacute; autom&agrave;tica, es aconsellable que l\'especificaci&oacute; de les interfaces sigui el m&eacute;s detallada possible i que no tingui ambig&uuml;itat.</p>';
$string['fullscreen'] = 'Pantalla completa';
$string['general_help'] = "Ajuda general sobre el llenguatge";
$string['go_next_page'] = 'Vés a la pàgina següent';
$string['gradeandnext'] = 'Qualifica & Sig';
$string['graded'] = 'Avaluades';
$string['gradedbyuser'] = 'Avaluades per l\'usuari';
$string['gradedon'] = "Avaluada el";
$string['gradedonby'] = "Avaluada el {\$a->date} per {\$a->gradername}";
$string['gradenotremoved'] = 'NO s\'ha pogut eliminar la qualificació. Comproveu la configuració de l\'activitat en qualificacions.';
$string['gradenotsaved'] = 'NO s\'ha desat la qualificació. Comproveu la configuració de l\'activitat en qualificacions.';
$string['gradeoptions'] = 'Avaluació';
$string['grader'] = "Avaluada per";
$string['gradercomments'] = "Comentaris del revisor";
$string['graderemoved'] = 'La qualificació s\'ha eliminat';
$string['groupwork'] = 'Treball en grup';
$string['help'] = 'Ajuda';
$string['help_about'] = 'Ajuda quant a';
$string['inconsistentgroup'] = 'No és membre d\'un únic grup (0 o >1)';
$string['incorrect_file_name'] = 'El nom del fitxer és incorrecte';
$string['individualwork'] = 'Treball individual';
$string['instanceselection'] = 'Selecció de VPL';
$string['isexample'] = 'Aquesta activitat actua com exemple';
$string['jail_servers'] = "Llista de servidors presó";
$string['jail_servers_config'] = "Configuració de servidors presó";
$string['jail_servers_description'] = "Escriviu un servidor en cada línia";
$string['joinedfiles'] = 'Fitxers seleccionats agrupats';
$string['keepfiles'] = "Fitxers a mantenir mentre s'està executant";
$string['keepfiles_help'] = '<h1>Fitxers a mantenir durant l\'execuci&oacute;</h1>
<p>Per raons de seguretat, els fitxers afegits en "Fitxers executables", son eliminats abans d\'executar el fitxer vpl_execution.</p>
<p>Si es necessari que algun d\'aquests fitxers es mantingui durant la fase d\'execuci&oacute;,
per exemple, per utilitzar-ho com a dades d\'entrada de les proves, marqueu-los en aquesta p&agrave;gina</p>';
$string['language_help'] = 'Ajuda del llenguatge';
$string['lasterror'] = 'Informació de l\'últim error';
$string['lasterrordate'] = 'Data de l\'últim error';
$string['line_number'] = 'Número de línia';
$string['listofcomments'] = 'Llista de comentaris';
$string['listsimilarity'] = 'S\'ha trobat la llista de similitud';
$string['listwatermarks'] = 'Llistat de marques d\'aigua';
$string['local_jail_servers'] = 'Servidors persó locals';
$string['local_jail_servers_help'] = '<h1>Servidors pres&oacute; locals</h1>
<p>Aqu&iacute; s\'estableixen els servidors pres&oacute; locals per aquesta activitat i les que es basen en ella.</p>
<p>Escriviu l\'URL complet del servidor en cada l&iacute;nia. Es poden introduir l&iacute;nies en blanc i comentaris comen&ccedil;ant la l&iacute;nia per "#".</p>
<p>Si es vol impedir que aquesta activitat i les que es basen en ella no utilitzi els servidors especificats en les activitats derivades ni
els especificats globalment, afegiu al final una l&iacute;nia que contingui "end_of_jails".
</p>';
$string['manualgrading'] = 'Qualificació manual';
$string['maxexefilesize'] = 'Màxima mida d\'un fitxer en execució';
$string['maxexememory'] = 'Màxima memòria utilitzada';
$string['maxexeprocesses'] = 'Màximo nombre de processos';
$string['maxexetime'] = 'Màxim temps d\'execució';
$string['maxfiles'] = 'Nombre màxim de fitxers';
$string['maxfilesexceeded'] = 'Superat el nombre màxim de fitxers';
$string['maxfilesize'] = 'Mida màxima de cada fitxer de pujada';
$string['maxfilesizeexceeded'] = 'Superada la mida màxima dels fitxers';
$string['maxresourcelimits'] = 'Límit màxim de recursos en execució';
$string['maxsimilarityoutput'] = 'Màxima sortida per similitud';
$string['menucheck_jail_servers'] = 'Comprovació presons';
$string['menuexecutionfiles'] = 'Fitxers';
$string['menuexecutionoptions'] = 'Opcions';
$string['menukeepfiles'] = "Fitxers a mantenir";
$string['menulocal_jail_servers'] = 'Presons locals';
$string['menuresourcelimits'] = 'Límits de recursos';
$string['minsimlevel'] = 'Nivell de similitud mínima a mostrar';
$string['moduleconfigtitle'] = 'Configuració del mòdul vpl';
$string['modulename'] = 'Laboratori virtual de programació';
$string['modulename_help'] = '<p><b>VPL. Laboratori Virtual de Programaci&oacute;</b></p>
<p>VPL permet la gesti&oacute; de pr&agrave;ctiques de programaci&oacute; tenint com a caracter&iacute;stiques m&eacute;s destacades:
<ul>
<li>Possibilitat d\'editar el codi font en el navegador.</li>
<li>Possibilitat d\'executar les pr&agrave;ctiques de forma interactiva des del navegador.</li>
<li>Possibilitat d\'executar proves que revisin les pr&agrave;ctiques.</li>
<li>Cerca de similitud entre pr&agrave;ctiques pel control del plagi.</li>
<li>Restriccions de lliurament de pr&agrave;ctiques que limiten el talla i enganxa de codi extern.</li>
</ul>
<p><a href="http://vpl.dis.ulpgc.es">Virtual Programming lab</a></p>';
$string['modulename_link'] = 'mod/vpl/view';
$string['modulenameplural'] = 'Laboratoris virtuals de programació';
$string['new'] = 'Nou';
$string['new_file_name'] = 'Nom del nou fitxer';
$string['next'] = 'Següent';
$string['next_page'] = 'Pàgina següent';
$string['nojailavailable'] = "No hi ha servidor presó disponible";
$string['noright'] = 'No teniu permís per accedir';
$string['nosubmission'] = 'No hi ha lliurament';
$string['notexecuted'] = 'No executat';
$string['notgraded'] = 'No avaluades';
$string['notsaved'] = 'No desat';
$string['novpls'] = 'No hi ha laboratori de programació definit';
$string['nowatermark'] = 'Marques d\'aigua pròpies {$a}';
$string['nsubmissions'] = '{$a} lliuraments';
$string['numcluster'] = 'Grup {$a}';
$string['open'] = 'Obert';
$string['opnotallowfromclient'] = 'Acció no permesa des d\'aquesta màquina';
$string['options'] = 'Opcions';
$string['optionsnotsaved'] = "Opcions no desades";
$string['optionssaved'] = "Opcions desades";
$string['origin'] = 'Origen';
$string['othersources'] = 'Altres fonts a utilitzar';
$string['page_unaccessible'] = 'No es pot accedir a la pàgina';
$string['paste'] = 'Enganxa';
$string['paste_text'] = 'Enganxa el text seleccionat';
$string['pluginadministration'] = 'Administració del VPL';
$string['pluginname'] = 'Laboratori virtual de programació';
$string['previous_page'] = 'Pàgina anterior';
$string['previoussubmissionslist'] = 'Mostra lliuraments previs';
$string['program_help'] = 'Ajuda de l\'editor';
$string['proposedgrade'] = 'Nota proposada: {$a}';
$string['proxy_port_from'] = "Valor inicial del rang de ports del proxy";
$string['proxy_port_from_description'] = "El proxy s'utilitza per connectar l'applet client amb el servidor presó. Establiu el valor inicial del rang de ports des dels que dóna servei el proxy";
$string['proxy_port_to'] = "Valor final del rango de ports del proxy";
$string['proxy_port_to_description'] = "El proxy s'utilitza per connectar l'applet client amb el servidor presó. Establiu el valor final del rang de ports des dels que dóna servei el proxy";
$string['redo'] = 'Refés';
$string['redo_undone'] = 'Refés canvis desfets';
$string['regularscreen'] = 'Pantalla normal';
$string['removegrade'] = 'Esborra la qualificació';
$string['rename'] = 'Canvia el nom';
$string['renameFile'] = 'Canvia el nom del fitxer';
$string['rename_file'] = 'Canvi el nom del fitxer';
$string['replace'] = 'Reemplaça';
$string['replace_all'] = 'Reemplaça tot';
$string['replace_all_next'] = 'Reemplaça totes les coincidències següents';
$string['replace_find'] = 'Reemplaça/Busca';
$string['replace_find_next'] = 'Reemplaça i torna a buscar en el text';
$string['replace_selection_if_match'] = 'Reemplaça el text seleccionat si coincideix amb el cercat';
$string['requestedfiles'] = 'Fitxers requerits';
$string['requestedfiles_help'] = '<h1>Fitxers requerits</h1>
<p>Aqu&iacute; es fixen noms pels fitxers requerits.</p>
<p>Si no es fixen noms pel nombre m&agrave;xim de fitxers establert en la definici&oacute; b&agrave;sica de l\'activitat, els fitxers pels que no s\'han establert noms s&oacute;n opcionals i poden tenir qualsevol nom.</p>
<p>A m&eacute;s, es poden establir continguts pels fitxers requerits, de forma que aquests continguts estaran disponibles la primera vegada que el fitxer s\'obri utilitzant l\'editor, si no se ha realitzat un lliurament previ.</p>';
$string['requirednet'] = 'Lliuraments restringits a la xarxa';
$string['requiredpassword'] = 'Cal una clau';
$string['resetfiles'] = 'Restablir fitxers';
$string['resetvpl'] = 'Reinicia {$a}';
$string['resourcelimits'] = 'Límits de recursos d\'execució';
$string['resourcelimits_help'] = '<h1>L&iacute;mit de recursos d\'execuci&oacute;</h1>
<p>Es poden establir l&iacute;mits m&agrave;xims pel temps d\'execuci&oacute;, la mem&ograve;ria utilitzada, la mida dels fitxers generats durant l\'execuci&oacute; i el nombre de processos simultanis.</p>
<p>Aquests l&iacute;mits s\'apliquen en executar els fitxers de script vpl_run.sh, vpl_debug.sh i vpl_evaluate.sh, i el fitxer vpl_execution generat per ells.</p>
<p>Si l\'activitat est&agrave; basada en altra, els l&iacute;mits establerts es poden veure restringits pels establerts en aquella i altres en la que la mateixa es basa, a m&eacute;s de pels establerts en la configuraci&oacute; global del m&ograve;dul.</p>';
$string['restrictededitor'] = "Només s'admeten lliuraments des del editor restringit";
$string['return_to_previous_page'] = 'Torna a la pàgina anterior';
$string['run'] = 'Executa';
$string['running'] = "En execució";
$string['save'] = 'Desa';
$string['savecontinue'] = 'Desa i continua';
$string['saved'] = 'Desat';
$string['savedfile'] = "El fitxer '{\$a}' s'ha desat";
$string['saveoptions'] = 'Desa opcions';
$string['saving'] = "Desant";
$string['scanactivity'] = 'Activitat';
$string['scandirectory'] = 'Directori';
$string['scanningdir'] = 'Examinant el directori ...';
$string['scanoptions'] = 'Opcions d\'escaneig';
$string['scanother'] = 'Escaneja similitud en altres fonts';
$string['scanzipfile'] = 'Fitxer zip';
$string['select_all'] = 'Selecciona tot';
$string['select_all_text'] = 'Selecciona tot el text';
$string['server'] = 'Servidor';
$string['serverexecutionerror'] = 'S\'ha produït un error en el servidor d\'execució';
$string['shortdescription'] = 'Descripció curta';
$string['similarity'] = 'Similitud';
$string['similarto'] = 'Similar a';
$string['startdate'] = 'data de disponibilitat';
$string['submission'] = 'Lliurament';
$string['submissionperiod'] = 'Període de lliurament';
$string['submissionrestrictions'] = 'Restriccions de lliurament';
$string['submissions'] = 'Lliuraments';
$string['submissionselection'] = 'Selecció de lliuraments';
$string['submissionslist'] = 'Llista de lliuraments';
$string['submissionview'] = 'Mostra lliurament';
$string['submittedon'] = 'Lliurat el';
$string['submittedonp'] = 'Lliurat el {$a}';
$string['sureresetfiles'] = 'Vol perdre tot el seu treball i restablir els fitxers al seu estat original?';
$string['test'] = 'Proves';
$string['testcases'] = 'Casos de prova';
$string['timelimited'] = 'Amb limitació de temps';
$string['timeunlimited'] = 'Sense límit de temps';
$string['toggle_show_line_number'] = 'Mostra número de línia';
$string['totalnumberoferrors'] = "Errors";

$string['undo'] = 'Desfés';
$string['undo_change'] = 'Desfés canvis';
$string['unzipping'] = 'Descomprimint ...';
$string['uploadfile'] = 'Carrega fitxer';
$string['usevariations'] = 'Usa variacions';
$string['variation_n'] = 'Variació {$a}';
$string['variation_n_i'] = 'Variació {$a->number}: {$a->identification}';
$string['variation_options'] = 'Opcions de variació';
$string['variations'] = 'Variacions';
$string['variations_help'] = '<h1>Variacions</h1>
<p>Es poden definir variacions per les activitats. Les variacions s\'assignen de forma aleat&ograve;ria als estudiants.</p>
<p>En aquesta p&agrave;gina es pot indicar si l\'activitat t&eacute; variacions, donar un t&iacute;tol al conjunt de variacions, i afegir les variacions desitjades.</p>
<p>Cada variaci&oacute; t&eacute; un codi d\'identificaci&oacute; i una descripci&oacute;. L\'identificador s\'usa en el fitxer <b>vpl_enviroment.sh</b> per passar la
variaci&oacute; assignada a l\'estudiant a los scripts. La descripci&oacute;, amb format HTML, es mostra als estudiants als que ha estat assignada la variaci&oacute; corresponent.</p>';
$string['variations_unused'] = 'Aquesta activitat té variacions, pero no estan habilitades';
$string['variationtitle'] = 'Títol de variació';
$string['varidentification'] = 'Identificació';
$string['visiblegrade'] = 'mostra avaluació';
$string['vpl'] = 'Laboratori virtual de programació';
$string['vpl:addinstance'] = 'Afegeix una nova instància vpl';
$string['vpl:grade'] = 'Avalua un lliurament';
$string['vpl:manage'] = 'Gestiona un vpl';
$string['vpl:setjails'] = 'Estableix servidors presó per instàncies concretes de VPL';
$string['vpl:similarity'] = 'Busca similituds entre lliuraments';
$string['vpl:submit'] = 'Fes lliuraments';
$string['vpl:view'] = 'Mostra la descripció completa d\'un vpl';
$string['vpl_debug.sh'] = "Prepara la depuració del programa";
$string['vpl_evaluate.cases'] = 'Escriviu aquí els casos de prova per avaluar automàticamente el programa';
$string['vpl_evaluate.sh'] = "Avalua el programa";
$string['vpl_run.sh'] = "Prepara l'execució del programa";
$string['workingperiods'] = 'Període de treball';
$string['worktype'] = 'Tipus de treball';
