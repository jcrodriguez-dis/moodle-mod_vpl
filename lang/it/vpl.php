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
 * English to Italian translation, 10/2016
 * @author Andrea Pruccoli (Maggioli Informatica - http://www.maggioli.it/divisioni/maggioli-informatica/)
 */

$string['about'] = 'Informazioni su';
$string['acceptcertificates'] = 'Accetta certificati autofirmati';
$string['acceptcertificates_description'] = 'Se i server di esecuzione non stanno usando certificati autofirmati, disispunta questa opzione';
$string['acceptcertificatesnote'] = '<p>Stai usando una connessione criptata.</p>
<p>Per usare una connessione criptata con i server di esecuzione è richiesta l\'accettazione dei certificati.</p>
<p>Se riscontri problemi con questo processo, puoi provare ad utilizzare una connessione http (non criptata) o un altro browser.</p>
<p>Clicca sul link seguente (server #) e accetta i certificati proposti.</p>';
$string['addfile'] = 'Aggiungi file';
$string['advanced'] = 'Avanzate';
$string['allfiles'] = 'Tutti i file';
$string['allsubmissions'] = 'Tutte le consegne';
$string['always_use_ws'] = 'Usa sempre protocollo websocket (ws) non criptato';
$string['always_use_wss'] = 'Usa sempre protocollo websocket (ws) criptato';
$string['anyfile'] = 'Qualsiasi file';
$string['attemptnumber'] = 'Tentativo numero {$a}';
$string['automaticevaluation'] = 'Valutazione automatica';
$string['automaticgrading'] = 'Voto automatico';
$string['averageperiods'] = 'Periodo medio {$a}';
$string['averagetime'] = 'Tempo medio {$a}';
$string['basedon'] = 'Basato su';
$string['basic'] = 'Base';
$string['binaryfile'] = 'File binario';
$string['browserupdate'] = 'Aggiorna il tuo browser all\'ultima versione<br />o usane un altro che supporta Websocket.';
$string['calculate'] = 'Calcola';
$string['changesNotSaved'] = 'Le modifiche non sono state salvate';
$string['check_jail_servers'] = 'Controllo dei server di esecuzione';
$string['check_jail_servers_help'] = '<p>Questa pagina controlla e mostra lo stato deli server di esecuzione utilizzato per questa attività.</p>';
$string['Clipboard'] = 'Appunti';
$string['closed'] = 'Chiuso';
$string['comments'] = 'Commenti';
$string['compilation'] = 'Compilazione';
$string['connected'] = 'connesso';
$string['connecting'] = 'connessione';
$string['connection_closed'] = 'connessione chiusa';
$string['connection_fail'] = 'connessione fallita';
$string['copy'] = 'Copia';
$string['create_new_file'] = 'Crea un nuovo file';
$string['currentstatus'] = 'Stato corrente';
$string['cut'] = 'Taglia';
$string['datesubmitted'] = 'Data inviata';
$string['defaultexefilesize'] = 'Grandezza massima file di esecuzione di default';
$string['defaultexememory'] = 'Uso massimo memoria di default';
$string['defaultexeprocesses'] = 'Numero massimo di processi di default';
$string['defaultexetime'] = 'Tempo di esecuzione massimo di default';
$string['defaultfilesize'] = 'Grandezza massima del file di upload di default';
$string['defaultresourcelimits'] = 'Limiti delle risorse di esecuzione di default';
$string['delete'] = 'Elimina';
$string['delete_file_fq'] = 'eliminare il file \'{$a}\'?';
$string['delete_file_q'] = 'Eliminare file?';
$string['deleteallsubmissions'] = 'Elimina tutte le consegne';
$string['depends_on_https'] = 'Usa ws o wss a seconda che si usi http o https';
$string['description'] = 'Descrizione';
$string['discard_submission_period'] = 'Scarta periodo di consegna';
$string['discard_submission_period_description'] = 'Per ogni studente e compito, il sistema prova a scartare la consegna. Il sistema mantiene l\'ultimo e almeno una consegna per ogni periodo';
$string['download'] = 'Scarica';
$string['downloadsubmissions'] = 'Scarica tutte le consegne';
$string['duedate'] = 'Data di scadenza';
$string['edit'] = 'Modifica';
$string['editing'] = 'In modifica';
$string['evaluate'] = 'Valuta';
$string['evaluateonsubmission'] = 'Valuta alla consegna';
$string['evaluating'] = 'In valutazione';
$string['evaluation'] = 'Valutazione';
$string['examples'] = 'Esempi';
$string['execution'] = 'Esecuzione';
$string['executionfiles'] = 'File di esecuzione';
$string['executionfiles_help'] = '<h2>Introduzione</h2>
<p>Qui imposti i file che sono necessari per preparare l\'esecuzione, il debug o la valutazione di una consegna. Include file di scripting, file di dati e di test del programma.</p>
<h2>Script di default per esecuzione o debug</h2>
<p>Se non imposti file script per esecuzione o debug delle consegne, il sistema identificherà il linguaggio di interesse (sulla base delle estensioni del nome del file) e userà uno script predefinito.
<h2>Valutazione automatica</h2>
<p>Le funzionalità incorporate per facilitare la valutazione delle consegne dello studente. Questa funzionalità permette di eseguire il programma dello studente e controllarne l\'output per un dato input. Per impostare i casi di valutazione devi popolare il file &quot;vpl_evaluate.cases&quot;. <p>Il file "vpl_evaluate.cases" ha il seguente formato:
<ul>
<li>"<strong>case</strong>= Descrizione del caso": Opzionale. Imposta l\'inizio di una definizione di caso.</li>
<li>"<strong>input</strong>=text": può utilizzare più righe. Finisce con un\'altra istruzione.</li>
<li>"<strong>output</strong>=text": può usare più righe. Un caso può avere diversi output corretti. Ci sono tre tipologie di output: numerico, di testo e di testo esatto:
<ul>
<li><strong>numerico</strong>: definito come una sequenza di numeri (interi e decimali). Solo i numeri nell\'output sono controllati, altro testo è ignorato. I decimali sono controllati con una tolleranza</li>
<li><strong>testo</strong>: definito come testo senza doppi apici. Solo le parole sono controllate e gli altri caratteri sono ignorati, il confronto è case-insensitive </li>
<li><strong>testo esatto</strong>: definito come testo racchiuso fra doppi apici. Il confronto esatto è utilizzato per testare l\'output.</li>
</ul>
</li>
<li>"<strong>riduzione di voto</strong> = [value|percentage%]" : di default un errore riduce il voto dello studente (inizia dal massimo del voto) di (range_voto/numero di casi), ma con questa istruzione puoi cambiare la riduzione di voto o la percentuale.</li>
</ul>
</p>
<h2>Uso generale</h2>
<p>Un nuovo file può essere aggiunto scrivendo il suo nome nella box &quot;<b>Aggiungi file</b>&quot;.</p>
<p>Un file esistente può essere caricato tramite &quot;<b>Carica file</b>&quot;. <p>Tutti i file aggiunti e caricati possono essere modificati, e tutti quanti, tranne i tre file di scripting menzionati sotto, possono essere rinominati o eliminati.</p>
<h2>Elaborazione manuale, esecuzione o valutazione</h2>
<p>Tre file di scripting per determinare ognuna di queste azioni possono essere impostati.
Questi file hanno nomi predefiniti: <b>vpl_run.sh</b> (esecuzione), <b>vpl_debug.sh</b> (debug), <b>vpl_evaluate.sh</b> (correzione).</p>
<p>L\'esecuzione di ognuno di questi file dovrebbe generare la creazione di un file chiamato <b>vpl_execution</b>.
Questo file deve essere un eseguibile binario o uno script che inizia con &quot;#!/bin/sh &quot;.
La non generazione di questo file impedisce l\'esecuzione di queste azioni.</p>
<p>Se l\'attività che stai configurando è "basata su" un\'altra attività, i file dell\'attività di base sono aggiunti automaticamente. I contenuti dei file vpl_run.sh, vpl_debug.sh e vpl_evaluate.sh sono concatenati dal più basso livello di "basato su" fino a quello corrente.</p>
<p>Infine, il file <b>vpl_environment.sh</b> è automaticamente aggiunto.
Questo file di scripting contiene informazioni riguardo la consegna.
Le informazioni vengono trattate come variabili d\'ambiente:</p>
<ul><li>LANG: lingua usata.</li>
<li>LC_ALL: stesso valore di LANG.</li>
<li>VPL_MAXTIME: tempo massimo di esecuzione in secondi. </li>
<li> VPL_FILEBASEURL: URL per accedere ai file del corso. </li>
<li> VPL_SUBFILE#:  ogni nome dei file consegnati dallo studente. # varia da 0 al numero di file conseganti. </Li>
<li> VPL_SUBFILES: lista di tutti i file consegnati. </li>
<li> VPL_VARIATION + id:dove id è l\'ordine di variazione partendo da 0 e value è il valore della variazione. </li>
</ul>
Se l\'azione richiesta è la valutazione (evaluation), allora sono aggiunte anche le seguenti variabili.
<ul>
	<li>VPL_MAXTIME: massimo tempo di esecuzione in secondi.</li>
	<li>VPL_MAXMEMORY: memoria massima utilizzabile</li>
	<li>VPL_MAXFILESIZE: grandezza massima in byte del file che può essere creato.</li>
	<li>VPL_MAXPROCESSES: numero massimo di processi che possono essere eseguiti simultaneamente.</li>
	<Li>VPL_FILEBASEURL: URL ai file del corso.</Li>
	<li>VPL_GRADEMIN: voto minimo per questa attività</li>
	<li>VPL_GRADEMAX: voto massimo per questa attività</li>
</ul>
<h2>Risultato della correzione</h2>
<p>L\'output della valutazione è processato in modo da estrarre, se possibile, commenti e un possibile voto per la correzione. I commenti possono essere impostati in due modi: da una linea di commento definita da una linea che inizia con \'Comment :=&gt;&gt;\' o con blocco di commenti che iniziano con una linea contenente solo \'&lt;|--\' e che finisce con una linea contenente colo \'--|&gt;\'. Il voto è recuperato dall\'ultima linea che inizia con \'Grade :=&gt;&gt;\'.</p>';
$string['executionoptions'] = 'Opzioni di esecuzione';
$string['executionoptions_help'] = '<p>Varie opzioni di esecuzione sono modificabili in questa pagina</P>
<ul>
<li><b>Basato su</b>: imposta un\'altra istanza VPL dalla quale alcune funzionalità sono importate:
<ul><li>File di esecuzione (concatenando i file di scripting predefiniti)</li>
<li>Limiti per le risorse di esecuzione.</li>
<li> Variazioni, che sono concatenate per generare multivariazioni.</li>
<li>Lunghezza massima per ogni file da caricare con la consegna</li>
</ul>
</li>
<li></b>Esegui:</b>, <b>Debug</b> e <b>Valuta</b>: devono essere impostati su "Sì" se le corrispondenti azioni possono essere eseguite mentre si modifica la consegna. Questo influenza solo gli studenti, gli utenti con capacità di giudizio possono sempre eseguire queste azioni.</li>
<li><b>Valuta alla consegna</b>: la consegna è valutata automaticamente quando è consegnata.</li>
<li><b>Correzione automatica</b>: se il risultato della valutazione include regole di giudizio, queste vengono utilizzate per impostare il voto in modo automatico.</li>
</ul>';
$string['file_name'] = 'Nome file';
$string['fileadded'] = 'Il file \'{$a}\' è stato aggiunto';
$string['filedeleted'] = 'Il file \'{$a}\' è stato eliminato';
$string['filelist'] = 'Lista di file';
$string['filenotadded'] = 'Il file non è stato aggiunto';
$string['fileNotChanged'] = 'Il file non è stato modifcato';
$string['filenotdeleted'] = 'Il file \'{$a}\' NON è stato eliminato';
$string['filenotrenamed'] = 'Il file \'{$a}\' NON è stato rinominato';
$string['filerenamed'] = 'Il file \'{$a->from}\' è stato rinominato come \'{$a->to}\'';
$string['filesChangedNotSaved'] = 'I file sono stati modificati ma non sono stati salvati';
$string['filesNotChanged'] = 'I file non sono stati modificati';
$string['filestoscan'] = 'File da analizzare';
$string['fileupdated'] = 'Il file \'{$a}\' è stato aggiornato';
$string['find'] = 'Trova';
$string['find_replace'] = 'Trova/Sostituisci';
$string['fulldescription'] = 'Descrizione completa';
$string['fulldescription_help'] = '<p>Scrivi qui una descrizione completa dell\'attività.</p>
<p>Se non scrivi niente, viene mostrata la descrizione breve.</p>
<p>Se vuoi che la valutazione sia automatica, le interfacce per i compiti devono essere dettagliate e non ambigue.</p>';
$string['fullscreen'] = 'Schermo intero';
$string['getjails'] = 'Recupera i server di esecuzione';
$string['gradeandnext'] = 'Correggi e avanza';
$string['graded'] = 'Corretto';
$string['gradedbyuser'] = 'Corretto dall\'utente';
$string['gradedon'] = 'Valutato il';
$string['gradedonby'] = 'Revisionato il {$a->date} da {$a->gradername}';
$string['gradenotremoved'] = 'Il voto NON è stato rimosso. Controlla la configurazione dell\'attività nel registro dei voti.';
$string['gradenotsaved'] = 'Il voto NON è stato salvato. Controlla la configurazione dell\'attività nel registro dei voti.';
$string['gradeoptions'] = 'Opzioni di correzione';
$string['grader'] = 'Valutatore';
$string['gradercomments'] = 'Report delle valutazioni';
$string['graderemoved'] = 'Il voto è stato rimosso';
$string['groupwork'] = 'Lavoro di gruppo';
$string['inconsistentgroup'] = 'Non sei membro di solo un gruppo (0 o >1)';
$string['incorrect_file_name'] = 'Nome del file incorretto';
$string['individualwork'] = 'Lavoro individuale';
$string['instanceselection'] = 'Selezione VPL';
$string['isexample'] = 'Questa attività funge da esempio';
$string['jail_servers'] = 'Lista dei server di esecuzione';
$string['jail_servers_config'] = 'Configurazione dei server di esecuzione';
$string['jail_servers_description'] = 'Scrivi una linea per ogni server';
$string['joinedfiles'] = 'File selezionati uniti';
$string['keepfiles'] = 'File da mantenere durante l\'esecuzione';
$string['keepfiles_help'] = '<p>Per problemi riguardanti la sicurezza, i file aggiunti come &quot;File di esecuzione&quot; sono eliminati prima dell\'esecuzione del file vpl_execution.</p>
Se qualcuno di questi file è richiesto durante l\'esecuzione (per esempio, per essere utilizzato come test di dati), deve essere segnato qui.';
$string['keyboard'] = 'Tastiera';
$string['lasterror'] = 'Info ultimo errore';
$string['lasterrordate'] = 'Data ultimo errore';
$string['listofcomments'] = 'Lista dei commenti';
$string['listsimilarity'] = 'Lista di somiglianze trovate';
$string['listwatermarks'] = 'Lista dei watermark';
$string['load'] = 'Carica';
$string['loading'] = 'Caricamento';
$string['local_jail_servers'] = 'Server di esecuzione locali';
$string['local_jail_servers_help'] = '<p>Qui puoi impostare i server di esecuzione locali aggiunti per questa attività e quelli che si basano su essa.</p>
<p>Inserisci URL completo per ogni server su ogni linea. Puoi utilizzare righe vuote e commenti che incominciano con "#".</p>
<p>Questa attività utilizzerà come lista di server di esecuzione: i server impostati qui più la lista di server impostata nell\'attività "basata su" più la lista di server di esecuzione comuni. Se vuoi prevenire che questa attività e quelle derivate da essa usino altri server, allora devi aggiungere una linea contente "end_of jails" alla fine della lista dei server.</p>';
$string['manualgrading'] = 'Votazione manuale';
$string['maxexefilesize'] = 'Grandezza massima file di esecuzione';
$string['maxexememory'] = 'Memoria massima utilizzata';
$string['maxexeprocesses'] = 'Numero massimo di processi';
$string['maxexetime'] = 'Tempo di esecuzione massimo';
$string['maxfiles'] = 'Numero massimo di file';
$string['maxfilesexceeded'] = 'Numero massimo di file di eccesso';
$string['maxfilesize'] = 'Grandezza massima file caricato';
$string['maxfilesizeexceeded'] = 'Grandezza massima di eccesso file';
$string['maximumperiod'] = 'Periodo massimo {$a}';
$string['maxresourcelimits'] = 'Limiti massimi risorse di esecuzione';
$string['maxsimilarityoutput'] = 'Output massimo per similarità';
$string['menucheck_jail_servers'] = 'Controllo server di esecuzione';
$string['menuexecutionfiles'] = 'File di esecuzione';
$string['menuexecutionoptions'] = 'Opzioni';
$string['menukeepfiles'] = 'File da mantenere';
$string['menulocal_jail_servers'] = 'Server di esecuzione locali';
$string['menuresourcelimits'] = 'Limiti di risorse';
$string['minsimlevel'] = 'Livello di similarità minimo da mostrare';
$string['moduleconfigtitle'] = 'Configurazione di modulo VPL';
$string['modulename_help'] = '<p>VPL è un modulo di attività per Moodle che gestisce compiti di programmazione e le cui funzionalità salienti sono: </p>
<ul>
<li>Possibilità di modificare il codice sorgete dei programmi dal browser utilizzando una applet</li>
<li>Gli studenti possono eseguire in modo interettivo dal browser</li>
<li>Puoi eseguire test per controllare i programmi.</li>
<li>Permette la ricerca per similarità fra file.</li>
<li>Permette limpostazione di restrizioni alla modifica e impedisce/evita il copia/incolla del testo da fonti esterne.</li>
</ul>
<p><b>Definizione base di un\'attività di Virtual Programming Lab</b></p>
<p>Per accedere ad altre opzioni, una definizione base <b>deve inizialmente essere salvata</b>.</p>
<p>Limiti e vincoli per la consegna possono essere impostati nel pannello <b>Restrizioni sulla consegna</b>:</p>
<ul>
<li>Numero massimo di file da consegnare. Puoi impostare i nomi dei file nella scheda "file richiesti".</li>
<li>Consegna disponibile solo tramite i mezzi/strumenti del Code Editor. Se imposti questa opzione, non puoi caricare file o incollare testo dall\'esterno del code editor.</li>
<li>Grandezza massima file caricato</li>
<li>Password per accedere e inviare/presentare l\'attività. Se imposti questa opzione il sistema richiederà la password per accedere all\'attività.</li>
<li>Reti dalle quali la consegna è permessa.</li>
</ul>
<p>Anche opzioni comuni, come la scala di voto e i gruppi, possono essere impostati in questa pagina.</p>';
$string['new'] = 'Nuovo';
$string['new_file_name'] = 'Nuovo nome file';
$string['next'] = 'Successivo';
$string['nojailavailable'] = 'Nessun server di esecuzione disponibile';
$string['noright'] = 'Non hai diritti di accesso';
$string['nosubmission'] = 'Nessuna consegna disponibile';
$string['notexecuted'] = 'Non eseguito';
$string['notgraded'] = 'Non graduato';
$string['notsaved'] = 'Non salvato';
$string['novpls'] = 'Nessun laboratorio di programmazione virtuale definito';
$string['nowatermark'] = 'Propri watermark {$a}';
$string['nsubmissions'] = '{$a} consegne';
$string['numcluster'] = 'Gruppo {$a}';
$string['open'] = 'Apri';
$string['opnotallowfromclient'] = 'Azione non consentita da questa macchina';
$string['options'] = 'Opzioni';
$string['optionsnotsaved'] = 'Le opzioni non sono state salvate';
$string['optionssaved'] = 'Le opzioni sono state salvate';
$string['origin'] = 'Origine';
$string['othersources'] = 'Altre fonti da aggiungere alla scansione';
$string['outofmemory'] = 'Eccesso di memoria';
$string['paste'] = 'Incolla';
$string['pluginadministration'] = 'Amministrazione VPL';
$string['previoussubmissionslist'] = 'Lista consegne precedente';
$string['print'] = 'Stampa';
$string['proposedgrade'] = 'Voto proposto: {$a}';
$string['proxy_description'] = 'Proxy da Moodle ai server di esecuzione';
$string['redo'] = 'Ripeti';
$string['regularscreen'] = 'Schermo regolare';
$string['removegrade'] = 'Rimuovi voto';
$string['rename'] = 'Rinomina';
$string['rename_file'] = 'Rinomina file';
$string['replace_find'] = 'Sostituisci\\Trova';
$string['requestedfiles'] = 'File richiesti';
$string['requestedfiles_help'] = '<p>Qui imposti i nomi e il contenuto iniziale per i file richiesti per un numero di file fino al numero massimo che è stato impostato nella descrizione base dell\'attività.</p>
<p>Se non imposti i nomi per tutti i file, i file senza nome sono opzionali e possono avere qualsiasi nome.</p>
<p>Puoi anche aggiungere del contenuto ai file richiesti, così questi contenuti saranno disponibili la prima volta che gli studenti apriranno l\'editor, se non esistono precedenti consegne.</p>';
$string['requirednet'] = 'Cosegna permessa dalla rete';
$string['requiredpassword'] = 'Una password è richiesta';
$string['resetfiles'] = 'Resetta file';
$string['resetvpl'] = 'Resetta {$a}';
$string['resourcelimits'] = 'Limiti delle risorse';
$string['resourcelimits_help'] = '<p>Puoi impostare limiti per il tempo di esecuzione, per la memoria usata, per le dimensioni dei file di esecuzione e per il numero di processi che possono essere eseguiti simultaneamente.</p>
<p>Questi limiti sono utilizzati quando si eseguono i file di scripting vpl_run.sh, vpl_debug.sh e vpl_evaluate.sh e il file vpl_execution costruito da essi.</p>
<p>Se questa attivirà è basata su altre attività, i limiti possono essere influenzati da quelli impostati nell\'attività base e i suoi antenati o nella configurazione globale del modulo.</p>';
$string['restrictededitor'] = 'Disabilita caricamento di file esterno, incolla e drop di contenuto esterno';
$string['retrieve'] = 'Recupera risultati';
$string['run'] = 'Esegui';
$string['running'] = 'In esecuzione';
$string['save'] = 'Salva';
$string['savecontinue'] = 'Salva e continua';
$string['saved'] = 'Salvato';
$string['savedfile'] = 'Il file \'{$a}\' è stato salvato';
$string['saveoptions'] = 'salva opzioni';
$string['saving'] = 'Salvataggio';
$string['scanactivity'] = 'Attività';
$string['scandirectory'] = 'Cartella';
$string['scanningdir'] = 'Cartella in scansione ...';
$string['scanoptions'] = 'Opzioni di scansione';
$string['scanother'] = 'Similarità di scansione in risorse aggiunte';
$string['select_all'] = 'Seleziona tutto';
$string['serverexecutionerror'] = 'Errore server di esecuzione';
$string['shortcuts'] = 'Scorciatoie da tastiera';
$string['shortdescription'] = 'Descrizione corta';
$string['similarity'] = 'Similarità';
$string['similarto'] = 'Similare a';
$string['startdate'] = 'Disponibile dal';
$string['submission'] = 'Consegna';
$string['submissionperiod'] = 'Periodo di consegna';
$string['submissionrestrictions'] = 'Restrizioni sulla consegna';
$string['submissions'] = 'Consegne';
$string['submissionselection'] = 'Selezione consegna';
$string['submissionslist'] = 'Lista consegne';
$string['submissionview'] = 'Vista consegna';
$string['submittedby'] = 'Consegnato da {$a}';
$string['submittedon'] = 'Consegnato il';
$string['submittedonp'] = 'Consegnato il {$a}';
$string['sureresetfiles'] = 'Vuoi perdere tutto il tuo lavoro e resettare i file al loro stato originale?';
$string['test'] = 'Attività di test';
$string['testcases'] = 'Casi di test';
$string['testcases_help'] = '<p>Questa funzionalità permette di eseguire il programma dello studente e di controllare l\'output per un dato input. Per impostare i casi di valutazione devi popolare il file &quot;vpl_evaluate.cases&quot;.</p>
<p>Il file "vpl_evaluate.cases" ha il seguente formato:
<ul>
<li>"<strong>case </strong>= Descrizione del caso": Opzionale. Imposta un\'inizio alla definizione del caso di test.</li>
<li>"<strong>input </strong> = testo": può utilizzare più righe. Finisce con un\'altra istruzione.</li>
<li>"<strong>output </strong> = testo": può utilizzare più righe. Finisce con un\'altra istruzione. Un caso può avere diversi output corretti. Ci sono tre tipi di output: numeri, testo e testo esatto:
<ul>
<li><strong>numeri</strong>: definito come una sequenza di numeri (interi e decimali). Solo i numeri nell\'output sono controllati, altro testo è ignorato. I decimali sono controllati con una tolleranza</li>
<li><li> <strong>testo</strong>: definito come testo senza doppi apici. Solo le parole sono controllate e gli altri caratteri sono ignorati, il confronto è case-insensitive</li>
<li><strong>testo esatto</strong>: definito come testo racchiuso tra doppi apici. Il confronto esatto è utilizzato per controllare l\'output.</li>
</ul>
</li>
<li>"<strong>grade reduction</strong> = [valore|percentuale%]" : Di default un errore riduce il voto dello studente (inizialmente impostato al voto massimo) di (range di voto/numero di casi), ma con questa istruzione puoi cambiare il valore o la percentuale della reduzione.</li>
</ul>';
$string['timeleft'] = 'Tempo rimasto';
$string['timelimited'] = 'Tempo limitato';
$string['timeunlimited'] = 'Tempo illimitato';
$string['totalnumberoferrors'] = 'Errori';
$string['undo'] = 'Annulla';
$string['unexpected_file_name'] = 'Nome file non corretto: atteso \'{$a->expected}\' e trovato \'{$a->found}\'';
$string['unzipping'] = 'Estrazione ...';
$string['uploadfile'] = 'Carica file';
$string['usevariations'] = 'Usa variazioni';
$string['usewatermarks'] = 'Usa watermark';
$string['usewatermarks_description'] = 'Aggiungi watermark ai file degli studenti (solo per le lingue supportate)';
$string['variation'] = 'Variazioni {$a}';
$string['variation_options'] = 'Opzioni di variazione';
$string['variations'] = 'Variazioni';
$string['variations_help'] = '<p>Un insieme di variazioni può essere definito per un\'attività. Queste variazioni sono assegnate in modo casuale agli studenti</p>
<p>Qui puoi indicare se l\'attività prevede variazioni, porre un titolo per l\'insieme di variazioni, e aggiungere le variazioni desiderate.</p>
<p>Ogni variazione ha un codice identificativo e una descrizione. Il codice identificativo è usato dal file <b>vpl_environment.sh</b> per passare la variazione assegnata ad ogni studente ai file script. La descrizione, formattato in HTML, è mostrata agli studenti che hanno assegnato la variazione corrispondente.</p>';
$string['variations_unused'] = 'L\'attività ha variazioni, ma sono disabilitati';
$string['variationtitle'] = 'Titolo variazione';
$string['varidentification'] = 'Identificazione';
$string['visiblegrade'] = 'Visibile';
$string['VPL_COMPILATIONFAILED'] = 'La compilazione o la preparazione della esecuzione ha fallito';
$string['vpl_debug.sh'] = 'Questi script preparano il debugging';
$string['vpl_evaluate.cases'] = 'Casi di test per la valutazione';
$string['vpl_evaluate.sh'] = 'Questo script prepara la valutazione';
$string['vpl_run.sh'] = 'Questo script prepara l\'esecuzione';
$string['vpl:addinstance'] = 'Aggiungi nuove istanze VPL';
$string['vpl:grade'] = 'Correggi compito VPL';
$string['vpl:manage'] = 'Gestisci compito VPL';
$string['vpl:setjails'] = 'Imposta i server di esecuzione per particolari istanze VPL';
$string['vpl:similarity'] = 'Cerca similarità compito VPL';
$string['vpl:submit'] = 'Consegna compito VPL';
$string['vpl:view'] = 'Visualizza la descrizione completa del compito VPL';
$string['websocket_protocol'] = 'Protoccolo WebSocket';
$string['websocket_protocol_description'] = 'Tipo di protocollo WebSocket (ws:// o wss://) usato dal browser per connettere i server di esecuzione.';
$string['workingperiods'] = 'Periodi di lavoro';
$string['worktype'] = 'Tipo di lavoro';
