<?php
$MESS["ERR_MAX_INPUT_VARS"] = "Der Wert von max_input_vars muss #MIN# oder noch höher sein. Der aktuelle Wert ist: #CURRENT#";
$MESS["ERR_NO_MODS"] = "Die erforderlichen Erweiterungen sind nicht installiert:";
$MESS["ERR_NO_MODS_DOC_GENERATOR"] = "Das Modul Dokumenten-Designer erfordert Erweiterungen php-xml und php-zip.";
$MESS["ERR_NO_SSL"] = "Die SSL-Unterstützung ist für PHP nicht aktiviert";
$MESS["ERR_NO_VM"] = "Eine reibungslose Funktionsweise von Bitrix24 kann nur mit der Bitrix Environment garantiert werden. Sie nutzen eine benutzerdefinierte Server-Environment.";
$MESS["ERR_OLD_VM"] = "Sie nutzen eine veraltete Version von Bitrix Environment (#CURRENT#). Installieren Sie bitte eine aktuelle Version, um etwaige Konfigurationsprobleme zu vermeiden (#LAST_VERSION#).";
$MESS["MAIN_AGENTS_HITS"] = "Die Agenten laufen über Hits, es wird empfohlen, die Agenten über cron auszuführen.";
$MESS["MAIN_BX_CRONTAB_DEFINED"] = "Die Konstante BX_CRONTAB ist definiert, sie kann nur in den Scripts definiert werden, welche über cron funktionieren.";
$MESS["MAIN_CATDOC_WARN"] = "Schlechte Version von catdoc: #VERSION#<br>
Details: https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=679877<br>
Installieren Sie eine ältere catdoc Version, oder eine neuere mit Fehlerbehebungen.";
$MESS["MAIN_CRON_NO_START"] = "cron_events.php ist nicht konfiguriert, um auf cron zu laufen; die letzte Ausführung des Agenten liegt mehr als 24 Stunden zurück.";
$MESS["MAIN_FAST_DOWNLOAD_ERROR"] = "Schnelles Herunterladen von Dateien auf der Basis von nginx ist nicht verfügbar, die entsprechende Option ist in den Einstellungen des Hauptmoduls aktiviert.";
$MESS["MAIN_FAST_DOWNLOAD_SUPPORT"] = "Die Unterstützung für schnelle Dateiübertragung via nginx ist verfügbar, aber die entsprechende Option ist in den Einstellungen des Hauptmoduls deaktiviert.";
$MESS["MAIN_IS_CORRECT"] = "Korrekt";
$MESS["MAIN_NO_OPTION_PULL"] = "Im Modul Push and Pull ist die Option zum Senden von PUSH-Benachrichtigungen nicht aktiviert. Die Benachrichtigungen werden an mobile Geräte nicht gesendet.";
$MESS["MAIN_NO_PULL"] = "Das Modul Push and Pull ist nicht installiert.";
$MESS["MAIN_NO_PULL_MODULE"] = "Das Modul Push and Pull ist nicht installiert. Die PUSH-Benachrichtigungen werden an mobile Geräte nicht gesendet.";
$MESS["MAIN_PAGES_PER_SECOND"] = "Seiten pro Sekunde";
$MESS["MAIN_PERF_HIGH"] = "Hoch";
$MESS["MAIN_PERF_LOW"] = "Niedrig";
$MESS["MAIN_PERF_MID"] = "Durchschnittlich";
$MESS["MAIN_PERF_VERY_LOW"] = "Unerlaubt niedrig";
$MESS["MAIN_SC_ABS"] = "Keine";
$MESS["MAIN_SC_ABSENT_ALL"] = "Keine";
$MESS["MAIN_SC_AGENTS_CRON"] = "Agentenausführung über Cron";
$MESS["MAIN_SC_ALL_FUNCS_TESTED"] = "Alle Intranet-Funktionen wurden überprüft und sind in Ordnung.";
$MESS["MAIN_SC_ALL_MODULES"] = "Alle erforderlichen Module sind installiert.";
$MESS["MAIN_SC_AVAIL"] = "Verfügbar";
$MESS["MAIN_SC_BUSINESS"] = "Business-Funktionen des Intranets";
$MESS["MAIN_SC_CANT_CHANGE"] = "Es ist nicht möglich, den Wert von pcre.backtrack_limit über ini_set zu ändern.";
$MESS["MAIN_SC_CLOUD_TEST"] = "Zugriff auf Bitrix Cloud Services";
$MESS["MAIN_SC_COMPRESSION_TEST"] = "Komprimierung und Beschleunigung der Seite";
$MESS["MAIN_SC_COMP_DISABLED"] = "Die Komprimierung wird vom Server nicht unterstützt, es muss dafür das php-Modul Komprimierung eingesetzt werden.";
$MESS["MAIN_SC_COMP_DISABLED_MOD"] = "Die Komprimierung wird vom Server nicht unterstützt, das Modul Komprimierung ist deaktiviert.";
$MESS["MAIN_SC_CORRECT"] = "Korrekt";
$MESS["MAIN_SC_CORRECT_DESC"] = "Intranet erfordert eine spezielle Konfiguration der Server-Umgebung. Die <a href=\"http://www.bitrix.de/products/virtual_appliance/\" target=\"_blank\">Bitrix Virtual Appliance</a> ist standardmäßig vorkonfiguriert. Einige Funktionen können nicht verfügbar sein, wenn erforderliche Parameter nicht konfiguriert sind.";
$MESS["MAIN_SC_CORRECT_SETTINGS"] = "Einstellungen sind korrekt";
$MESS["MAIN_SC_DEFAULT_CHARSET"] = "Der Parameter default_charset darf nicht leer sein.";
$MESS["MAIN_SC_DOCS_EDIT_MS_OFFICE"] = "Dokumente in Microsoft Office bearbeiten";
$MESS["MAIN_SC_ENABLED"] = "Die Komprimierung wird vom Server unterstützt, das Modul Komprimierung muss  deinstalliert werden.";
$MESS["MAIN_SC_ENABLED_MOD"] = "Die Komprimierung erfolgt durch das Server-Modul.";
$MESS["MAIN_SC_ENC_EQUAL"] = "Die Werte mbstring.internal_encoding und default_charset stimmen nicht überein. Es wird empfohlen, den Wert mbstring.internal_encoding zu bereinigen und default_charset zu setzen.";
$MESS["MAIN_SC_ENC_NON_UTF"] = "Der Wert von default_charset muss auf eine andere Codierung als UTF-8 gesetzt werden.";
$MESS["MAIN_SC_ENC_UTF"] = "Der Wert von default_charset muss auf UTF-8 gesetzt werden.";
$MESS["MAIN_SC_ERROR_PRECISION"] = "Der Wert des Parameters \"precision\" ist nicht gültig.";
$MESS["MAIN_SC_EXTERNAL_ANSWER_INCORRECT"] = "Die Verbindung mit dem Intranet von außen war erfolgreich, aber der Server gibt einen nicht korrekten Status an.";
$MESS["MAIN_SC_EXTERNAL_APPS_TEST"] = "Anwendungen (MS Office, Outlook, Exchange) über sichere Verbindung";
$MESS["MAIN_SC_EXTERNAL_CALLS"] = "Externe Videoanrufe";
$MESS["MAIN_SC_EXTRANET_ACCESS"] = "Externer Zugriff auf Extranet";
$MESS["MAIN_SC_FAST_FILES_TEST"] = "Schneller Zugriff und Dateien und Dokumente";
$MESS["MAIN_SC_FULL_TEST_DESC"] = "Starten Sie die komplette Systemüberprüfung, um Engpässe festzustellen und Fehler zu beheben oder Probleme in Zukunft zu vermeiden. Kurzbeschreibungen für jeden Test werden Ihnen helfen, etwaige Probleme schnell zu finden und zu beseitigen.";
$MESS["MAIN_SC_FUNC_OVERLOAD"] = "Veralteter Parameter mbstring.func_overload wurde festgestellt. Bitte entfernen Sie ihn.";
$MESS["MAIN_SC_FUNC_WORKS_FINE"] = "Die Funktion ist in Ordnung.";
$MESS["MAIN_SC_FUNC_WORKS_PARTIAL"] = "Diese Funktion kann Probleme haben, Sie sollten diese finden und beheben.";
$MESS["MAIN_SC_FUNC_WORKS_WRONG"] = "Die Funktion ist nicht in Ordnung, beheben Sie Fehler.";
$MESS["MAIN_SC_GENERAL"] = "Allgemeine Intranet-Funktionen";
$MESS["MAIN_SC_GENERAL_SITE"] = "Allgemeine Website-Funktionen";
$MESS["MAIN_SC_GOT_ERRORS"] = "Die Website enthält Fehler. <a href=\"#LINK#\">Prüfen und beheben</a>";
$MESS["MAIN_SC_MAIL_INTEGRATION"] = "Integration mit den externen E-Mail-Account ist n Ordnung, aber kein Nutzer hat die Integrationseinstellungen vorgenommen.";
$MESS["MAIN_SC_MAIL_IS_NOT_INSTALLED"] = "Das Modul E-Mail ist nicht installiert.";
$MESS["MAIN_SC_MAIL_TEST"] = "E-Mail-Benachrichtigungen";
$MESS["MAIN_SC_MBSTRING_SETTIGNS_DIFFER"] = "Die Einstellungen von mbstring in <i>/bitrix/.settings.php</i> (utf_mode) und <i>/bitrix/php_interface/dbconn.php</i> (BX_UTF) unterscheiden sich.";
$MESS["MAIN_SC_MCRYPT"] = "Verschlüsselungsfunktionen";
$MESS["MAIN_SC_METHOD_NOT_SUP"] = "Der Server unterstützt nicht die Methode #METHOD#.";
$MESS["MAIN_SC_NOT_AVAIL"] = "Nicht verfügbar";
$MESS["MAIN_SC_NOT_SUPPORTED"] = "Der Server unterstützt nicht diese Funktion.";
$MESS["MAIN_SC_NO_ACCESS"] = "Der Server von Bitrix24 ist nicht verfügbar. Aktualisierungen und Bitrix Cloud Services sind nicht verfügbar.";
$MESS["MAIN_SC_NO_CONFLICT"] = "Es wurden keine Konflikte festgestellt.";
$MESS["MAIN_SC_NO_CONNECTTO"] = "Verbindung zu #HOST# kann nicht hergestellt werden";
$MESS["MAIN_SC_NO_EXTERNAL_ACCESS_"] = "Diese Funktion ist nicht verfügbar, weil das Intranet von außen nicht erreichbar ist.";
$MESS["MAIN_SC_NO_EXTERNAL_ACCESS_MOB"] = "Diese Funktion ist nicht verfügbar, weil das Intranet von außen via mobile Anwendung nicht erreichbar ist.";
$MESS["MAIN_SC_NO_EXTERNAL_CONNECT_WARN"] = "Die Verbindung mit dem Intranet von außen ist nicht möglich. Die mobile Anwendung wird nicht funktionieren.";
$MESS["MAIN_SC_NO_EXTRANET_CONNECT"] = "Das Extranet funktioniert nicht korrekt, weil das Intranet von außen via Internet nicht erreichbar ist.";
$MESS["MAIN_SC_NO_IM"] = "Das Modul Web Messenger ist nicht installiert.";
$MESS["MAIN_SC_NO_LDAP_INTEGRATION"] = "Die Integration mit dem AD-Server ist nicht eingestellt.";
$MESS["MAIN_SC_NO_LDAP_MODULE"] = "Das Modul LDAP ist nicht installiert.";
$MESS["MAIN_SC_NO_NTLM"] = "Aktuelle Verbindung verwendet nicht die NTLM-Authentifizierung";
$MESS["MAIN_SC_NO_PULL_EXTERNAL_2"] = "Externe Verbindung mit Ihrem Bitrix24 wurde hergestellt. Der Leseport des Push-Servers ist jedoch nicht verfügbar. Sofortnachrichten werden in der mobilen App nicht verfügbar sein.";
$MESS["MAIN_SC_NO_PUSH_STREAM_2"] = "Push-Server ist in den Einstellungen des Moduls Push and Pull nicht konfiguriert. Dieser Server ist erforderlich, um die Feed-Kommentare in Echtzeit anzeigen zu können.";
$MESS["MAIN_SC_NO_PUSH_STREAM_CONNECTION"] = "Die Verbindung mit dem Modul nginx-push-stream kann nicht hergestellt werden, um Sofortnachrichten zu senden";
$MESS["MAIN_SC_NO_PUSH_STREAM_CONNECTION_2"] = "Verbindung mit dem Push-Server kann nicht hergestellt werden, um Sofortnachrichten zu versenden";
$MESS["MAIN_SC_NO_PUSH_STREAM_VIDEO_2"] = "Push-Server ist in den Einstellungen des Moduls Push and Pull nicht konfiguriert. Dieser Server ist erforderlich, um Videoanrufe machen zu können.";
$MESS["MAIN_SC_NO_REST_MODULE"] = "Das Modul Rest ist nicht installiert.";
$MESS["MAIN_SC_NO_SOCIAL_MODULE"] = "Das Modul Soziale Netzwerke ist nicht installiert.";
$MESS["MAIN_SC_NO_SOCIAL_SERVICES"] = "In den Einstellungen des Moduls Soziale Netzwerke sind keine sozialen Services konfiguriert.";
$MESS["MAIN_SC_NO_SOCIAL_SERVICES_24NET"] = "Die Integration mit Bitrix24.net ist in den Einstellungen des Moduls Soziale Services nicht konfiguriert.";
$MESS["MAIN_SC_NO_SUB_CONNECTION_2"] = "Verbindung mit dem Push-Server kann nicht hergestellt werden, um Sofortnachrichten zu lesen";
$MESS["MAIN_SC_NO_WEBDAV_MODULE"] = "Das Modul der Dokumentenbibliothek ist nicht installiert.";
$MESS["MAIN_SC_NTLM_SUCCESS"] = "Die NTLM-Authentifizierung ist in Ordnung, aktueller Nutzer: ";
$MESS["MAIN_SC_OPTION_SWITCHED_OFF"] = "De NTLM-Authentifizierung ist in den Einstellungen des LDAP-Moduls aktiviert.";
$MESS["MAIN_SC_PATH_PUB"] = "Der Pfad für die Veröffentlichung von Nachrichten in den Einstellungen des Moduls Push and Pull ist nicht korrekt.";
$MESS["MAIN_SC_PATH_SUB"] = "Die Lese-URL der Nachricht in den Einstellungen des Moduls Push and Pull ist nicht korrekt.";
$MESS["MAIN_SC_PERFORM"] = "Performance";
$MESS["MAIN_SC_PERF_TEST"] = "Bewertung der Server-Performance";
$MESS["MAIN_SC_PULL_NOT_REGISTERED"] = "Fehler bei der Registrierung auf dem Bitrix Push Server";
$MESS["MAIN_SC_PULL_UNSUPPORTED_VERSION"] = "In den Einstellungen des Moduls Push and Pull ist eine veraltete Version des Push-Servers angegeben. Sie müssen Ihren Push-Server aktualisieren. <a href=\"https://training.bitrix24.com/support/training/course/index.php?COURSE_ID=26&LESSON_ID=21596\">Mehr erfahren.</a> ";
$MESS["MAIN_SC_PUSH_INCORRECT"] = "Das Modul nginx-push-stream funktioniert nicht korrekt.";
$MESS["MAIN_SC_REAL_TIME"] = "Kommunikation in Echtzeit und Videoanrufe";
$MESS["MAIN_SC_REQUIRED_MODS_DESC"] = "Stellt sicher, dass alle erforderlichen Module installiert und alle wichtigen Einstellungen korrekt sind. Im anderen Fall kann eine reibungslose Funktionsfähigkeit des Intranets nicht gewährleistet werden.";
$MESS["MAIN_SC_SEARCH_INCORRECT"] = "Die Indexierung der Dokumentinhalte funktioniert nicht korrekt.";
$MESS["MAIN_SC_SITE_GOT_ERRORS"] = "Die Website hat Fehler. <a href=\"#LINK#\">Prüfen und beheben.</a>";
$MESS["MAIN_SC_SOME_WARNING"] = "Warnung";
$MESS["MAIN_SC_SSL_NOT_VALID"] = "Der Server verfügt über ein nicht gültiges SSL-Zertifikat.";
$MESS["MAIN_SC_STREAM_DISABLED_2"] = "Push-Server ist in den Einstellungen des Moduls Push and Pull nicht konfiguriert.";
$MESS["MAIN_SC_SYSTEST_LOG"] = "Protokoll der Systemüberprüfung";
$MESS["MAIN_SC_TEST_COMMENTS"] = "Kommentare live";
$MESS["MAIN_SC_TEST_DOCS"] = "Dokumente in Google Docs und Microsoft Office Online bearbeiten";
$MESS["MAIN_SC_TEST_FAST_FILES"] = "Bitrix24.Drive. Schnelle Dateiverwaltung";
$MESS["MAIN_SC_TEST_IS_INCORRECT"] = "Korrekte Ergebnisse sind nicht möglich, weil der Test fehlgeschlagen ist.";
$MESS["MAIN_SC_TEST_LDAP"] = "Integration mit Active Directory";
$MESS["MAIN_SC_TEST_MAIL_INTEGRATION"] = "Mail-Integration innerhalb des Unternehmens";
$MESS["MAIN_SC_TEST_MAIL_PUSH"] = "E-Mail-Nachrichten an den Activity Stream weiterleiten";
$MESS["MAIN_SC_TEST_MOBILE"] = "Bitrix24 Mobile App";
$MESS["MAIN_SC_TEST_NTLM"] = "Windows NTLM-Authentifizierung";
$MESS["MAIN_SC_TEST_PUSH"] = "Benachrichtigungen auf mobile Geräte (Push-Benachrichtigungen)";
$MESS["MAIN_SC_TEST_PUSH_SERVER"] = "Push and Pull Server";
$MESS["MAIN_SC_TEST_REST"] = "Nutzung von REST API";
$MESS["MAIN_SC_TEST_RESULT"] = "Testergebnisse:";
$MESS["MAIN_SC_TEST_SEARCH_CONTENTS"] = "Dokumentinhalte suchen";
$MESS["MAIN_SC_TEST_SOCNET_INTEGRATION"] = "Integration mit sozialen Services";
$MESS["MAIN_SC_TEST_SSL1"] = "Sichere HTTPS-Verbindung wurde hergestellt, die Überprüfung der Gültigkeit vom SSL-Zertifikat ist fehgeschlagen, weil die Liste der Zertifizierungsstellen von der Website &quot;Bitrix&quot; nicht heruntergeladen wurde.";
$MESS["MAIN_SC_TEST_SSL_WARN"] = "Eine sichere Verbindung konnte nicht hergestellt werden. Es können Probleme bei Integration mit externen Anwendungen entstehen.";
$MESS["MAIN_SC_TEST_VIDEO"] = "Video-Anrufe";
$MESS["MAIN_SC_UNKNOWN_ANSWER"] = "Unbekannte Antwort von #HOST#";
$MESS["MAIN_SC_WARNINGS"] = "Mobile Benachrichtigungen";
$MESS["MAIN_SC_WARN_EXPAND_SESSION"] = "Wenn das Modul Instant Messenger installiert ist, muss die Option der Sitzungsverlängerung, wenn der Nutzer im Browser aktiv ist, in den <a href='/bitrix/admin/settings.php?mid=main' target=_blank>Einstellungen des Hauptmoduls</a> deaktiviert werden, um die Serverbelastung zu reduzieren.";
$MESS["MAIN_SC_WINDOWS_ENV"] = "Integration mit Windows-Environment";
$MESS["MAIN_TMP_FILE_ERROR"] = "Eine temporäre Datei für die Testzwecke konnte nicht erstellt werden.";
$MESS["MAIN_WRONG_ANSWER_PULL"] = "Eine unbekannte Antwort vom PUSH-Server.";
$MESS["SC_BX_UTF"] = "Benutzen Sie folgenden Code in <i>/bitrix/php_interface/dbconn.php</i>:
<code>define('BX_UTF', true);</code> 
";
$MESS["SC_BX_UTF_DISABLE"] = "Die Konstante BX_UTF muss nicht bestimmt werden";
$MESS["SC_CACHED_EVENT_WARN"] = "Informationen über das Versenden von E-Mails sind im Cache, was ein Fehler sein mag. Versuchen Sie Cache zu leeren.";
$MESS["SC_CHARSET_CONN_VS_RES"] = "Die Verbindungskodierung (#CONN#) unterscheidet sich von der Ergebniskodierung (#RES#).";
$MESS["SC_CHECK_B"] = "Prüfung";
$MESS["SC_CHECK_FILES"] = "Dateiberechtigungen überprüfen";
$MESS["SC_CHECK_FILES_ATTENTION"] = "Achtung!";
$MESS["SC_CHECK_FILES_WARNING"] = "Datei-Zugriffsüberprüfung verursacht hohe Last auf dem Server.";
$MESS["SC_CHECK_FOLDER"] = "Ordnerprüfung";
$MESS["SC_CHECK_FULL"] = "Komplettprüfung";
$MESS["SC_CHECK_KERNEL"] = "Kernprüfung";
$MESS["SC_CHECK_TABLES_ERRORS"] = "Datenbank-Tabellen enthalten #VAL# Fehler der Zeichenkodierung, #VAL1# von ihnen können automatisch korrigiert werden.";
$MESS["SC_CHECK_TABLES_STRUCT_ERRORS"] = "Es gibt Fehler in der Datenbankstruktur. Gesamt Fehler: #VAL#. #VAL1# können sofort behoben werden.";
$MESS["SC_CHECK_TABLES_STRUCT_ERRORS_FIX"] = "Die Felder wurden behoben, aber einige Felder (#VAL#) haben andere Typen. Sie werden es manuell beheben müssen, indem Sie das Website-Protokoll überprüfen.";
$MESS["SC_CHECK_UPLOAD"] = "Prüfung des Uploadordners";
$MESS["SC_COLLATE_WARN"] = "Der Vergleichswert für &quot;#TABLE#&quot; (#VAL0#) weicht von dem Datenbankwert (#VAL1#) ab. ";
$MESS["SC_CONNECTION_CHARSET"] = "Zeichenkodierung der Verbindung";
$MESS["SC_CONNECTION_CHARSET_NA"] = "Prüfung ist wegen eines Fehlers der Verbindungskodierung fehlgeschlagen.";
$MESS["SC_CONNECTION_CHARSET_WRONG"] = "Die Zeichenkodierung der Verbindung mit der Datenbank muss #VAL# sein, der aktuelle Wert ist #VAL1#.";
$MESS["SC_CONNECTION_CHARSET_WRONG_NOT_UTF"] = "Die Zeichenkodierung der Verbindung mit der Datenbank soll nicht UTF-8 sein, der aktuelle Wert ist: #VAL#.";
$MESS["SC_CONNECTION_COLLATION_WRONG_UTF"] = "Die alphabetische Sortierung der Verbindung mit der Datenbank muss utf8_unicode_ci sein, der aktuelle Wert ist #VAL#.";
$MESS["SC_CRON_WARN"] = "Die Konstante BX_CRONTAB_SUPPORT ist in /bitrix/php_interface/dbconn.php definiert, dabei müssen Agenten via Cron gestartet werden.";
$MESS["SC_DATABASE_CHARSET_DIFF"] = "Die Datenbank-Zeichenkodierung (#VAL1#) stimmt nicht mit der Zeichencodierung der alphabetischen Sortierung (#VAL0#) überein.";
$MESS["SC_DATABASE_COLLATION_DIFF"] = "Die alphabetische Sortierung der Datenbank (#VAL1#) stimmt nicht mit der alphabetischen Sortierung der Verbindung  (#VAL0#) überein.";
$MESS["SC_DB_CHARSET"] = "Zeichenkodierung der Datenbank";
$MESS["SC_DB_ERR"] = "Fehler bei der Datenbankversion:";
$MESS["SC_DB_ERR_INNODB_STRICT"] = "innodb_strict_mode=#VALUE#, OFF ist erforderlich";
$MESS["SC_DB_ERR_MODE"] = "Die sql_mode Variable in MySQL muss leer sein. Aktueller Wert:";
$MESS["SC_DB_MISC_CHARSET"] = "Der Zeichensatz (#T_CHAR#) der Tabelle #TBL# entspricht nicht dem Datenbankzeichensatz (#CHARSET#).";
$MESS["SC_DELIMITER_ERR"] = "Aktuelles Trennzeichen: &quot;#VAL#&quot;, &quot;.&quot; ist erforderlich.";
$MESS["SC_ERROR0"] = "Fehler!";
$MESS["SC_ERROR1"] = "Der Test ist fehlgeschlagen.";
$MESS["SC_ERRORS_FOUND"] = "Es&nbsp;wurden&nbsp;Fehler entdeckt";
$MESS["SC_ERRORS_NOT_FOUND"] = "Keine&nbsp;Fehler&nbsp;entdeckt";
$MESS["SC_ERR_CONNECT_MAIL001"] = "Der Mail-Server mail-001.bitrix24.com kann nicht verbunden werden.";
$MESS["SC_ERR_CONN_DIFFER"] = "sind in .settings.php und dbconn.php unterschiedlich.";
$MESS["SC_ERR_DNS"] = "Der MX Eintrag für die Domain #DOMAIN# kann nicht angefordert werden.";
$MESS["SC_ERR_DNS_WRONG"] = "DNS-Konfiguration ist nicht korrekt. Dort muss ein einziger MX Eintrag sein: mail-001.bitrix24.com (aktuell: #DOMAIN#).";
$MESS["SC_ERR_FIELD_DIFFERS"] = "Tabelle #TABLE#: Das Feld #FIELD# \"#CUR#\" entspricht nicht der Beschreibung \"#NEW#\"";
$MESS["SC_ERR_NO_FIELD"] = "In der Tabelle #TABLE# fehlt das Feld #FIELD#";
$MESS["SC_ERR_NO_INDEX"] = "In der Tabelle #TABLE# fehlt der Index #INDEX#";
$MESS["SC_ERR_NO_INDEX_ENABLED"] = "Der Volltext-Suchindex #INDEX# ist für die Tabelle #TABLE# nicht aktiviert";
$MESS["SC_ERR_NO_SETTINGS"] = "Die Konfigurationsdatei /bitrix/.settings.php wurde nicht gefunden";
$MESS["SC_ERR_NO_TABLE"] = "Die Tabelle #TABLE# existiert nicht.";
$MESS["SC_ERR_NO_VALUE"] = "Es gibt keinen Systemeintrag #SQL# für Tabelle #TABLE#";
$MESS["SC_ERR_PHP_PARAM"] = "Der Parameter #PARAM# ist #CUR#, erforderlich ist jedoch #REQ#.";
$MESS["SC_ERR_TEST_MAIL_PUSH"] = "Die Verbindung zu #DOMAIN# kann vom E-Mail Server nicht hergestellt werden.";
$MESS["SC_FIELDS_COLLATE_WARN"] = "Das Ergebnis des Feldes &quot;#FIELD#&quot; in der Tabelle &quot;#TABLE#&quot;  (#VAL1#) stimmt nicht mit dem der Datenbank (#VAL1#) überein.";
$MESS["SC_FILES_CHECKED"] = "Geprüfte Dateien: <b>#NUM#</b><br>Aktueller Pfad: <i>#PATH#</i>";
$MESS["SC_FILES_FAIL"] = "Nicht verfügbar zum Lesen und Schreiben (die ersten 10):";
$MESS["SC_FILES_OK"] = "Alle geprüften Dateien sind verfügbar zum Lesen und Schreiben.";
$MESS["SC_FILE_EXISTS"] = "Datei vorhanden:";
$MESS["SC_FIX"] = "Korrigieren";
$MESS["SC_FIX_DATABASE"] = "Datenbank-Fehler korrigieren";
$MESS["SC_FIX_DATABASE_CONFIRM"] = "Das System wird jetzt versuchen, die Datenbank-Fehler zu korrigieren. Diese Aktion kann gefährlich sein. Erstellen Sie eine Datenbank-Sicherungskopie, bevor Sie weitere Schritte unternehmen.

