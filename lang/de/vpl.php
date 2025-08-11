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
 * German language strings for the VPL module.
 *
 * @author
 * @copyright authors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @var array $string
 * @package mod_vpl
 */

$string['VPL_COMPILATIONFAILED'] = 'Die Kompilierung oder Vorbereitung zur Ausführung ist fehlgeschlagen';
$string['about'] = 'Über';
$string['addfile'] = 'Datei hinzufügen';
$string['advanced'] = 'Erweitert';
$string['allfiles'] = 'Alle Dateien';
$string['allsubmissions'] = 'Alle Abgaben';
$string['anyfile'] = 'Beliebige Datei';
$string['attemptnumber'] = 'Versuch Nummer {$a}';
$string['automaticevaluation'] = 'Automatische Evaluierung';
$string['automaticgrading'] = 'Automatische Bewertung';
$string['averageperiods'] = 'Durchschn. Überarbeitungen {$a}';
$string['averagetime'] = 'Durchschnittszeit {$a}';
$string['basedon'] = 'basiert auf';
$string['basic'] = 'Einfach';
$string['calculate'] = 'Berechnen';
$string['case_sensitive'] = 'Groß-/Kleinschreibung beachten';
$string['changesNotSaved'] = 'Änderungen wurden nicht gespeichert';
$string['check_jail_servers'] = 'Jail-Server überprüfen';
$string['check_jail_servers_help'] = '<p>Diese Seite überprüft und zeigt den Status der Jail-Server,
        die für diese Aktivität verwendet werden.</p>';
