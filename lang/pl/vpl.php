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
 * English to Polish translation, 02/02/2011
 *
 * @author Marcin Kolodziej
 * @author Michal Kedzia
 * @copyright 2011 Marcin Kolodziej and Michal Kedzia
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @var array $string
 * @package mod_vpl
 */

$string['about'] = 'O';
$string['addfile'] = 'Dodaj plik';
$string['advanced'] = 'Zaawansowany';
$string['allfiles'] = 'Wszystkie pliki';
$string['allsubmissions'] = 'Wszystkie złożenia';
$string['anyfile'] = 'Dowolny plik';
$string['attemptnumber'] = 'Próba numeru {$a}';
$string['automaticevaluation'] = 'Automatyczne ocenianie';
$string['automaticgrading'] = 'Automatyczna ocena';
$string['basedon'] = 'W oparciu o';
$string['basic'] = 'Podstawy';
$string['calculate'] = 'przelicz';
$string['case_sensitive'] = 'Uwzględnij wielkość znaków';
$string['changesNotSaved'] = 'Zmiany nie zostały zapisane';
$string['check_jail_servers'] = 'Check jail servers';
$string['closed'] = 'Zamknięty';
$string['comments'] = 'Komentarze';
$string['compilation'] = 'Kompilacja';
$string['connected'] = 'połączono';
$string['connecting'] = 'łączenie';
$string['connection_closed'] = 'zamknięto połączenie';
$string['connection_fail'] = 'błąd połączenia';
$string['console'] = 'Konsola';
$string['contextual_help'] = 'Pomoc kontekstowa';
$string['copy'] = 'Kopiuj';
$string['copy_text'] = 'Kopiuj zaznaczony tekst do schowka';
$string['create_new_file'] = 'Utwórz nowy plik';
$string['currentstatus'] = 'Current status';
$string['cut'] = 'Wytnij';
$string['cut_text'] = 'Wytnij zaznaczony tekst do schowka';
$string['datesubmitted'] = 'Data dodana';
$string['debug'] = 'Debuguj';
$string['debugging'] = 'Debugowanie';
$string['delete'] = 'Usuń';
$string['delete_file_fq'] = "usunąć plik '{\$a}'?";
$string['delete_file_q'] = 'Usunąć plik?';
$string['deleteallsubmissions'] = 'Usuń wszystkie złożenia';
$string['description'] = 'Opis';
$string['diff'] = 'diff';
$string['discard_submission_period'] = 'Odrzuć okres wysyłania';
$string['discard_submission_period_description'] = 'Dla każdego studenta i każdego przypisania system próbuje odrzucić wysyłanie. System utrzymuje ostatnie i przynajmniej jedno dla każdego okresu';
$string['download'] = 'Pobierz';
$string['duedate'] = 'Do';
$string['edit'] = 'Edycja';
$string['editing'] = 'Edycja';
$string['evaluate'] = 'Sprawdź';
$string['evaluateonsubmission'] = 'Sprawdzenie poprzez złożenie';
$string['evaluating'] = 'Sprawdzanie';
$string['evaluation'] = 'Sprawdzanie';
$string['examples'] = 'Przykłady';
$string['execution'] = 'Wykonanie';
$string['executionfiles'] = 'Pliki wykonywalne';
$string['executionfiles_help'] = '
<h1>Pliki wykonywalne</h1>
<h1>VPL wersji 1.3</h1>
<p>Tutaj ustawia się pliki, które są niezbędne do przygotowania wykonania, debugowania lub oceny składni. Dotyczy to również skryptów, plików testowych programu i plików danych.</p><p>
Nowy plik może być dodany poprzez podanie jego nazwy w polu tekstowym  &quot;<b>Dodaj plik</b>&quot; a następnie kliknięcie przycisku &quot;<b>Dodaj plik</b>&quot;.
<p>Istniejący plik może być zaimportowany poprzez kliknięcie przycisku &quot;<b>Importuj plik</b>&quot;.<p>Wszystkie dodane lub przesłane pliki mogą być edytowane, a wszystkie z nich, z wyjątkiem trzech plików skryptów wymienionych poniżej, mogą zostać zmienione lub usunięte.
</p>
Do przygotowania każdej akcji muszą być ustawione trzy pliki skryptów. Pliki te mają predefiniowane nazwy:
 <b>vpl_run.sh</b> (wykonanie), <b>vpl_debug.sh</b>  (debugowanie) and <b>vpl_evaluate.sh</b> (ocena).