Fortfahren?";
$MESS["SC_FIX_MBSTRING"] = "Konfiguration korrigieren";
$MESS["SC_FIX_MBSTRING_CONFIRM"] = "Achtung!

Die Konfigurationsdateien werden geändert. Wird diese Operation fehlschlagen, werden Sie Ihre Website nur über das Control Panel des Webhostings wiederherstellen können.

Fortfahren?
";
$MESS["SC_FULL_CP_TEST"] = "Vollständiger Systemtest";
$MESS["SC_GR_EXTENDED"] = "Zusätzliche Funktionen";
$MESS["SC_GR_FIX"] = "Fehler der Datenbank beheben";
$MESS["SC_GR_MYSQL"] = "Datenbanktest";
$MESS["SC_HELP"] = "Hilfe.";
$MESS["SC_HELP_CHECK_ACCESS_DOCS"] = "Um Dokumente via Google Docs oder Microsoft Office Online anzeigen oder bearbeiten zu können, wird eine spezielle von extern aus erreichbare URL erstellt und an die Services gesendet, welche für das Dokument benutzt werden. Die URL ist einmalig und wird sofort ungültig, sobald das Dokument geschlossen wird.

Diese Funktion erfordert, dass Ihr Intranet von außen via Internet erreichbar ist.
";
$MESS["SC_HELP_CHECK_ACCESS_MOBILE"] = "Die mobile Anwendung erfordert, dass Ihr Intranet von außen via Internet erreichbar ist.