$string['closed'] = 'Geschlossen';
$string['comments'] = 'Kommentare';
$string['compilation'] = 'Kompilierung';
$string['connected'] = 'Verbunden';
$string['connecting'] = 'Verbinde';
$string['connection_closed'] = 'Verbindung geschlossen';
$string['connection_fail'] = 'Verbindungsfehler';
$string['console'] = 'Konsole';
$string['contextual_help'] = 'Kontextuelle Hilfe';
$string['copy'] = 'Kopieren';
$string['copy_text'] = 'Ausgewählten Text in die Zwischenablage kopieren';
$string['create_new_file'] = 'Neue Datei erstellen';
$string['currentstatus'] = 'Aktueller Status';
$string['cut'] = 'Ausschneiden';
$string['cut_text'] = 'Ausgewählten Text in die Zwischenablage verschieben';
$string['datesubmitted'] = 'Abgabedatum';
$string['debug'] = 'Debuggen';
$string['debugging'] = 'Debuggt';
$string['defaultexefilesize'] = 'Standard maximale Größe der Ausführungsdatei';
$string['defaultexememory'] = 'Standard maximaler Speicherbedarf';
$string['defaultexeprocesses'] = 'Standard maximale Anzahl an Prozessen';
$string['defaultexetime'] = 'Standard maximale Ausführungszeit';
$string['defaultfilesize'] = 'Standard maximale Upload Dateigröße';
$string['defaultresourcelimits'] = 'Standard maximaler Ressourcenverbrauch bei Ausführung';
$string['delete'] = 'Löschen';
$string['delete_file_fq'] = "Datei '{\$a}' löschen?";
$string['delete_file_q'] = 'Datei wirklich löschen?';
$string['deleteallsubmissions'] = 'Alle Abgaben löschen';
$string['description'] = 'Beschreibung';
$string['diff'] = 'diff';
$string['direct_applet'] = 'Direktes Applet Deployment';
$string['direct_applet_description'] = 'Verwende HTML tags, um das Applet zu deployen. Diese Option wird benötigt, wenn Clients keinen Zugriff auf www.java.com haben.';
$string['discard_submission_period'] = 'Abgabenspeicherungsperiode';
$string['discard_submission_period_description'] = 'Pro Student und Aufgabe behält das System immer die letzte Abgabe sowie zumindest eine für jede Periode.';
$string['download'] = 'Herunterladen';
$string['downloadsubmissions'] = 'Alle Abgaben herunterladen';
$string['duedate'] = 'Abgabetermin';
$string['edit'] = 'Bearbeiten';
$string['editing'] = 'Bearbeiten';
$string['evaluate'] = 'Evaluieren';
$string['evaluateonsubmission'] = 'Nur bei Abgabe evaluieren';
$string['evaluating'] = 'Evaluiert';
$string['evaluation'] = 'Evaluierung';
$string['examples'] = 'Beispiele';
$string['execution'] = 'Ausführung';
$string['executionfiles'] = 'Ausführungsdateien';
$string['executionfiles_help'] = '<h2>Einleitung</h2>
<p>Hier definiert man die Dateien, die für die Vorbereitung der Ausführung, des Debuggens oder der Bewertung einer Abgabe benötigt werden. Dazu zählen unter anderem Skripts, Test-Dateien oder Daten.</p>
<h2>Standard Skript zum Ausführen oder Debuggen</h2>
<p>Wenn kein Skript zum Ausführen oder Debuggen von Abgaben angegeben wird, verwendet das System automatisch ein Standardskript für die verwendete Sprache (abhängig von der angegebenen Dateiendung). Die folgende Tabelle zeigt die unterstützten Programmiersprachen mit den zugehörigen Dateiendungen und den verwendeten compiler/interpreter/debugger, der im Skript verwendet wird.</p>
<table>
<tr><th>Sprache</th>
<th>Dateiname<br />
Endung</th>
<th>ausführen</th>
<th>debuggen</th>
<th>Compiler/<br />interpreter</th>
<th>Kommentar</th>
</tr>
<tr>
<td>Ada</td><td>ada, adb, ads</td><td>X</td><td>X</td><td>gnat (Ada 2005)/gdb</td><td>Erste Datei wird als main verwendet</td>
</tr>
<tr>
<td>C</td><td>c</td><td>X</td><td>X</td><td>gcc C99/gdb</td><td>Alle Quelldateien werden kompiliert</td>
</tr>
<tr>
<td>C++</td><td>cpp, C</td><td>X</td><td>X</td><td>g++/gdb</td><td>Alle Quelldateien werden kompiliert</td>
</tr>
<tr>
<td>C#</td><td>cs</td><td>X</td><td>X</td><td>gmcs+mono/mdb</td><td>Alle Quelldateien werden kompiliert</td>
</tr>
<tr>
<td>Fortran</td><td>f, f77</td><td>X</td><td>X</td><td>gfortran/gdb</td><td>Alle Quelldateien werden kompiliert</td>
</tr>
<tr>
<td>Haskell</td><td>hs</td><td>X</td><td></td><td>hugs</td><td>Erste Datei wird ausgeführt</td>
</tr>
<tr>
<td>Java</td><td>java</td><td>X</td><td>X</td><td>javac+java/jdb</td><td>Alle Quelldateien werden kompiliert<br />main-Klasse wird automatisch gefunden</td>
</tr>
<tr>
<td>Matlab/Octave</td><td>m</td><td>X</td><td>-</td><td>matlab, octave</td><td>Erste Datei wird ausgeführt<br>Verwende vpl_replot nach dem Zeichnen.</td>
</tr>
<tr>
<td>Pascal</td><td>pas, p</td><td>X</td><td>X</td><td>fpc or gpc/gdb</td><td>Erste Datei wird kompiliert (fpc) oder alle Dateien (gpc)</td>
</tr>
<tr>
<td>Perl</td><td>perl, prl</td><td>X</td><td>X</td><td>perl</td><td>Erste Datei wird ausgeführt</td>
</tr>
<tr>
<td>PHP</td><td>php</td><td>X</td><td>-</td><td>php5</td><td>Erste Datei wird ausgeführt</td>
</tr>
<tr>
<td>Prolog</td><td>pl, pro</td><td>X</td><td>-</td><td>swipl</td><td>Erste Datei wird ausgeführt</td>
</tr>
<tr>
<td>Python</td><td>py</td><td>X</td><td>X</td><td>python</td><td>Erste Datei wird ausgeführt</td>
</tr>
<tr>
<td>Ruby</td><td>rb</td><td>X</td><td>X</td><td>ruby</td><td>Erste Datei wird ausgeführt</td>
</tr>
<tr>
<td>Scheme</td><td>scm, s</td><td>X</td><td>-</td><td>mzscheme</td><td>Erste Datei wird ausgeführt</td>
</tr>
<tr>
<td>Shell script</td><td>sh</td><td>X</td><td>-</td><td>bash</td><td>Erste Datei wird ausgeführt</td>
</tr>
<tr>
<td>SQL</td><td>sql</td><td>X</td><td>-</td><td>sqlite3</td><td>Alle Dateien werden ausgeführt<br />Ausführungsdateien zuerst</td>
</tr>
<tr>
<td>VHDL</td><td>vhd, vhdl</td><td>X</td><td>-</td><td>ghdl</td><td>Alle Dateien werden kompiliert; die erste Datei muss die Main-Methode definieren</td>
</tr>
</table>
<h2>Automatische Evaluierung</h2>
<p>Seit VPL 1.4 gibt es eine Funktion, um Abgaben automatisch zu bewerten. Diese Funktion führt ein abgegebenes Programm aus und vergleicht die Ausgabe zu einer vorgegebenen Eingabe. Diese Testfälle müssen in der Datei &quot;vpl_evaluate.cases&quot; definiert werden.</p>
<p>Die Datei &quot;vpl_evaluate.cases&quot; ist folgendermaßen aufgebaut:
<ul>
<li> &quot;<strong>case </strong>= Beschreibung des Testfalls&quot;: Optional. Definiert den Start eines Anwendungsfalls.</li>
<li> &quot;<strong>input </strong>= text&quot;: kann mehrere Zeilen umfassen und wird mit der nächsten Instruktion abgeschlossen.</li>
<li> &quot;<strong>output </strong>= text&quot;: kann mehrere Zeilen umfassen und wird mit der nächsten Instruktion abgeschlossen. Ein Testfall kann mehrere richtige Ausgaben haben. Dazu gibt es drei verschiedene Arten: Zahlen, Text und exakter Text:
<ul>
<li> <strong>number</strong>: definiert als eine Folge von Zahlen (ganze und fließkomma). Es werden in der Ausgabe nur Zahlen beachten und anderer Text ignoriert. Fließkommazahlen besitzen eine gewisse Toleranz.</li>
<li> <strong>text</strong>: definiert als ein Text ohne Anführungszeichen. Es werden in der Ausgabe nur Wörter beachtet und die restlichen Zeichen ignoriert. Groß- und Kleinschreibung wird auch ignoriert.</li>
<li> <strong>exact text</strong>: definiert als ein Text mit Anführungszeichen. Jedes Zeichen der Ausgabe muss übereinstimmen.</li>
</ul>
</li>
<li> &quot;<strong>grade reduction</strong> = [Wert|Prozentsatz%]&quot; : Standardmäßig reduziert ein fehlgeschlagener Testfall die Punkte der Abgabe anteilsmäßig (Gesamtpunkte/Anzahl der Testfälle). Diese Anweisung ändert jedoch den Punkteabzug.</li>
</ul>
</p>
<h2>Allgemeine Verwendung</h2>
<p>Eine neue Datei kann hinzugefügt werden, indem man ihren Namen in das Eingabefeld unter &quot;<b>Datei hinzufügen</b>&quot; schreibt, und auf die Schaltfläche &quot;<b>Datei hinzufügen</b>&quot; klickt.</p>
<p>Eine existierende Datei kann hochgeladen werden, indem man auf &quot;<b>Datei hochladen </b>&quot; klickt.</p>
<p>Alle hinzugefügten oder hochgeladenen Dateien können geändert werden und alle, außer den drei unten genannten Skripts können umbenannt oder gelöscht werden.</p>
<h2>Manuelle Ausführung, Debugging oder Evaluierung</h2>
<p>Es können drei Skripts zur Vorbereitung jeder dieser Aktionen definiert werden. Diese Skripts besitzen vordefinierte Namen: <b>vpl_run.sh</b> (Ausführung),
<b>vpl_debug.sh</b>  (Debugging) and <b>vpl_evaluate.sh</b> (Evaluierung).</p>
<p>Die Ausführung eines dieser Skripts soll eine Datei namens <b>vpl_execution</b> generieren. Diese Datei muss eine ausführbare Binärdatei sein oder ein Skript, das mit &quot;#!/bin/sh &quot; beginnt. Wenn diese Datei nicht erstellt wird, kann die Aktion nicht weiter ausgeführt werden.</p>
<p>Wenn die konfigurierte Aktivität auf einer anderen Aktivität basiert, werden die referenzierten Basisdateien automatisch hinzugefügt.</p>
<p>Schließlich wird die Datei <b>vpl_environment.sh</b> automatisch hinzugefügt. Dieses Skript beinhaltet Informationen über die Abgabe und bietet diese als Umgebungsvariablen an:</p>
<ul> <li> LANG:  verwendete Sprache. </li>
<li> LC_ALL: gleicher Wert wie LANG. </li>
<li> VPL_MAXTIME: maximale Ausführungszeit in Sekunden. </li>
<li> VPL_FILEBASEURL: URL, um auf die Dateien des Kurses zuzugreifen to access the files of the course. </li>
<li> VPL_SUBFILE#: jeder Name von Dateien der Abgabe. Das #-Symbol stellt Zahlen zwischen 0 und der Anzahl der abgegebenen Dateien. </li>
<li> VPL_SUBFILES: Liste aller abgegebenen Dateien. </li>
<li> VPL_VARIATION + id: wobei die id die Variationsreihenfolge angibt und mit 0 anfängt und der Variablenwert den Wert der Variation angibt.</li>
</ul>
Wenn die Aktion Evaluierung ausgeführt wird, werden die folgenden Variablen auch hinzugefügt.
<ul>
	<li>VPL_MAXTIME: maximale Ausführungszeit in Sekunden.</li>
	<li>VPL_MAXMEMORY: maximale Hauptspeicherverwendung</li>
	<li>VPL_MAXFILESIZE: maximale Dateigröße in byte, die erstellt werden kann.</li>
	<li>VPL_MAXPROCESSES: maximale Anzahl an Prozessen, die gleichzeitig ausgeführt werden.</li>
	<Li>VPL_FILEBASEURL: URL zu den Kursdateien.</Li>
	<li>VPL_GRADEMIN: Minimale Bewertung für diese Aktivität</li>
	<li>VPL_GRADEMAX: Maximale Bewertung für diese Aktivität</li>