<p>Wykonanie każdego z tych skryptów powinno generować plik o nazwie<b>vpl_execution</b>. Ten plik musi być wykonywalnym plikiem binarnym lub skryptem zaczynającym się od &quot;#!/ bin / sh &quot;. Nie wygenerowanie tego pliku utrudnia wykonanie wybranej akcji.</p>

<p>Jeśli konfigurowana akcja jest oparta na innej akcji, pliki bazowej akcji są dodawane automatycznie.
Zawartość plików vpl_run.sh, vpl_debug.sh and vpl_evaluate.sh jest łączona w kolejności od bazowego do obecnego.</P>
<p>Ostatecznie plik <b>vpl_environment.sh</b> jest dodawany automatycznie. Ten plik skryptowy zawiera informacje o składni. Informacja zawarta jest w zmiennych środowiskowych: </p>
<ul> <li> LANG:  używany język. </li>
<li> LC_ALL: ta sama wartość co w LANG. </li>
<li> VPL_MAXTIME: maksymalny czas wykonania w sekundach. </li>
<li> VPL_FILEBASEURL: URL do plików kursu. </li>
<li> VPL_SUBFILE#:  każda nazwa pliku podana przez studenta. # Zakres od 0 do ilości podanych plików. </Li>
<li> VPL_SUBFILES: lista wszytkich podanych plików. </li>
<li> VPL_VARIATION + id: gdzie id jest kolejnością działań, zaczynając od 0, a wartość zmiennej jest wartością działania. </li>
</ul>
<h2>Wyniki</h2>
<p>Wyjściowa ocena jest przetwarzana, jeśli to możliwe, dodawane są komentarze oraz proponowana ocena. Komentarze mogą być dodawane
na dwa sposoby: poprzez linię komentarzy zaczynającą się od \'Comment :=&gt;&gt;\' lub
lub jako bloki komentarzy zaczynające się linią zawierającą jedynie \'&lt;|--\' i kończące się linią zawierającą tylko \'--|&gt;\'.
Ocena znajduje się w ostatniej linii zawierającej \'Grade :=&gt;&gt;\'.
</p>
';
$string['executionoptions'] = 'Opcje wykonania';
$string['executionoptions_help'] = '
<h1>Opcje wykonania</h1>
<p>Na tej stronie ustawiane są róne opcje wykonania.</p>
<ul>
<li><b>W oparciu o</b>: ustawienie innej instancji VPL z której zaimportowane są następujące cechy:
<ul><li>Pliki wykonywalne (łączenie predefiniowanych plików skryptowych).</li>
<li>Limity dla zasobów wykonania.</li>
<li>Wariacje, które są łączone w celu wykonywania multiwariacji.</li>
<li>Maksymalna długość każdego importowanego pliku ze składnią</li>
</ul>
</li>
<li><b>Wykonaj</b>, <b>Debuguj</b> i <b>Sprawdź</b>: muszą być ustawione na "Tak" jeżeli korespondująca akcja może być wykonana podczas edycji składni. To wpływa jedynie na studentów, użytkowników z możliwością oceny przy każdym wykonaniu akcji.</li>
<li><b>Sprawdzenie samej składni</b>: składnia jest sprawdzana automatycznie po zaimportowaniu.</li>
<li><b>Automatyczna ocena</b>: jeżli rezultat sprawdzenia zawiera kody oceny, używane są one do automatycznego oceniania.</li>
</ul>
';
$string['figure'] = 'Image';
$string['file'] = 'Plik';
$string['fileNotChanged'] = 'Plik nie został zmieniony';
$string['file_name'] = 'Nazwa pliku';
$string['fileadded'] = "Plik '{\$a}' został dodany";
$string['filedeleted'] = "Plik '{\$a}' został usunięty";
$string['filenotadded'] = 'Plik nie został dodany';
$string['filenotdeleted'] = 'Plik \'{$a}\' nie został usunięty';
$string['filenotrenamed'] = 'Nazwa pliku \'{$a}\' nie została zmieniona';
$string['filerenamed'] = "Nazwa pliku '{\$a->from}' została zmieniona na '{\$a->to}'";
$string['filesChangedNotSaved'] = 'Pliki zostały zmienione, ale nie zostały zapisane';
$string['filesNotChanged'] = 'Pliki nie zostały zmienione';
$string['filestoscan'] = 'Pliki do skanowania';
$string['fileupdated'] = "Plik '{\$a}' zotał zaktualizowany";
$string['find'] = 'Znajdź';
$string['find_find_replace'] = 'Znajdź lub Znajdź i zamień';
$string['find_next_search_string'] = 'Znajdź następny szukany łańcuch w tekście';
$string['find_replace'] = 'Znajdź/Zamień';
$string['font_size'] = 'Rozmiar czcionki';
$string['fulldescription'] = 'Pełny opis';
$string['fulldescription_help'] = '
<h1>Pełny opis</h1>
<p>Musisz tutaj napisać pełny opis czynności.</p>
<p>Jeśli nic tutaj nie napiszesz, w zamian pokaże się krótki opis.</p>
<p>Jeśli chcesz sprawdzić automatycznie, interfejsy dla przypisań muszą być dokładne i jednoznaczne.</p>
';
$string['fullscreen'] = 'Pełny ekran';
$string['general_help'] = 'Ogólna pomoc językowa';
$string['go_next_page'] = 'Idź do następnej strony';
$string['gradeandnext'] = 'Oceń i przejdź dalej';
$string['graded'] = 'Oceniony';
$string['gradedbyuser'] = 'Ocenione przez użytkownika';
$string['gradedon'] = "Oceniony";
$string['gradedonby'] = "Przeglądane dnia {\$a->date} przez {\$a->gradername}";
$string['gradenotremoved'] = 'Ocena nie została usunięta. Sprawdż konfigurację czynności w dzienniku ocen.';
$string['gradenotsaved'] = 'Ocena nie została zapisana. Sprawdź konfigurację czynności w dzienniku ocen.';
$string['gradeoptions'] = 'Opcje oceniania';
$string['grader'] = "Oceniający";
$string['gradercomments'] = 'Komentarze oceniającego';
$string['graderemoved'] = 'Ocena została usunięta';
$string['help'] = 'Pomoc';
$string['help_about'] = 'Pomoc na temat';
$string['inconsistentgroup'] = 'Nie jesteś członkiem tylko jednej grupy (0 o> 1)';
$string['incorrect_file_name'] = 'Niepoprawna nazwa pliku';
$string['index_help'] = '
<h1>Wirtualne laboratorium programowania</h1>
<ul>
  <li><a href="help.php?module=vpl&amp;file=mods.html">Podstawy</a></li>
  <li><a href="help.php?module=vpl&amp;file=fulldescription.html">Pełny opis</a></li>
  <li><a href="help.php?module=vpl&amp;file=variations.html">Wariacje</a></li>
  <li><a href="help.php?module=vpl&amp;file=requestedfiles.html">Żądane pliki</a></li>
  <li>Wykonanie
  <ul>
  <li><a href="help.php?module=vpl&amp;file=executionfiles.html">Pliki wykonywalne</a></li>
  <li><a href="help.php?module=vpl&amp;file=executionoptions.html">Opcje wykonania</a></li>
  <li><a href="help.php?module=vpl&amp;file=resourcelimits.html">Limity zasobów wykonania</a></li>
  <li><a href="help.php?module=vpl&amp;file=keepfiles.html">Pliki używane podczas wykonania</a></li>
  </ul>
  </li>