Der Test verwendet einen speziellen Server auf checker.internal.bitrix24.com, der versucht eine Verbindung mit Ihrem Intranet anhand des URL herzustellen, welche im Web-Browser angegeben ist. Während der Verbindung mit dem Fernserver werden keine Nutzerdaten übertragen.

Der Instant Messenger erfordert, dass der Leseport von Nginx's push-stream-module verbunden werden kann. Die Portnummer kann in den Einstellungen des Moduls <a href=\"http://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=26&LESSON_ID=5144\">Push and Pull</a> gefunden werden.
";
$MESS["SC_HELP_CHECK_AD"] = "Wenn in Ihrem lokalen Netzwerk ein Windows AD- oder LDAP-Server eingestellt ist, sollte überprüft werden, ob AD <a href=\"http://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=20&CHAPTER_ID=04264\">korrekt konfiguriert ist</a>.

Diese Funktion erfordert, dass das PHP ldap-Modul installiert ist.
";
$MESS["SC_HELP_CHECK_BX_CRONTAB"] = "Um die aperiodischen Agenten und die E-Mail auf cron zu übertragen, fügen Sie die folgende Konstante zu <i>/bitrix/php_interface/dbconn.php</i> hinzu:

<code>define('BX_CRONTAB_SUPPORT', true);</code>