</ul>
<h2>Bewertungsergebnis</h2>
<p>Die Evaluierungsausgabe wird verarbeitet, um Kommentare und eine vorgeschlagene Bewertung für die Aufgabe zu erhalten. Kommentare können über zwei Arten hinzugefügt werden: mit einem Zeilenkommentar, das mit \'Comment :=&gt;&gt;\' beginnt oder mit einem Blockkommentar, das mit der Zeile \'&lt;|--\' beginnt und mit der Zeile \'--|&gt;\' endet. Die Bewertung wird von der letzten Zeile die mit \'Grade :=&gt;&gt;\' beginnt genommen.</p>';
$string['executionoptions'] = 'Ausführungsoptionen';
$string['executionoptions_help'] = '<p>Auf dieser Seite können verschiedene Ausführungsoptionen konfiguriert werden.</p>
<ul>
<li><b>Basiert auf</b>: selektiert eine andere VPL Instanz, von der einige Eigenschaften importiert werden:
<ul><li>Ausführungsdateien (Vordefinierte Skripts werden zusammengefügt)</li>
<li>Grenzen für die Ausführungsressourcen.</li>
<li>Variationen, die zusammengefügt werden, um Multivariationen zu erzeugen.</li>
<li>Maximale Größe jeder Datei, die mit der Abgabe hochgeladen wurde.</li>
</ul>
</li>
<li><b>Ausführen</b>, <b>Debuggen</b> and <b>Evalauieren</b>: Muss auf &quot;Ja&quot; gesetzt werden wenn die jeweilige Aktion beim bearbeiten der Abgabe für Studenten auswählbar sein soll.</li>
<li><b>Nur bei Abgabe evaluieren</b>: Die Abgabe wird automatisch evaluiert, sobald sie hochgeladen wird.</li>
<li><b>Automatische Bewertung</b>: Wenn das Evaluierungsergebnis Bewertungen enthält, werden diese automatisch als Bewertung für die Abgabe angewandt.</li>
</ul>';
$string['figure'] = 'Abbildung';
$string['file'] = 'Datei';
$string['fileNotChanged'] = 'Datei ist unverändert';
$string['file_name'] = 'Dateiname';
$string['fileadded'] = "Die Datei '{\$a}' wurde hinzugefügt";
$string['filedeleted'] = "Die Datei '{\$a}' wurde gelöscht";
$string['filenotadded'] = 'Datei wurde nicht hinzugefügt';
$string['filenotdeleted'] = 'Die Datei \'{$a}\' wurde NICHT gelöscht';
$string['filenotrenamed'] = 'Die Datei \'{$a}\' wurde NICHT umbenannt';
$string['filerenamed'] = "Die Datei '{\$a->from}' wurde auf '{\$a->to}' umbenannt";
$string['filesChangedNotSaved'] = 'Dateien sind verändert aber sie wurden nicht gespeichert';
$string['filesNotChanged'] = 'Dateien sind unverändert';
$string['filestoscan'] = 'Zu prüfende Dateien';
$string['fileupdated'] = "Die Datei '{\$a}' wurde aktualisiert";
$string['find'] = 'Finden';
$string['find_find_replace'] = 'Suchen oder suchen und ersetzen';
$string['find_next_search_string'] = 'Finde den nächsten Suchtext im Text';
$string['find_replace'] = 'Suchen/Ersetzen';
$string['font_size'] = 'Schriftgröße';
$string['fulldescription'] = 'Beschreibung';
$string['fulldescription_help'] = '<p>Hier wird die komplette Beschreibung für diese Aktivität eingetragen.</p>
<p>Wenn hier nichts angegeben wird, wird stattdessen die Kurzbeschreibung angezeigt.</p>
<p>Wenn automatisch evaluiert werden soll, müssen die Interfacebeschreibungen für die Aufgabenstellungen detailliert und eindeutig sein.</p>';
$string['fullscreen'] = 'Vollbildmodus';
$string['general_help'] = 'Allgemeine Hilfe zur Sprache';
$string['go_next_page'] = 'Weiter zur nächsten Seite';
$string['gradeandnext'] = 'Bewerten & nächstes';
$string['graded'] = 'Bewertet';
$string['gradedbyuser'] = 'Bewertet durch Benutzer';
$string['gradedon'] = "Bewertet am";
$string['gradedonby'] = 'Bewertet am {$a->date} von {$a->gradername}';
$string['gradenotremoved'] = 'Die Bewertung wurde NICHT gelöscht. Überprüfen Sie die Aktivitätskonfiguration in der Bewertungsverwaltung.';
$string['gradenotsaved'] = 'Die Bewertung wurde NICHT gespeichert. Überprüfen Sie die Aktivitätskonfiguration in der Bewertungsverwaltung.';
$string['gradeoptions'] = 'Benotungseinstellungen';
$string['grader'] = "Bewerter";
$string['gradercomments'] = 'Kommentare zur Bewertung';
$string['graderemoved'] = 'Die Bewertung wurde gelöscht';
$string['groupwork'] = 'Gruppenarbeit';
$string['help'] = 'Hilfe';
$string['help_about'] = 'Hilfe über';
$string['inconsistentgroup'] = 'Sie sind nicht Mitglied nur einer Gruppe (0 o >1)';
$string['incorrect_file_name'] = 'Falscher Dateiname';
$string['individualwork'] = 'Einzelarbeit';
$string['instanceselection'] = 'VPL Auswahl';
$string['isexample'] = 'Dies ist eine Beispielaktivität';
$string['jail_servers'] = 'Jail-Server Liste';
$string['jail_servers_config'] = 'Jail-Server Konfiguration';
$string['jail_servers_description'] = 'Jeder Server in eine eigene Zeile';
$string['joinedfiles'] = 'Zusammengefügte ausgewählte Dateien';
$string['keepfiles'] = 'Dateien, die beim Ausführen behalten werden';
$string['keepfiles_help'] = '<p>Aufgrund von Sicherheitsrisiken werden Dateien, die als &quot;Ausführungsdateien&quot; hinzugefügt wurden,
gelöscht bevor das Skript vpl_execution ausgeführt wird.</p>
Falls einige dieser Dateien während der Ausführung benötigt werden (zum Beispiel als Testdaten) müssen sie hier markiert werden.';
$string['language_help'] = 'Sprachenspezifische Hilfe';
$string['lasterror'] = 'Letzte Fehlerbeschreibung';
$string['lasterrordate'] = 'Letzter Fehlerzeitpunkt';
$string['line_number'] = 'Zeilennummer';
$string['listofcomments'] = 'Kommentarliste';
$string['listsimilarity'] = 'Liste der gefundenen Ähnlichkeiten';
$string['listwatermarks'] = 'Wasserzeichen-Liste';
$string['local_jail_servers'] = 'Lokale Jail-Server';
$string['local_jail_servers_help'] = '<p>Hier können die lokalen Jail-Server für diese Aktivität definiert werden.</p>
<p>Jede Zeile beinhaltet die komplette URL eines Servers. Leere Zeilen und Kommentare die mit &quot;#&quot; starten können verwendet werden.</p>
<p>Diese Aktivität wird als Jail-Server Liste verwenden: die Server, die hier gesetzt werden plus die Server der Aktivität auf der diese Aktivität
basiert plus die allgemeinen Jail-Server. Wenn diese Aktivität und jene, die auf dieser Aktivität basieren, keine anderen Jail-Server benutzen sollen, kann man am Ende die Zeile &quot;end_of_jails&quot; anfügen.</p>';
$string['manualgrading'] = 'Manuelle Bewertung';
$string['maxexefilesize'] = 'Maximale Größe der Ausführungsdatei';
$string['maxexememory'] = 'Maximaler Speicherbedarf';
$string['maxexeprocesses'] = 'Maximale Anzahl an Prozessen';
$string['maxexetime'] = 'Maximale Ausführungszeit';
$string['maxfiles'] = 'Maximale Anzahl an Dateien';
$string['maxfilesexceeded'] = 'Maximale Anzahl an Dateien überschritten';
$string['maxfilesize'] = 'Maximale Upload Dateigröße';
$string['maxfilesizeexceeded'] = 'Maximale Dateigröße überschritten';
$string['maximumperiod'] = 'Max. Überarbeitungen {$a}';
$string['maxresourcelimits'] = 'Maximaler Ressourcenverbrauch bei Ausführung';
$string['maxsimilarityoutput'] = 'Maximale Ausgabe durch Ähnlichkeit';
$string['menucheck_jail_servers'] = 'Jail-Server überprüfen';
$string['menuexecutionfiles'] = 'Ausführungsdateien';
$string['menuexecutionoptions'] = 'Optionen';
$string['menukeepfiles'] = 'Zu behaltene Dateien';
$string['menulocal_jail_servers'] = 'Lokale Jail-Server';
$string['menuresourcelimits'] = 'Ressourcenbeschränkungen';
$string['minsimlevel'] = 'Minimale angezeigte Ähnlichkeit';
$string['moduleconfigtitle'] = 'VPL Module Konfiguration';
$string['modulename'] = 'Virtual programming lab';
$string['modulename_help'] = '<p>VPL ist ein Aktivitätsmodul für Moodle, das Programmieraufgaben verwaltet und folgende Eigenschaften aufweist:
</p>
<ul>
<li>Möglichkeit, den Programmcode im Browser</li>
<li>Studenten können Programme interaktiv im Browser ausführen</li>
<li>Automatische Tests zur Bewertung von Programmen können ausgeführt werden</li>
<li>Möglichkeit, Ähnlichkeiten zwischen Dateien zu suchen</li>
<li>Möglichkeit, Änderungseinschränkungen und externe Einfügeoptionen zu konfigurieren</li>
</ul>
<p><a href="http://vpl.dis.ulpgc.es">Virtual Programming lab</a></p>';
$string['modulename_link'] = 'mod/vpl/view';
$string['modulenameplural'] = 'Virtual programming labs';
$string['new'] = 'Neu';
$string['new_file_name'] = 'Neuer Dateiname';
$string['next'] = 'Weiter';
$string['next_page'] = 'Nächste Seite';
$string['nojailavailable'] = 'Kein Jail-Server verfügbar';
$string['noright'] = 'Keine ausreichenden Zugriffsberechtigungen';
$string['nosubmission'] = 'Keine Abgabe verfügbar';
$string['notexecuted'] = 'Nicht ausgeführt';
$string['notgraded'] = 'Nicht bewertet';
$string['notsaved'] = 'Nicht gespeichert';
$string['novpls'] = 'Kein virtual programming lab definiert';
$string['nowatermark'] = 'Eigene Wasserzeichen {$a}';
$string['nsubmissions'] = '{$a} abgaben';
$string['numcluster'] = 'Gruppe {$a}';
$string['open'] = 'Offen';
$string['opnotallowfromclient'] = 'Aktion ist von diesem Rechner nicht erlaubt';
$string['options'] = 'Optionen';
$string['optionsnotsaved'] = 'Einstellungen wurden nicht gespeichert';
$string['optionssaved'] = 'Einstellungen wurden gespeichert';
$string['origin'] = 'Ursprung';
$string['othersources'] = 'Andere zu prüfende Quellen';
$string['page_unaccessible'] = 'Unerreichbare Seite';
$string['paste'] = 'Einfügen';
$string['paste_text'] = 'Text aus der Zwischenablage einfügen';
$string['pluginadministration'] = 'VPL Administration';
$string['pluginname'] = 'Virtual programming lab';
$string['previous_page'] = 'Vorige Seite';
$string['previoussubmissionslist'] = 'Vorige Abgabeliste';
$string['program_help'] = 'Programmhilfe';
$string['proposedgrade'] = 'Bewertungsvorschlag: {$a}';
$string['redo'] = 'Wiederholen';
$string['redo_undone'] = 'Änderungen wiederherstellen';
$string['regularscreen'] = 'Fenstermodus';
$string['removegrade'] = 'Bewertung löschen';
$string['rename'] = 'Umbenennen';
$string['renameFile'] = 'Datei umbenennen';
$string['replace'] = 'Ersetzen';
$string['replace_all'] = 'Alle ersetzen';
$string['replace_all_next'] = 'Alle weiteren ersetzen';
$string['replace_find'] = 'Ersetzen/Finden';
$string['replace_find_next'] = 'Ersetzen und den nächsten Suchtext finden';
$string['replace_selection_if_match'] = 'Ersetze Auswahl, wenn sie dem Suchtext entspricht';
$string['requestedfiles'] = 'Erforderliche Dateien';
$string['requestedfiles_help'] = '<p>Hier können Namen für die erforderlichen Dateien gesetzt werden.</p>
<p>Wenn nicht für alle erforderlichen Dateien Namen gesetzt werden sind die unbenannten Dateien optionen und können beliebige Namen erhalten.</p>
<p>Man kann zusätzlich bereits Inhalte für die erforderlichen Dateien erzeugen, die beim ersten Öffnen mit dem Editor sichtbar werden, wenn noch keine andere Abgabe vorhanden ist.</p>';
$string['requirednet'] = 'Erlaubte Online-Abgabe';
$string['requiredpassword'] = 'Ein Passwort wird benötigt';
$string['resetfiles'] = 'Dateien zurücksetzen';
$string['resetvpl'] = '{$a} zurücksetzen';
$string['resourcelimits'] = 'Ressourcenbeschränkungen';
$string['resourcelimits_help'] = '<p>Grenzen für das Maximum können für die Ausführungszeit, den benutzten Arbeitsspeicher, die Ausführungsdateigrößen und die Anzahl der gleichzeitigen Prozesse gesetzt werden.</p>
<p>Diese Grenzen werden verwendet, wenn die Skripts vpl_run.sh, vpl_debug.sh, vpl_evaluate.sh und vpl_execution ausgeführt werden.</p>
<p>Wenn diese Aktivität auf einer anderen Aktivität basiert können diese Grenzen von denen der Basisaktivität und der globalen Konfiguration beeinflusst werden.</p>';
$string['restrictededitor'] = 'Abgabe durch eingeschränkten Code Editor';
$string['return_to_previous_page'] = 'Züruck zur vorigen Seite';
$string['run'] = 'Ausführen';
$string['running'] = 'Läuft';
$string['save'] = 'Speichern';
$string['savecontinue'] = 'Speichern und fortfahren';
$string['saved'] = 'Gespeichert';
$string['savedfile'] = "Die Datei '{\$a}' wurde gespeichert";
$string['saveoptions'] = 'Einstellungen speichern';
$string['saving'] = 'Speichert';
$string['scanactivity'] = 'Aktivität';
$string['scandirectory'] = 'Ordner';
$string['scanningdir'] = 'Prüfe Ordner ...';
$string['scanoptions'] = 'Prüfeinstellungen';
$string['scanother'] = 'Prüfe Ähnlichkeit in hinzugefügten Quellen';
$string['scanzipfile'] = 'Zip-Datei';
$string['select_all'] = 'Alles markieren';
$string['select_all_text'] = 'Gesamten Text markieren';
$string['server'] = 'Server';
$string['serverexecutionerror'] = 'Server Ausführungsfehler';
$string['shortdescription'] = 'Kurzbeschreibung';
$string['similarity'] = 'Ähnlichkeit';
$string['similarto'] = 'Ähnlich wie';
$string['startdate'] = 'Verfügbar von';
$string['submission'] = 'Abgabe';
$string['submissionperiod'] = 'Abgabezeitraum';
$string['submissionrestrictions'] = 'Abgabeeinschränkungen';
$string['submissions'] = 'Abgaben';
$string['submissionselection'] = 'Abgabeauswahl';
$string['submissionslist'] = 'Abgabeliste';
$string['submissionview'] = 'Abgabesicht';
$string['submittedby'] = 'Abgegeben von {$a}';
$string['submittedon'] = 'Abgegeben am';
$string['submittedonp'] = 'Abgegeben am {$a}';
$string['sureresetfiles'] = 'Wollen Sie Ihre Änderungen wirklich verwerfen?';
$string['test'] = 'Testaktivität';
$string['testcases'] = 'Testfälle';
$string['testcases_help'] = '<p>Dieses Feature erlaubt es, Studentenprogramme auszuführen und die Ausgabe für eine gegebene Eingabe zu überprüfen. Um die Testfälle zu konfigurieren, muss die Datei &quot;vpl_evaluate.cases&quot; befüllt werden.</p>
<p>Die Datei &quot;vpl_evaluate.cases&quot; ist folgendermaßen aufgebaut:
<ul>
<li> &quot;<strong>case </strong>= Beschreibung des Testfalls&quot;: Optional. Definiert den Start eines Anwendungsfalls.</li>
<li> &quot;<strong>input </strong>= text&quot;: kann mehrere Zeilen umfassen und wird mit der nächsten Instruktion abgeschlossen.</li>
<li> &quot;<strong>output </strong>= text&quot;: kann mehrere Zeilen umfassen und wird mit der nächsten Instruktion abgeschlossen. Ein Testfall kann mehrere richtige Ausgaben haben. Dazu gibt es drei verschiedene Arten: Zahlen, Text und exakter Text:
<ul>
<li> <strong>number</strong>: definiert als eine Folge von Zahlen (ganze und fließkomma). Es werden in der Ausgabe nur Zahlen beachten und anderer Text ignoriert. Fließkommazahlen besitzen eine gewisse Toleranz.</li>
<li> <strong>text</strong>: definiert als ein Text ohne Anführungszeichen. Es werden in der Ausgabe nur Wörter beachtet und die restlichen Zeichen ignoriert. Groß- und Kleinschreibung wird auch ignoriert.</li>
<li> <strong>exact text</strong>: definiert als ein Text mit Anführungszeichen. Jedes Zeichen der Ausgabe muss übereinstimmen.</li>
</ul>
</li>
<li> &quot;<strong>grade reduction</strong> = [Wert|Prozentsatz%]&quot; : Standardmäßig reduziert ein fehlgeschlagener Testfall die Punkte der Abgabe anteilsmäßig (Gesamtpunkte/Anzahl der Testfälle). Diese Anweisung ändert jedoch den Punkteabzug.</li>
</ul>
</p>';
$string['timelimited'] = 'Zeitlich begrenzt';
$string['timeunlimited'] = 'Zeitlich unbegrenzt';
$string['toggle_show_line_number'] = 'Zeilennummern anzeigen';
$string['totalnumberoferrors'] = "Fehler";
$string['undo'] = 'Rückgängung';
$string['undo_change'] = 'Änderung rückgängig machen';
$string['unzipping'] = 'Entpacke ...';
$string['uploadfile'] = 'Datei hochladen';
$string['usevariations'] = 'Verwende Variationen';
$string['variation_n'] = 'Variation {$a}';
$string['variation_n_i'] = 'Variation {$a->number}: {$a->identification}';
$string['variation_options'] = 'Variationsoptionen';
$string['variations'] = 'Variationen';
$string['variations_help'] = '<p>Eine Menge von Variationen können für eine Aktivität definiert werden, die dann zufällig Studenten zugewiesen werden.</p>
<p>Hier kann festgelegt werden, ob diese Aktivität Variationen hat und welche Bezeichnung die Menge trägt und es können die Variationen selbst hinzugefügt werden.</p>
<p>Jede Variation hat eine Identifizierungsnummer und eine Beschreibung. Die Identifizierungsnummer wird von der Datei <b>vpl_enviroment.sh</b> benutzt, um die Variation
jedes Studenten an die Skriptdatei weiterzugeben. Die Beschreibung, in HTML formatiert, wird für die jeweiligen Studenten angezeigt.</p>';
$string['variations_unused'] = 'Diese Aktivität hat Variationen, die deaktiviert sind';
$string['variationtitle'] = 'Variationsname';
$string['varidentification'] = 'Identifikation';
$string['visiblegrade'] = 'Sichtbar';
$string['vpl'] = 'Virtual Programming Lab';
$string['vpl:addinstance'] = 'Neue VPL Instanzen hinzufügen';
$string['vpl:grade'] = 'VPL Aufgabe bewerten';
$string['vpl:manage'] = 'VPL Aufgabe verwalten';
$string['vpl:setjails'] = 'Jail-Server speziellen VPL Instanzen zuweisen';
$string['vpl:similarity'] = 'VPL Aufgaben Ähnlichkeit prüfen';
$string['vpl:submit'] = 'VPL Aufgabe abgeben';
$string['vpl:view'] = 'Komplette VPL Aufgabenbeschreibung anzeigen';
$string['vpl_debug.sh'] = 'Dieses Skript bereitet das abgegebene Programm zum Debuggen vor';
$string['vpl_evaluate.cases'] = 'Hier werden die Testfälle zur Evaluierung des abgegebenen Programms angegeben';
$string['vpl_evaluate.sh'] = 'Dieses Skript evaluiert das abgegebene Programm';
$string['vpl_run.sh'] = 'Dieses Skript bereitet das abgegebene Programm zur Ausführung vor';
$string['workingperiods'] = 'Arbeitszeiten';
$string['worktype'] = 'Arbeitstyp';