</ul>
';
$string['instanceselection'] = 'Wybór VPL';
$string['isexample'] = 'Ćwiczenie przykładowe';
$string['jail_servers'] = 'Lista Jail serwerów';
$string['jail_servers_config'] = 'Konfiguracja Jail serwerów';
$string['jail_servers_description'] = 'Napisz linię dla każdego serwera';
$string['joinedfiles'] = 'Dołączone wybrane pliki';
$string['keepfiles'] = 'Pliki używane podczas wykonania';
$string['keepfiles_help'] = '
<h1>Pliki używane podczas wykonania</h1>
<p>W związku z kwestią bezpieczeństwa, wszystkie pliki dodane jako &quot;Pliki wykonywalne&quot;  vpl_execution.</p>
Jeżeli któryś z plików jest wymagany podczas wykonania (na przykład jako dane testowe), musi być to tutaj zaznaczone.
';
$string['language_help'] = 'Pomoc językowa';
$string['lasterror'] = 'Last error info';
$string['lasterrordate'] = 'Last error date';
$string['line_number'] = 'Numer linii';
$string['listofcomments'] = 'Lista komentarzy';
$string['listsimilarity'] = 'Lista podobieństw znaleziona';
$string['listwatermarks'] = 'Lista znaków wodnych';
$string['local_jail_servers'] = 'Lokalne jail serwery';
$string['local_jail_servers_help'] = '
<h1>Lokalne jail serwery</h1>
<p>Dla tej czynności możesz tutaj ustawić lokalny jail serwer i te, które są na nim oparte.</p>
<p>W każdej linii wpisz pełny URL serwera. Możesz używać pustych linii i komentarzy zaczynających się od "#".</p>
<p>Jeśli nie chcesz używać większej ilości jail serwerów oraz serwerów pochodnych, na końcu listy serwerów dodaj linię "end_of_jails".
</p>
';
$string['manualgrading'] = 'Ręczne ocenianie';
$string['maxexefilesize'] = 'Maksymalny rozmiar wykonywalnych plików';
$string['maxexememory'] = 'Maksymalna zużyta pamięć';
$string['maxexeprocesses'] = 'Maksymalna liczba procesów';
$string['maxexetime'] = 'Maksymalny czas wykonania';
$string['maxfiles'] = 'Maksymalna liczba plików';
$string['maxfilesexceeded'] = 'Maksymalna liczba plików przekroczona';
$string['maxfilesize'] = 'Maksymalny rozmiar wysyłanego pliku';
$string['maxfilesizeexceeded'] = 'Maksymalny rozmiar pliku przekroczony';
$string['maxsimilarityoutput'] = 'Maksymalne wyjście przez podobieństwo';
$string['menucheck_jail_servers'] = 'Check jails';
$string['menuexecutionfiles'] = 'Pliki wykonywalne';
$string['menuexecutionoptions'] = 'Opcje';
$string['menukeepfiles'] = 'Pliki używane';
$string['menulocal_jail_servers'] = 'Lokalne jail';
$string['menuresourcelimits'] = 'Limity zasobów';
$string['minsimlevel'] = 'Minimalny poziom podobieństwa';
$string['moduleconfigtitle'] = 'Moduł konfiguracji VPL';
$string['modulename'] = 'Wirtualne Laboratorium Programowania';
$string['modulename_help'] = '
<p><img alt="VPL" src="<?php echo $CFG->wwwroot?>/mod/vpl/icon.gif" />&nbsp;
<b>VPL. Wirtualnego Laboratorium Programowania</b></p>
<p><b>Podstawowy opis obsługi</b></p>
<p>Aby uzyskać dostęp do innych opcji, muszą być ustawione opcje podstawowe.</p>
<p>Ograniczenia dla wysyłania mogą być ustawione w panelu <b>Ograniczenia wysyłania</b>:</p>
<ul>
<li>Maksymalna liczba plików do wysłania.</li>
<li>Wysyłanie dostępne tylko poprzez zastrzeżony edytor.</li>
<li>Maksymalny rozmiar wysyłanych plików.</li>
<li>Hasło do uzyskania dostępu oraz możliwości wysyłania plików.</li>
<li>Sieci, z których wysyłanie jest dozwolone.</li>
</ul>
<p><a href="http://vpl.dis.ulpgc.es">Virtual Programming lab</a></p>';
$string['modulename_link'] = 'mod/vpl/view';
$string['modulenameplural'] = 'Wirtualne Laboratoria Programowania';
$string['new'] = 'Nowy';
$string['new_file_name'] = 'Nowa nazwa pliku';
$string['next'] = 'Następny';
$string['next_page'] = 'Następna strona';
$string['nojailavailable'] = 'Brak dostępnego Jail Serwera';
$string['noright'] = 'Nie masz prawa dostępu';
$string['nosubmission'] = 'Składanie niedostępne';
$string['notexecuted'] = 'Nie wykonane';
$string['notgraded'] = 'Nieoceniony';
$string['notsaved'] = 'Nie zapisano';
$string['novpls'] = 'Brak zdefiniowanego laboratorium';
$string['nowatermark'] = 'Własny znak wodny {$a}';
$string['nsubmissions'] = '{$a} złożono';
$string['numcluster'] = 'Klaster {$a}';
$string['open'] = 'Otwarty';
$string['opnotallowfromclient'] = 'Akcja niedozwolona z tej maszyny';
$string['options'] = 'Opcje';
$string['optionsnotsaved'] = 'Opcje nie zostały zapisane';
$string['optionssaved'] = 'Opcje zostały zapisane';
$string['origin'] = 'Pochodzenie';
$string['othersources'] = 'Inne źródła do skanowania';
$string['page_unaccessible'] = 'Strona niedostępna';
$string['paste'] = 'Wklej';
$string['paste_text'] = 'Wklej tekst ze schowka';
$string['pluginname'] = 'Wirtualne Laboratorium Programowania';
$string['previous_page'] = 'Poprzednia strona';
$string['previoussubmissionslist'] = 'Poprzednia lista składania';
$string['program_help'] = 'Pomoc programu';
$string['proposedgrade'] = 'Proponowana ocena: {$a}';
$string['redo'] = 'Ponów';
$string['redo_undone'] = 'Ponów poprzednio wprowadzone zmiany';
$string['regularscreen'] = 'Normalny ekran';
$string['removegrade'] = 'Usuń ocenę';
$string['rename'] = 'Zmień nazwę';
$string['renameFile'] = 'Zmień nazwę pliku';
$string['replace'] = 'Zamień';
$string['replace_all'] = 'Zamień wszystko';
$string['replace_all_next'] = 'Zamień wszystkie następne szukane łańcuchy';
$string['replace_find'] = 'Zamień/Znajdź';
$string['replace_find_next'] = 'Zamień i znajdź następny łańcuch';
$string['replace_selection_if_match'] = 'Zamień zaznaczone jeśli pasuje do wyszukiwanego łańcucha';
$string['requestedfiles'] = 'Żądane pliki';
$string['requestedfiles_help'] = '
<h1>Żądane pliki</h1>
<p>Tutaj ustawia się nazwy żądanych plików do maksymalnego numeru pliku, który został określony w podstawowych wymaganiach ćwiczenia.</p>
<p>Jeśli nie ustawisz nazw dla wszystkich plików, nienazwane pliki opcjonalnie mogą mieć jakąś nazwę.</p>
<p>Możesz także dodać zawartość żądanych plików, zawartość ta będzie dostępna za pierwszym razem, gdy zostaną one otwarte w edytorze, jeśli
nie istnieją poprzednie wpisy.</p>
';
$string['requirednet'] = 'Dozwolone wysyłanie z sieci';
$string['requiredpassword'] = 'Potrzebne hasło';
$string['resetfiles'] = 'Zresetuj pliki';
$string['resetvpl'] = 'Resetuj {$a}';
$string['resourcelimits'] = 'Limity zasobów wykonania';
$string['resourcelimits_help'] = '
<h1>Limity zasobów wykonania</h1>
<p>Maksymalny limit może być ustawiony dla czasu wykonania, użytej pamięci, rozmiaru wykonywalnych plików i liczby procesów wykonywanych równocześnie.</p>
<p>Limity te są używane podczas uruchomienia plików skryptowych vpl_run.sh, vpl_debug.sh i vpl_evaluate.sh oraz wbudowanego w nie pliku vpl_execution.</p>
<p>Jeśli dana czynność jest oparta na innej czynności, limity mogą wpływać na bazowe pliki oraz ich potomków lub na globalną konfigurację tego modułu.</p>
';
$string['restrictededitor'] = 'Składanie tylko w zastrzeżonym edytorze';
$string['return_to_previous_page'] = 'Wróć do poprzedniej strony';
$string['run'] = 'Wykonaj';
$string['running'] = 'Wykonywanie';
$string['save'] = 'Zapisz';
$string['savecontinue'] = 'Zapisz i kontynuuj';
$string['saved'] = 'Zapisane';
$string['savedfile'] = "Plik '{\$a}' został zapisany";
$string['saveoptions'] = 'opcje zapisu';
$string['saving'] = 'Zapisywanie';
$string['scanactivity'] = 'Czynność';
$string['scandirectory'] = 'Katalog';
$string['scanningdir'] = 'Skanowanie katalogu ...';
$string['scanoptions'] = 'Opcje skanowania';
$string['scanother'] = 'Skanuj podobieństwa w dodanych plikach';
$string['scanzipfile'] = 'Plik Zip';
$string['select_all'] = 'Zaznacz wszystko';
$string['select_all_text'] = 'Zaznacz cały tekst';
$string['server'] = 'serwer';
$string['serverexecutionerror'] = 'Błąd wykonania serwera';
$string['shortdescription'] = 'Krótki opis';
$string['similarity'] = 'Podobieństwo';
$string['similarto'] = 'Podobne do';
$string['startdate'] = 'Dostępny od';
$string['submission'] = 'Składanie';
$string['submissionperiod'] = 'Okres składania';
$string['submissionrestrictions'] = 'Ograniczenia składania';
$string['submissions'] = 'Złożono';
$string['submissionselection'] = 'Wybór składania';
$string['submissionslist'] = 'Lista składania';
$string['submissionview'] = 'Widok składania';
$string['submittedby'] = 'Napisane przez {$a}';
$string['submittedon'] = 'Złożono';
$string['submittedonp'] = 'Złożono {$a}';
$string['sureresetfiles'] = 'Czy chcesz utracić całą pracę i zresetować pliki do postaci pierwotnej?';
$string['test'] = 'Składnik testu';
$string['testcases'] = 'Przypadków testowych';
$string['testcases_help'] = '
<b>Przykro nam, patrz wersja angielska</b>
';
$string['timelimited'] = 'Czas ograniczony';
$string['timeunlimited'] = 'Czas nieograniczony';
$string['toggle_show_line_number'] = 'Włącz numerowanie linii';
$string['totalnumberoferrors'] = "Errors";
$string['undo'] = 'Cofnij';
$string['undo_change'] = 'Cofnij zmianę';
$string['unzipping'] = 'Rozpakowywanie ...';
$string['uploadfile'] = 'Importuj plik';
$string['usevariations'] = 'Użyj wariacji';
$string['variation_n'] = 'Wariacja {$a}';
$string['variation_n_i'] = 'Wariacja {$a->number}: {$a->identification}';
$string['variation_options'] = 'Opcje wariacji';
$string['variations'] = 'Wariacje';
$string['variations_help'] = '
<h1>Wariacje</h1>
<p>Zestaw wariacji może być zdefiniowany dla każdej czynności. Wariacje te są losowo przypisywane do studentów.</p>
<p>Możesz tutaj wskazać czy czynność zawiera wariacje, ustawić tytuł zestawowi wariacji i dodać pożądane wariacje.</p>
<p>Każda wariacja ma kod identyfikacyjny oraz opis. Kod identyfikacyjny jest używany przez plik <b>vpl_enviroment.sh</b> aby przekazać wariację przypisaną do każdego studenta plikowi skryptowemu. Opis w formacie HTML jest pokazywany studentom, którzy przypisali odpowiednie zmiany.</p>
';
$string['variations_unused'] = 'Ta czynność ma wariacje, ale są wyłączone';
$string['variationtitle'] = 'Tytuł wariacji';
$string['varidentification'] = 'Identyfikacja';
$string['visiblegrade'] = 'Widoczny';
$string['vpl'] = 'Wirtualne Laboratorium Programowania';
$string['vpl:grade'] = 'Oceń przypisanie VPL';
$string['vpl:manage'] = 'Zarządzaj przypisaniem VPL';
$string['vpl:setjails'] = 'Ustaw jail serwery dla poszczególnych instancji VPL';
$string['vpl:similarity'] = 'Szukaj podobieństw przypisań VPL';
$string['vpl:submit'] = 'Wyślij przypisanie VPL';
$string['vpl:view'] = 'Pokaż pełny opis przypisania VPL';
$string['vpl_debug.sh'] = 'Ten skrypt przygotowuje debugowanie przesłanego programu';
$string['vpl_evaluate.sh'] = 'Ten skrypt przygotowuje sprawdzenie przesłanego programu';
$string['vpl_run.sh'] = 'Ten skrypt przygotowuje wykonanie przesłanego programu';