Wenn bei dieser Konstante der Wert \"true\" gesetzt wird, werden im System nur periodische Agenten bei Hits ausgeführt. Nun fügen Sie zu cron eine Aufgabe hinzu, das Skript <i>/var/www/bitrix/modules/main/tools/cron_events.php</i> jede Minute auszuführen (ersetzen Sie  <i>/var/www</i> durch den Pfad zum Root-Verzeichnis Ihrer Website).



Das Skript bestimmt die Konstante <b>BX_CRONTAB</b>, die zeigt, dass das Skript von cron aus aktiviert ist und nur aperiodische Agenten ausführt. Wenn Sie diese Konstante aus Versehen in <i>dbconn.php</i> bestimmen, werden periodische Agenten nie ausgeführt.
";
$MESS["SC_HELP_CHECK_CACHE"] = "Bei diesem Test wird geprüft, ob ein PHP-Prozess eine <b>.tmp</b> Datei im Cache-Verzeichnis erstellen und diese dann zu <b>.php</b> umbenennen kann. Einige Web-Server für Windows können beim Umbenennen versagen, wenn die Nutzer-Zugriffsberechtigungen nicht korrekt eingestellt sind.";
$MESS["SC_HELP_CHECK_CA_FILE"] = "Der Test versucht eine Verbindung mit der Website www.bitrix.de herzustellen. 

Das ist erforderlich für die tägliche Arbeit mit Bitrix Cloud Services &quot; Bitrix Cloud Services &quot; (CDN, Sicherungskopien, Sicherheits-Scanner usw.), wenn die Informationen über Disk Quota oder über den aktuellen Service-Status aktualisiert werden. Die Nutzerdaten werden dabei an unseren Server nicht übertragen.

Mit diesem Test kann außerdem eine Liste der Zertifizierungsstellen von unserer Website geladen werden: Diese Liste ist dann für den nächsten Test zur Prüfung der Gültigkeit des SSL-Zertifikats einer aktuellen Website erforderlich.
";
$MESS["SC_HELP_CHECK_CLONE"] = "Seit der Version 5 werden im PHP die Objekte eher mit dem Verweis übertragen als kopiert. Es gibt aber nach wie vor PHP 5 Sets, die das Vererben unterstützen, so dass Objekte als Kopien übertragen werden.



Um dieses Problem zu lösen, laden Sie einen aktuelleren PHP 5 Set herunter und installieren Sie ihn.
";
$MESS["SC_HELP_CHECK_COMPRESSION"] = "Eine html-Komprimierung ist erforderlich, um die Zeit der Übertragung sowie die allgemeine Wartezeit zum Öffnen von Seiten zu reduzieren.

Um die Serverlast zu reduzieren, muss die Komprimierung mit dem speziellen Modul des Web-Servers erfolgen. 

Ist diese Möglichkeit nicht verfügbar, wird dafür das Bitrix Modul Komprimierung eingesetzt, im anderen Fall muss das Modul Komprimierung  werden <a href=\"/bitrix/admin/module_admin.php\">nicht installiert</a> sein.
";
$MESS["SC_HELP_CHECK_CONNECT_MAIL"] = "Um Benachrichtigungen über neue E-Mail-Nachrichten direkt aus dem Intranet zu bekommen, muss der Nutzer die Parameter seiner Mailbox-Verbindung in seinem Intranet-Profil angeben.";
$MESS["SC_HELP_CHECK_DBCONN"] = "Hier wird die Textausgabe in den Konfigurationsdateien  <i>dbconn.php</i> und <i>init.php</i> geprüft.

Selbst ein Leerzeichen oder ein Zeilenumbruch können dazu führen, dass eine komprimierte Seite von dem Client-Browser nicht entpackt und gelesen werden kann.

Darüber hinaus können Probleme mit Autorisierung und CAPTCHA auftreten.
";
$MESS["SC_HELP_CHECK_DBCONN_SETTINGS"] = "Dieser Test wird Verbindungsparameter für die Datenbank, welche in <i>/bitrix/php_interface/dbconn.php</i> angegeben sind mit denen in <i>/bitrix/.settings.php</i> vergleichen. 
Diese Einstellungen müssen in beiden Dateien gleich sein. Anderenfalls werden einige SQL-Anfragen an eine andere Datenbank umgeleitet, was unberechenbare Folgen verursachen kann.

Der neue D7-Kernel benutzt Parameter aus <i>.settings.php</i>. Aufgrund einer Rückkompatibilität kann auf <i>dbconn.php</i> nicht verzichtet werden.

Wenn in <i>.settings.php</i> Verbindungsparameter nicht angegeben sind, benutzt der neue Kernel die aus <i>dbconn.php</i>.
";
$MESS["SC_HELP_CHECK_EXEC"] = "Wenn PHP im Modus CGI/FastCGI auf einem Unix-System funktioniert, verlangen Skripts bestimmte Berechtigungen zur Ausführung, sonst werden sie nicht funktionieren.

Wenn dieser Test fehlschlägt, kontaktieren Sie den Technischen Support Ihres Hosting-Anbieters, um benötigte Zugriffsberechtigungen für Dateien zu bekommen und stellen Sie dann die Konstanten <b>BX_FILE_PERMISSIONS</b> und <b>BX_DIR_PERMISSIONS</b> in <i>dbconn.php</i> entsprechend ein.



Wenn möglich, konfigurieren Sie PHP als ein Apache-Modul.
";
$MESS["SC_HELP_CHECK_EXTRANET"] = "Damit das Modul <a href=\"http://www.bitrixsoft.com/products/intranet/features/collaboration/extranet.php\">Extranet</a> funktioniert, muss Ihr Intranet von außen via Internet erreichbar sein.

Wenn Sie die Funktionen, die dieses Modul anbietet, nicht benötigen, können Sie es einfach <a href=\"/bitrix/admin/module_admin.php\">deinstallieren</a>.
";
$MESS["SC_HELP_CHECK_FAST_DOWNLOAD"] = "Schnelle Dateiübertragung benutzt eine interne Umleitung <a href=\"http://wiki.nginx.org/X-accel\">nginx</a>. In diesem Fall erfolgt die Überprüfung des Zugriffs auf die Datei mithilfe von PHP, und die Übertragung selbst mithilfe von nginx. 

Die PHP-Ressourcen werden für die Bearbeitung einer nächsten Anfrage freigemacht. Das erhöht die Intranet-Performance und die Geschwindigkeit der Dateiübertragung über Bitrix24.Drive, optimiert die Arbeit mit der Dokumentenbibliothek und ermöglicht eine schnellere Dateiübertragung aus dem Activity Stream.

In den Einstellungen vom <a href=\"/bitrix/admin/settings.php?mid=main\">Hauptmodul</a> muss eine entsprechende Option aktiviert werden. Die <a href=\"http:// www.bitrix.de/products/virtual_appliance/\">Bitrix Virtual Appliance</a> unterstützt diese Option standardmäßig.
";
$MESS["SC_HELP_CHECK_GETIMAGESIZE"] = "Wenn Sie ein Flash-Objekt hinzufügen, braucht der visuelle Editor die Objektgröße zu erkennen. Dazu führt er die PHP-Standardfunktion <b>getimagesize</b> aus, welche die Erweiterung <b>Zlib</b> erfordert. Bei komprimierten Flash-Objekten kann diese Funktion fehlschlagen, wenn die Erweiterung  <b>Zlib</b> als ein Modul installiert ist. Die Erweiterung muss also statisch aufgebaut werden.



Zur Lösung dieses Problem kontaktieren Sie den Technischen Support Ihres Hosting-Anbieters.
";
$MESS["SC_HELP_CHECK_HTTP_AUTH"] = "Mithilfe von den HTTP-Überschriften werden bei diesem Test die Autorisierungsdaten gesendet. Dann wird versucht, diese Daten mit der Server-Variablen REMOTE_USER (oder REDIRECT_REMOTE_USER) zu bestimmen. Die HTTP-Autorisierung ist für die Integration mit den Softwares der Dritthersteller erforderlich.



Wenn PHP im Modus CGI/FastCGI funktioniert (fragen Sie dies bei Ihrem Hosting-Anbieter nach), verlangt der Apache-Server das Modul mod_rewrite module und eine folgende Regel in .htaccess:

<b>RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization}]</b>



Wenn möglich, konfigurieren Sie PHP als ein Apache-Modul.
";
$MESS["SC_HELP_CHECK_INSTALL_SCRIPTS"] = "Manchmal können Nutzer vergessen, die Installationsskripts (restore.php, bitrixsetup.php) zu löschen, nachdem das System wiederhergestellt oder installiert wurde. Das kann zu einem ernsthaften Sicherheitsproblem werden und  zum eventuellen Website-Hijacking führen. Wenn Sie die Meldung über das automatische Löschen ignoriert haben, müssen Sie diese Dateien manuell entfernen.";
$MESS["SC_HELP_CHECK_LOCALREDIRECT"] = "Nachdem das Formular des administrativen Bereichs gespeichert ist (also auf Speichern oder Anwenden geklickt wurde), wird der Client auf die ursprüngliche Seite umgeleitet. Das wird gemacht, um wiederholte Formulareinträge zu vermeiden, wenn ein Nutzer die Seite aktualisiert. Damit diese Umleitung erfolgreich funktioniert, muss eine ganze Reihe von wichtigen Variablen auf dem Web-Server korrekt bestimmt sind sowie das Überschreiben der http-Überschriften erlaubt ist.

Wenn einige der Server-Variablen in <i>dbconn.php</i> neu bestimmt wurden, wird der Test eben diese Neubestimmungen benutzen. Mit anderen Worten, bei der Umleitung werden reale Lebenssituationen komplett simuliert.
";
$MESS["SC_HELP_CHECK_MAIL"] = "Hier wird eine E-Mail-Nachricht an hosting_test@bitrixsoft.com via PHP-Standardfunktion \"mail\" gesendet. Dabei gibt es ein spezielles Postfach, damit die Testbedingungen an die des wirklichen Lebens maximal angepasst werden. 

Dieser Test sendet das Skript der Seitenprüfung als eine Testnachricht, aber  <b>er sendet nie irgendwelche Nutzerdaten</b>.

Beachten Sie, dass der Test den Nachrichtempfang nicht überprüft. Der Empfang bei den anderen Postfächern kann ebenso nicht überprüft werden.

Wenn Versenden von E-Mails länger als eine Sekune dauern, kann die Server-Leistungsstärke wesentlich beeinträchtigt werden. Kontaktieren Sie den Technischen Support Ihres Hosting-Anbieters, damit er das Versenden der E-Mails über einen Spooler konfiguriert.

Alternativ können Sie cron benutzen, um die E-Mails zu versenden. Dafür fügen Sie <code>define('BX_CRONTAB_SUPPORT', true);</code> zu dbconn.php hinzu. Dann stellen Sie cron ein, <i>php /var/www/bitrix/modules/main/tools/cron_events.php</I> jede Minute auszuführen (ersetzen Sie <i>/var/www</i> durch das Root-Verzeichnis Ihrer Website).

Wenn der Aufruf der Funktion mail() fehlgeschlagen wurde, werden Sie die E-Mails von Ihrem Server mit Standardverfahren nicht versenden können.

Wenn Ihr Hosting-Anbieter alternative Services zum Versenden von E-Mails anbietet, können Sie diese via Funktion \"custom_mail\" benutzen. Bestimmen Sie diese Funktion in <i>/bitrix/php_interface/init.php</I>. Wird diese Funktion bestimmt, wird sie im System anstatt der PHP-Funktion \"mail\" mit denselben Ausgabeparametern benutzt.
";
$MESS["SC_HELP_CHECK_MAIL_BIG"] = "Beim Versenden einer umfangreichen Nachricht wird der Text der vorherigen Mail (Skript der Seitenprüfung) 10 Mal wiederholt. Darüber hinaus wird die Betreff-Zeile in zwei Zeilen aufgeteilt sowie das BCC-Feld zum Senden an noreply@bitrixsoft.com hinzugefügt.

Wenn der Server nicht korrekt konfiguriert ist, können solche Nachrichten nicht versendet werden.

Sollten etwaige Probleme entstehen, kontaktieren Sie Ihren Hosting-Anbieter. Wenn Sie das System auf einem lokalen Computer installieren, werden Sie den Server manuell konfigurieren müssen.
";
$MESS["SC_HELP_CHECK_MAIL_B_EVENT"] = "In der Datenbank-Tabelle B_EVENT werden die E-Mail-Warteschleifen von der Website gespeichert sowie die Aktivitäten zum Versenden der E-Mails registriert. Wenn einige Nachrichten nicht versendet werden können, sind mögliche Problemursachen eine ungültige Empfängeradresse, nicht korrekte Parameter der E-Mail-Vorlage oder das E-Mail-System des Servers.";
$MESS["SC_HELP_CHECK_MAIL_PUSH"] = "Die Funktion <a href=\"https://helpdesk.bitrix24.com/open/1612393/\" target=_blank>Aisrichtung der Nachricht</a> wird die Nachrichten aus E-Mails im Activity Stream veröffentlichen, sodass auch andere Nutzer, welche keinen Account in Ihrem Bitrix24 haben, an der Diskussion teilnehmen können.

Sie müssen DNS konfigurieren, damit Ihr Bitrix24 auch von außen erreichbar wird.";
$MESS["SC_HELP_CHECK_MBSTRING"] = "Für die Arbeit mit mehreren Sprachen ist das Modul mbstring erforderlich. 

Website-Codierung muss als ein Wert des Parameters default_charset angegeben werden. Zum Beispiel:

<b>default_charset=utf-8</b>

Inkorrekte Konfiguration wird verschiedene Probleme verursachen: Texte werden willkürlich abgeschnitten, der XML-Import und das Update-System wird nicht korrekt funktionieren usw.

Fügen Sie diesen Code zu <i>/bitrix/php_interface/dbconn.php</I> hinzu, um UTF-8 auf Ihrer Website zu aktivieren:
<code>define('BX_UTF', true);</code>
fügen Sie diesen Code zu <i>/bitrix/.settings.php</i> hinzu:
<code>'utf_mode' => 
  array (
    'value' => true,
    'readonly' => true,
  ),</code>";
$MESS["SC_HELP_CHECK_MEMORY_LIMIT"] = "Bei diesem Test wird ein extra PHP-Prozess erstellt, um eine Variable mit der schrittweise inkrementierten Größe zu generieren. Zum Schluss wird dadurch der Speicherumfang festgelegt, welcher für den PHP-Prozess verfügbar sein wird.

PHP bestimmt die Speichereinschränkungen in php.ini , indem der Parameter <b>memory_limit</b> eingestellt wird. Aber Sie sollten diesem Parameter nicht vertrauen, da auf den Hostings auch noch weitere Einschränkungen gesetzt werden können.

Der Test versucht, den Wert von <b>memory_limit</b> zu erhöhen, indem er den folgenden Code benutzt:
<code>ini_set(&quot;memory_limit&quot;, &quot;512M&quot;)</code>

Wenn der aktuelle Wert kleiner ist, fügen Sie die Zeile vom Code zu <i>/bitrix/php_interface/dbconn.php</i> hinzu.
";
$MESS["SC_HELP_CHECK_METHOD_EXISTS"] = "Das Skript schlägt fehl, wenn <i>method_exists</I> an einigen PHP-Versionen ausgeführt wird. Hier finden Sie nähere Informationen zu diesem Problem: <a href='http://bugs.php.net/bug.php?id=51425' target=_blank>http://bugs.php.net/bug.php?id=51425</a>
Als Problemlösung wird vorgeschlagen, einen andere PHP-Version zu installieren.
";
$MESS["SC_HELP_CHECK_MYSQL_BUG_VERSION"] = "Es gibt einige MySQL-Versionen, in denen Fehler enthalten sind, welche ein fehlerhaftes Funktionieren der Website verursachen können.
<b>4.1.21</b> - Sortierung funktioniert unter bestimmten Bedingungen nicht korrekt;
<b>5.0.41</b> - Die Funktion EXISTS funktioniert nicht korrekt; die Suchfunktionen lassen nicht korrekte Ergebnisse anzeigen;
<b>5.1.34</b> - Der Schritt auto_increment ist standardmäßig 2, während 1 erforderlich ist.
<b>5.1.66</b> - Falsche Zähler der Forenthemen. Als Ergebnis kann möglichweise die Seite des Nutzers fehlerhaft funktionieren.

Haben Sie bei Ihnen eine dieser MySQL-Versionen installiert, sollten Sie MySQL aktualisieren.";
$MESS["SC_HELP_CHECK_MYSQL_CONNECTION_CHARSET"] = "Hier werden die Codierung und der Verglich geprüft, welche bei Datenübertragung an den MySQL-Server verwendet werden.

Für eine Website in der Codierung <i>utf8</i> muss die Codierung <i>utf8</i> sein, und der Vergleich <i>utf8_unicode_ci</i>.Wenn die Website in der Codierung <i>cp1251</i> ist, muss die Verbindung auch diese Codierung nutzen.

Um die Codierung der Verbindung zu ändern, fügen Sie in <i>/bitrix/php_interface/after_connect_d7.php</i> den folgenden Code ein (ein Beispiel für <i>utf8</i>):
<code>\$connection = Bitrix\\Main\\Application::getConnection(); 
\$connection->queryExecute('SET NAMES &quot;utf8&quot;');</code>
Um den Vergleich genau einzustellen, fügen Sie <b>nach der Angabe der Codierung</b> folgenden Code ein:
<code>\$connection->queryExecute('SET collation_connection = &quot;utf8_unicode_ci&quot;');</code>
<b>Wichttig!</b>Nachdem neue Werte definiert werden, stellen Sie sicher, dass die Daten auf der Website korrekt angezeigt werden.
";
$MESS["SC_HELP_CHECK_MYSQL_DB_CHARSET"] = "Bei diesem Test wird geprüft, ob die Zeichenkodierung und alphabetische Sortierung der Datenbank mit denen der Verbindung übereinstimmen. MySQL verwendet diese Parameter, um neue Tabellen zu erstellen.



Solche Fehler, falls sie auftreten, können automatisch korrigiert werden, wenn der aktuelle Nutzer Schreibrechte für die Datenbank hat (ALTER DATABASE).
";
$MESS["SC_HELP_CHECK_MYSQL_MODE"] = "Der Parameter <i>sql_mode</i> bestimmt die Arbeitsweise von MySQL. Dieser Parameter kann Werte enthalten, die mit Bitrix nicht kompatibel sind. Um die standardmäßige Arbeitsweise auszuwählen, fügen Sie den folgenden Code hinzu: <i>/bitrix/php_interface/after_connect_d7.php</i>:
<code>\$connection = Bitrix\\Main\\Application::getConnection();
\$connection-&gt;queryExecute(&quot;SET sql_mode=''&quot;);
\$connection-&gt;queryExecute(&quot;SET innodb_strict_mode=0&quot;);</code>

Beachten Sie, dass Sie evtl. Datenbanknutzerprivileg SESSION_VARIABLES_ADMIN für MySQL 8.0.26 und höher benötigen werden. Ist Ihr aktuelles Privileg unzureichend, sollten Sie sich an den Administrator Ihrer Datenbank wenden oder die Konfigurationsdatei der MySQL bearbeiten.
";
$MESS["SC_HELP_CHECK_MYSQL_TABLE_CHARSET"] = "Die Zeichenkodierung aller Tabellen und Felder muss mit der Zeichenkodierung der datenbank übereinstimmen. Unterscheidet sich die Zeichenkodierung von irgendeiner der Tabellen, müssen Sie dies manuell mithilfe von SQL-Befehlen korrigieren.



Die alphabetische Sortierung der Tabellen muss mit der alphabetischen Sortierung der Datenbank übereinstimmen. Sind die Zeichenkodierungen korrekt konfiguriert, dann wird das Nichtübereinstimmen der alphabetischen Sortierungen automatisch korrigiert.



<b>Achtung!</b> Erstellen Sie immer eine komplette Sicherungskopie von der Datenbank, bevor Sie die Zeichenkodierung ändern.
";
$MESS["SC_HELP_CHECK_MYSQL_TABLE_STATUS"] = "In diesem Test werden die MySQL-üblichen Mechanismen zur Tabellenprüfung verwendet. Wird der Test eine oder mehrere beschädigte Tabellen feststellen, wird Ihnen vorgeschlagen, Korrekturen vorzunehmen.";
$MESS["SC_HELP_CHECK_MYSQL_TABLE_STRUCTURE"] = "Die Installationspakete der Module enthalten immer Informationen über die Struktur der Datenbanktabellen, die sie nutzen. Bei Updates können die Tabellenstruktur und die Moduldateien (Skripts) geändert werden.

Wenn die Modulskripts der aktuellen Tabellenstruktur nicht entsprechen, führt das zu den Systemfehlern.

Es können neue Datenbankindexe geben, die zu den neuen Produktpaketen hinzugefügt, aber nicht in die Updates eingeschlossen wurden. Das hängt damit zusammen, dass eine Aktualisierung des Systems inklusive Indexe sehr viel Zeit in Anspruch nimmt und oft Fehler verursacht.

Die Websiteüberprüfung erstellt eine Diagnose für die <b>installierten</b> Module und erstellt und/oder aktualisiert fehlende Indexe und Felder, um so die Datenintegrität sicherzustellen. Trotzdem werden Sie das Protokoll manuell überprüfen, wenn ein Feldtyp geändert wurde.
";
$MESS["SC_HELP_CHECK_MYSQL_TIME"] = "Hier werden die Systemzeiten der Datenbank und des Web-Servers verglichen. Diese können nicht synchron laufen, wenn sie auf zwei verschiedenen Maschinen installiert sind, aber öfter passiert das wegen einer nicht korrekten Einstellung der Zeitzone.

Die PHP-Zeitzone kann hier eingestellt werden: <i>/bitrix/php_interface/dbconn.php</i>, z.B.:
<code>date_default_timezone_set(&quot;Europe/Berlin&quot;);</code> (benutzen Sie Ihre Region und Stadt)

Die Datenbank-Zeitzone kann eingestellt werden, indem ein folgender Code hinzugefügt wird: <i>/bitrix/php_interface/after_connect_d7.php</i>:
<code>\$connection = Bitrix\\Main\\Application::getConnection(); 
\$connection->queryExecute(&quot;SET LOCAL time_zone='&quot;.date('P').&quot;'&quot;);</code>

Näheres entnehmen Sie der Seite http://en.wikipedia.org/wiki/List_of_tz_database_time_zones, um die Liste der standardmäßigen Regionen und Städte zu bekommen.
";
$MESS["SC_HELP_CHECK_NTLM"] = "Die <a href=\"http://en.wikipedia.org/wiki/Single_sign-on\">Single sign-on</a> Authentifizierung erfordert, dass der Web-Server ganz bestimmt konfiguriert ist und die NTLM-Authentifizierung ist im Intranet aktiviert und entsprechend konfiguriert.

Einstellung von NTLM auf Linux ist keine einfache Aufgabe, aber die <a href=\"http://www.bitrixsoft.com/products/virtual_appliance/\">Bitrix Virtual Appliance</a> ab Version 4.2. enthält auch diese Funktion und sie muss lediglich aktiviert werden.
";
$MESS["SC_HELP_CHECK_PCRE_RECURSION"] = "Der Parameter <i>pcre.recursion_limit</i> ist standardmäßig 100000. Wenn die Rekursion mehr Speicher verbraucht als die Stapelspeichergröße  es  ermöglicht (gewöhnlich 8 MB), wird PHP bei komplexen regulären Ausdrücken fehlschlagen und den Fehler <i>Segmentation fault</i> aufweisen.
Um das Limit der Stapelspeichergröße zu deaktivieren, muss das Skript zur Apache-Ausführung bearbeitet werden: <code>ulimit -s unlimited</code>
Bei FreeBSD müssen Sie PCRE mithilfe von der Option disable-stack-for-recursion erneut zusammenstellen.

Alternativ kann man den Wert von <i>pcre.recursion_limit</i> auf 1000 oder noch weniger reduzieren.
So wird das Fehlschlagen von PHP vermieden, aber es kann zu einem nicht immer korrekten Verhalten von Zeilenfunktionen führen: Z.B. können die Foren als erstes leere Beiträge anzeigen.
";
$MESS["SC_HELP_CHECK_PERF"] = "Hier wird die Server-Performance mithilfe des <a href=\"http://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=20&CHAPTER_ID=04955\">Performance-Monitors</a> geprüft.

Es wird die Anzahl leerer Seiten angezeigt, welche der Server pro Sekunde ausgeben kann. Das ist ein Kehrwert für die Zeit der Generierung einer Seite, welche nur die Verbindung zum Produktkernel enthält.

Die <a href=\"http://www.bitrix.de/products/virtual_appliance/\">Bitrix Virtual Appliance</a> wird mit ca. 30 Einheiten bewertet.

Wenn bei einer niedrigeren Serverlast eine niedrige Bewertung erhalten wurde, zeugt das von den etwaigen Konfigurationsfehlern. Wenn die Bewertung erst unter hoher Belastung niedrig wird, kann das von etwaigen Hardwaremangeln zeugen.
";
$MESS["SC_HELP_CHECK_PHP_MODULES"] = "Hier werden die für das System erforderlichen PHP-Erweiterungen geprüft. Fehlen einige solche Erweiterungen, dann werden die Module angezeigt, welche ohne diese Erweiterungen nicht funktionieren können.

Um fehlende PHP-Erweiterungen hinzuzufügen, kontaktieren Sie den Technischen Support Ihres Hosting-Anbieters. Wenn Sie das System auf einem lokalen Computer installieren, werden Sie diese Erweiterungen manuell installieren müssen. Benutzen Sie dafür Dokumentation auf php.net.
";
$MESS["SC_HELP_CHECK_PHP_SETTINGS"] = "Hier werden kritische Parameter geprüft, die in der Datei php.ini bestimmt werden. Bei Fehlern werden nicht korrekt eingestellte Parameter angezeigt. Eine detaillierte Parameterbeschreibung finden Sie auf php.net.";
$MESS["SC_HELP_CHECK_POST"] = "Hier wird eine POST-Anfrage mit mehreren Parametern gesendet. Einige Softwares, die den Server schützen, beispielsweise \"suhosin\", können ausführliche Anfragen blockieren. In diesem Fall können die Informationsblockelemente meistens nicht gespeichert werden.";
$MESS["SC_HELP_CHECK_PULL_COMMENTS"] = "Damit die Kommentare im Activity Stream gleich für alle Leser verfügbar werden, muss das Modul Push and Pull zusätzlich konfiguriert werden. Dafür muss auf Ihrem Nginx-Server das Modul push-stream-module installiert, und dann in den Moduleinstellungen des Moduls <a href=\"http://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=26&LESSON_ID=5144\">Push and Pull</a> aktiviert werden.

Die <a href=\"http://www.bitrixsoft.com/products/virtual_appliance/index.php\">Bitrix Virtual Appliance</a> unterstützt diese Funktion ab Version 4.2.
";
$MESS["SC_HELP_CHECK_PULL_STREAM"] = "Die Unterstützung seitens des Servers ist erforderlich für eine korrekte Arbeitsweise des Moduls <a href=\"http://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=26&LESSON_ID=5144\">Push and Pull</a>.

Dieses Modul ermöglicht eine Sofortzustellung von Nachrichten via Web-Messenger und Mobile Anwendung sowie eine sofortige Aktualisierung des Activity Streams.

Die <a href=\"http://www.bitrix.de/products/virtual_appliance /\">Bitrix Virtual Appliance</a> ab Version 4.2 unterstützt dieses Modul im vollen Umfang.
";
$MESS["SC_HELP_CHECK_PUSH_BITRIX"] = "Das Modul <a href=\"http://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=26&LESSON_ID=5144\">Push and Pull</a> ermöglicht eine Sofortübertragung von Nachrichten mit der Technologie Pull und das Senden von Benachrichtigungen an mobile Geräte mit der Technologie Push via <a href=\"http://www.bitrix.de/products/intranet/features/bitrixmobile.php\">Bitrix Mobile App</a>.";
$MESS["SC_HELP_CHECK_REST"] = "Das Modul Rest ist erforderlich, um externe Anwendungen zu integrieren und einige Anwendungen aus Bitrix24.Market zu starten. Um Ihre eigenen Anwendungen in Bitrix24 zu integrieren, folgen Sie bitte <a href=\"https://training.bitrix24.com/rest_help/\" target=\"_blank\">dieser Anleitung</a>.";
$MESS["SC_HELP_CHECK_SEARCH"] = "Das System kann nach Texten in Dokumenten von Open XML Format (eingeführt in Microsoft Office 2007) suchen. Damit auch andere Datei-Formate unterstützt werden können, müssen Pfade zu entsprechenden Anwendungen <a href=\"/bitrix/admin/settings.php?mid=intranet\">in den Einstellungen des Moduls Intranet angegeben werden</a>. Im anderen Fall wird das System lediglich nach Namen suchen.

Die <a href=\"http://www.1c-bitrix.ru/products/vmbitrix/index.php\">Bitrix Virtual Appliance</a> unterstützt das standardmäßig.
";
$MESS["SC_HELP_CHECK_SECURITY"] = "Das Apache-Modul  mod_security ist genauso wie das PHP-Modul suhosin dazu gedacht, die Website gegen Hacker zu schützen. In der Tat stört es aber meistens, normale Nutzeraktivitäten auszuführen. Es wird also empfohlen, das Standardmodul \"Proaktiver Schutz\" anstatt von mod_security zu benutzen.";
$MESS["SC_HELP_CHECK_SERVER_VARS"] = "Hier werden die Server-Variablen geprüft.

Der Wert von HTTP_HOST ist vom aktuellen virtuellen Host (der Domain) abgeleitet. Einige Browser können Cookies für nicht korrekte Domainnamen nicht speichern, weswegen auch eine Cookie-Autorisierung nicht möglich sein wird.
";
$MESS["SC_HELP_CHECK_SESSION"] = "Dieser Test prüft, ob der Server die Daten mithilfe von Sitzungen speichern kann. Das ist erforderlich, damit die Autorisierung zwischen den Hits verfügbar bleibt.

Dieser Test wird fehlschlagen, wenn auf dem Server die Unterstützung für Sitzungen nicht installiert ist, ein nicht gültiges Sitzungsverzeichnis angegeben ist oder wenn dieses Verzeichnis schreibgeschützt ist.
";
$MESS["SC_HELP_CHECK_SESSION_UA"] = "Hier wird die Fähigkeit geprüft, Sitzungen zu speichern, ohne dabei die http-Überschrift <i>User-Agent</i> einzustellen. 

Mehrere externe Anwendungen und Add-Ons stellen diese Überschrift nicht ein, beispielsweise Uploader für Dateien und Fotos, WebDav-Clients etc. 

Wenn der Test fehlschlägt, liegt das Problem höchstwahrscheinlich bei der nicht korrekten Konfiguration des PHP-Moduls <b>suhosin</b>.
";
$MESS["SC_HELP_CHECK_SITES"] = "Allgemeine Multisite-Parameter werden geprüft. Wenn für eine Website der Pfad zum Root-Verzeichnis angegeben ist (was nur dann erforderlich ist, wenn die Websites auf verschiedenen Domains existieren), muss dieses Verzeichnis eine symbolische Verlinkung zum beschreibbaren \"bitrix\" Ordner enthalten.

Alle Websites, die mit demselben Bitrix System eingerichtet wurden, müssen dieselbe Kodierung benutzen: Entweder UTF-8 oder Einzelbyte-Kodierung.
";
$MESS["SC_HELP_CHECK_SOCKET"] = "Hier wird der Web-Server eingestellt, um die Verbindung mit sich selbst herstellen zu können. Dies ist erforderlich, um die Netzwerk-Funktionen zu prüfen und einige nachfolgende Tests durchzuführen.

Wenn dieser Test fehlschlägt, können nachfolgende Tests, bei denen ein extra PHP-Prozess benötigt wird, nicht durchgeführt werden. If this test fails, the subsequent tests requiring a child PHP process cannot be performed. Dieses Problem kann durch eine Firewall, einen beschränkten IP-Zugriff oder eine HTTP/HTLM Autorisierung verursacht werden. Deaktivieren Sie diese Funktionen, wenn Sie den Test durchführen.
";
$MESS["SC_HELP_CHECK_SOCKET_SSL"] = "Die verschlüsselte Verbindung zum Server erfolgt via <a href=\"http://de.wikipedia.org/wiki/HTTPS\">HTTPS</a>. Damit die Verbindung auch wirklich sicher ist, muss man über ein gültiges SSL-Zertifikat verfügen.

Die Gültigkeit des Zertifikats setzt voraus, dass es von der Zertifizierungsstelle überprüft wurde und dem aktuellen Server auch wirklich gehört. Ein solches Zertifikat kann man über eigenen Hosting-Provider kaufen.

Wenn die Arbeit mit dem Intranet via HTTPS läuft und das Zertifikat dabei nicht bestätigt ist, können Probleme mit externer Software auftreten, z.B. bei der Anbindung der Netzwerklaufwerke via WebDav oder bei der Integration mit Outlook.
";
$MESS["SC_HELP_CHECK_SOCNET"] = "Um die Nachrichten aus den sozialen Netzwerken zu bekommen, muss das Modul Soziale Services konfiguriert werden, und zwar so, dass für jeden Service, der genutzt wird, ein Authentifizierungsschlüssel angegeben werden soll.";
$MESS["SC_HELP_CHECK_TURN"] = "Damit die Videoanrufe funktionieren, muss zwischen Browsern entsprechender Nutzer Verbindung hergestellt werden. Wenn die Nutzer dabei von verschiedenen Netzwerken aus arbeiten - z.B. sie sitzen in verschiedenen Büros, sodass eine direkte Verbindung nicht möglich ist, wird ein spezieller TURN-Server benötigt, um Verbindung herzustellen.

Bitrix24 bietet kostenlos einen vorkonfigurierten TURN-Server unter turn.calls.bitrix24.com. 

Alternativ können Sie Ihren eigenen Server konfigurieren und die Server-URL in den Einstellungen des Moduls Web Messenger angeben.
";
$MESS["SC_HELP_CHECK_UPDATE"] = "Hier wird versucht, mithilfe von den aktuellen Einstellungen des Hauptmoduls eine Testverbindung zum Update-Server herzustellen. Kann die Verbindung nicht hergestellt werden, werden Sie die Updates nicht installieren oder Ihre Testversion aktivieren können.

Die wahrscheinlichen Problemursachen  dafür sind nicht korrekte Proxy-Einstellungen, Firewall-Einschränkungen oder ungültige Netzwerk-Einstellungen des Servers.
";
$MESS["SC_HELP_CHECK_UPLOAD"] = "Hier wird versucht, die Verbindung mit dem Web-Server herzustellen und binäre Daten als eine Datei zu übertragen. Der Server wird dann die erhaltenen Daten mit den ursprünglichen vergleichen. Wird ein Problem entstehen, so kann es durch einige Parameter in <i>php.ini</I> verursacht werden, denn diese Datei lässt Übertragung von binären Daten nicht zu, oder durch einen nicht verfügbaren temporären Ordner (oder <i>/bitrix/tmp</i>).

Soll das Problem auftreten, kontaktieren Sie Ihren Hosting-Anbieter. Wenn Sie das System auf einem lokalen Computer installieren, werden Sie den Server manuell konfigurieren müssen.
";
$MESS["SC_HELP_CHECK_UPLOAD_BIG"] = "Hier wird eine große binäre Datei (über 4 Mb) hochgeladen. Wenn nun dieser Test fehlschlägt, der vorherige jedoch erfolgreich war, kann das Problem in der Einschränkung in php.ini (<b>post_max_size</b> oder <b>upload_max_filesize</b>) liegen. Benutzen Sie phpinfo, um aktuelle Werte zu setzen (Einstellungen - Tools - Konfiguration - PHP Einstellungen).

Auch ein unzureichender Festplattenspeicher kann dieses Problem verursachen.
";
$MESS["SC_HELP_CHECK_UPLOAD_RAW"] = "Sendet Binärdaten im Körper einer POST-Anfrage. Auf der Serverseite können diese Daten manchmal beschädigt werden: In diesem Fall wird der Flach-Lader für Bilder nicht funktionieren.";
$MESS["SC_HELP_CHECK_WEBDAV"] = "<a href=\"http://en.wikipedia.org/wiki/WebDAV\">WebDAV</a> ist ein Protokoll, das einem Nutzer erlaubt, Dokumente in Microsoft Office direkt im Intranet zu öffnen, zu bearbeiten und zu speichern, ohne sie also herunterladen bzw. hochzuladen zu müssen. Eine erforderliche Voraussetzung ist dabei, dass der Server, auf welchem das Intranet installiert ist, die WebDAV-Anfragen an PHP Scripts genauso sendet, wie er sie empfängt, also ohne Änderungen. Blockiert der Server diese Anfragen, ist die direkte Bearbeitung nicht möglich.

Außerdem kann auch auf der Client-Seite eine zusätzliche Konfiguration <a href=\"http://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=27&LESSON_ID=1466#office\">erforderlich sein</a>, um direkte Bearbeitung zu unterstützen, und es besteht keine Möglichkeit, diese Konfiguration per Fernzugriff zu prüfen.
";
$MESS["SC_HELP_NOTOPIC"] = "Zu diesem Thema gibt es leider keine Informationen.";
$MESS["SC_MBSTRING_NA"] = "Prüfung ist wegen der UTF-Konfigurationsfehler fehlgeschlagen";
$MESS["SC_MB_NOT_UTF"] = "Die Website funktioniert in der Einzelbyte-Kodierung";
$MESS["SC_MB_UTF"] = "Die Website funktioniert in der UTF-Kodierung";
$MESS["SC_MEMORY_CHANGED"] = "Der Wert von memory_limit wurde erhöht von #VAL0# auf #VAL1#, während beim Testen ini_set genutzt wurde.";
$MESS["SC_MOD_GD"] = "GD Library";
$MESS["SC_MOD_GD_JPEG"] = "Unterstützung für JPEG in GD";
$MESS["SC_MOD_JSON"] = "JSON kompatibel";
$MESS["SC_MOD_MBSTRING"] = "Mbstring-Unterstützung";
$MESS["SC_MOD_PERL_REG"] = "Unterstützung für reguläre Ausdrücke (Perl kompatibel)";
$MESS["SC_MOD_XML"] = "XML Unterstützung";
$MESS["SC_MYSQL_ERR_VER"] = "Aktuell ist die MySQL-Version #CUR# installiert, erforderlich ist jedoch #REQ#.";
$MESS["SC_NOT_FILLED"] = "Problembeschreibung erforderlich.";
$MESS["SC_NOT_LESS"] = "Nicht kleiner als #VAL# M.";
$MESS["SC_NO_PROXY"] = "Kann keine Verbindung zum Proxyserver aufbauen.";
$MESS["SC_NO_ROOT_ACCESS"] = "Kein Zugriff auf den Ordner ";
$MESS["SC_NO_TMP_FOLDER"] = "Temporärer Ordner existiert nicht.";
$MESS["SC_PATH_FAIL_SET"] = "Der Pfad zum Website-Root muss leer sein, der aktuelle Pfad ist:";
$MESS["SC_PCRE_CLEAN"] = "Lange Textzeilen können wegen Systemeinschränkungen eine nicht korrekte Verarbeitung verursachen.";
$MESS["SC_PORTAL_WORK"] = "Intranet-Funktionsfähigkeit";
$MESS["SC_PORTAL_WORK_DESC"] = "Prüfung der Intranet-Funktionsfähigkeit";
$MESS["SC_PROXY_ERR_RESP"] = "Ungülgige Antwort des Updateservers wegen dem Proxy";
$MESS["SC_READ_MORE_ANC"] = "Mehr Informationen finden Sie im <a href=\"#LINK#\" target=_blank>Protokoll der Systemüberprüfung</a>.";
$MESS["SC_RUS_L1"] = "Nachricht von der Seite";
$MESS["SC_SEC"] = "Sek.";
$MESS["SC_SENT"] = "Gesendet in:";
$MESS["SC_SITE_CHARSET_FAIL"] = "Gemischte Zeichensätze: UTF-8 und nicht UTF-8";
$MESS["SC_SOCKET_F"] = "Sockelsupport";
$MESS["SC_SOCK_NA"] = "Prüfung ist wegen Socket-Fehler fehlgeschlagen.";
$MESS["SC_START_TEST_B"] = "Test starten";
$MESS["SC_STOP_B"] = "Stop";
$MESS["SC_STOP_TEST_B"] = "Stop";
$MESS["SC_STRLEN_FAIL_PHP56"] = "String-Funktionen arbeiten nicht korrekt.  ";
$MESS["SC_STRTOUPPER_FAIL"] = "Die Zeilenfunktionen strtoupper und strtolower liefern inkorrekte Ergebnisse";
$MESS["SC_SUBTITLE_DISK"] = "Festplattenzugriffs-Überprüfung";
$MESS["SC_SUBTITLE_DISK_DESC"] = "Die Skripte müssen Schreibzugriff auf alle Dateien haben. Dies ist für die ordnungsgemäße Funktion des Datei-Managers, des Uploads und des Update-Systems erforderlich.";
$MESS["SC_SUPPORT_COMMENT"] = "Wenn Sie Probleme mit dem Nachrichtenversand haben, verwenden Sie sich bitte die Kontaktform auf unserer Website:";
$MESS["SC_SWF_WARN"] = "Einfügen der SWF-Objekte wird eventuell nicht funktionieren.";
$MESS["SC_SYSTEM_TEST"] = "Systemtest";
$MESS["SC_TABLES_NEED_REPAIR"] = "Tabellenintegrität ist verletzt, eine Korrektur ist erforderlich.";
$MESS["SC_TABLE_BROKEN"] = "Die Tabelle &quot;#TABLE#&quot; wurde infolge von einem internen MySQL Fehler zerstört. Automatische Wiederherstellung wird eine leere Tabelle erstellen.";
$MESS["SC_TABLE_CHARSET_WARN"] = "Die &quot;#TABLE#&quot;-Tabelle enthält Felder, die in der Kodierung nicht mit der Datenbankkdierung übereinstimmen. ";
$MESS["SC_TABLE_CHECK_NA"] = "Prüfung ist wegen eines Fehlers der Datenbank-Zeichenkodierung fehlgeschlagen.";
$MESS["SC_TABLE_COLLATION_NA"] = "Überprüfung wurde wegen Fehler der Tabellenkodierung nicht durchgeführt";
$MESS["SC_TABLE_ERR"] = "Fehler in der Tabelle #VAL#:";
$MESS["SC_TABLE_SIZE_WARN"] = "Die Größe der &quot;#TABLE#&quot;-Tabelle ist möglicherweise zu groß (#SIZE# M).";
$MESS["SC_TAB_2"] = "Zugriffsüberprüfung";
$MESS["SC_TAB_5"] = "Technischer Support";
$MESS["SC_TESTING"] = "Überprüfung läuft...";
$MESS["SC_TESTING1"] = "Wird getestet...";
$MESS["SC_TEST_CONFIG"] = "Konfigurationsprüfung";
$MESS["SC_TEST_DOMAIN_VALID"] = "Die aktuelle Domain ist ungültig (#VAL#). Der Domainname kann nur Ziffern, lateinische Buchstaben und Bindestriche enthalten. Die Top-Level-Domain muss durch einen Punkt getrennt werden (z.B. .com).";
$MESS["SC_TEST_FAIL"] = "Üngültiger Serverantwort. Der Test kann nicht abgeschlossen werden.";
$MESS["SC_TEST_START"] = "Test starten";
$MESS["SC_TEST_SUCCESS"] = "Erfolg";
$MESS["SC_TEST_WARN"] = "Der Konfigurationsbericht wird abgeschlossen.
Wenn Fehler auftreten, bitte entfernen Sie die Markierung \"Testlog senden\", und versuchen Sie es noch ein mal.";
$MESS["SC_TIK_ADD_TEST"] = "Testlog senden";
$MESS["SC_TIK_DESCR"] = "Problembeschreibung";
$MESS["SC_TIK_DESCR_DESCR"] = "Folge der Arbeitsschritte, die den Fehler verursachten, Fehlerbeschreibung,... ";
$MESS["SC_TIK_LAST_ERROR"] = "Letzte Fehlermeldung";
$MESS["SC_TIK_LAST_ERROR_ADD"] = "angehängt";
$MESS["SC_TIK_SEND_MESS"] = "Nachricht senden";
$MESS["SC_TIK_SEND_SUCCESS"] = "Die Nachricht wurde erfolgreich gesendet. Bitte prüfen Sie Ihr E-Mail-Postfach #EMAIL# ,um die Bestätigungsmail des Support-Teams zu lesen.";
$MESS["SC_TIK_TITLE"] = "E-Mail an den Technischen Support senden";
$MESS["SC_TIME_DIFF"] = "Der Zeitunterschied beträgt #VAL# Sekunden.";
$MESS["SC_TMP_FOLDER_PERMS"] = "Unzureichende Zugriffsberechtigung, um im temporären Ordner zu schreiben.";
$MESS["SC_T_APACHE"] = "Web-Servermodule";
$MESS["SC_T_AUTH"] = "HTTP-Autorisierung";
$MESS["SC_T_CACHE"] = "Cache-Dateien verwenden";
$MESS["SC_T_CHARSET"] = "Datenbanktabellenzeichensatz";
$MESS["SC_T_CHECK"] = "Tabellenprüfung";
$MESS["SC_T_CLONE"] = "Objekt-Übergabe mit dem Verweis";
$MESS["SC_T_DBCONN"] = "Redundante Ausgabe in den Konfigurationsdateien";
$MESS["SC_T_DBCONN_SETTINGS"] = "Verbindungsparameter für die Datenbank";
$MESS["SC_T_EXEC"] = "Dateierstellung und Ausführung";
$MESS["SC_T_GETIMAGESIZE"] = "Getimagesize-Unterstützung für SWF";
$MESS["SC_T_INSTALL_SCRIPTS"] = "Service-Skripts im Website-Root";
$MESS["SC_T_MAIL"] = "E-Mail wird gesendet";
$MESS["SC_T_MAIL_BIG"] = "Große E-Mail wird gesendet (über 64 KB)";
$MESS["SC_T_MAIL_B_EVENT"] = "Auf ungesendete Nachrichten überprüfen";
$MESS["SC_T_MAIL_B_EVENT_ERR"] = "Fehler beim Versand von Systemnachrichten. Folgende Nachrichten wurden nicht gesendet:";
$MESS["SC_T_MBSTRING"] = "Parameter der UTF-Konfiguration (mbstring und BX_UTF)";
$MESS["SC_T_MEMORY"] = "Speicherlimit";
$MESS["SC_T_METHOD_EXISTS"] = "method_exists in der Zeile ausführen";
$MESS["SC_T_MODULES"] = "Erforderliche PHP-Module";
$MESS["SC_T_MYSQL_VER"] = "MySQL-Version";
$MESS["SC_T_PHP"] = "Erforderliche PHP-Parameter";
$MESS["SC_T_POST"] = "POST-Anfragen mit mehreren Parametern";
$MESS["SC_T_RECURSION"] = "Größe des Stapelspeichers; pcre.recursion_limit";
$MESS["SC_T_REDIRECT"] = "Lokale Weiterleitungen (LocalRedirect-Funktion)";
$MESS["SC_T_SERVER"] = "Server-Variablen";
$MESS["SC_T_SESS"] = "Sitzung beibehalten";
$MESS["SC_T_SESS_UA"] = "Sitzung beibehalten ohne NutzerAgent";
$MESS["SC_T_SITES"] = "Website-Parameter";
$MESS["SC_T_SOCK"] = "Sockel verwenden";
$MESS["SC_T_SQL_MODE"] = "MySQL-Modus";
$MESS["SC_T_STRUCTURE"] = "Datenbank-Struktur";
$MESS["SC_T_TIME"] = "Datenbank- und Webserverzeiten";
$MESS["SC_T_UPLOAD"] = "Datei hochladen";
$MESS["SC_T_UPLOAD_BIG"] = "Über 4 Mb große Dateien hochladen";
$MESS["SC_T_UPLOAD_RAW"] = "Datei hochladen via php://input";
$MESS["SC_UPDATE_ACCESS"] = "Der Zugriff zum Update-Server";
$MESS["SC_UPDATE_ERROR"] = "Keine Verbindung zum Update-Server.";
$MESS["SC_UPDATE_ERR_RESP"] = "Ungültige Antwort des Updateservers.";
$MESS["SC_VER_ERR"] = "Die PHP-Version ist #CUR#, erforderlich ist jedoch #REQ# oder höher.";
$MESS["SC_WARN"] = "nicht konfiguriert";
$MESS["SC_WARNINGS_FOUND"] = "Es wurden keine Fehler entdeckt, aber es gibt Warnungen.";
$MESS["SC_WARN_DAV"] = "WebDav ist deaktiviert, weil das Modul mod_dav/mod_dav_fs geladen wird.";
$MESS["SC_WARN_SECURITY"] = "Der mod_security-Modul wurde geladen, es können Probleme mit dem administrativen Panel auftreten.";
$MESS["SC_WARN_SUHOSIN"] = "Der Suhosin-Modul wurde geladen, es können Probleme mit dem administrativen Panel auftreten.";
