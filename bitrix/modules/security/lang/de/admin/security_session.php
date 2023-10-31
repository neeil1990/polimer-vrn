<?php
$MESS["SEC_SESSION_ADMIN_DB_BUTTON_OFF"] = "Das Speichern der Session-Daten in der Datenbank deaktivieren";
$MESS["SEC_SESSION_ADMIN_DB_BUTTON_ON"] = "Session-Daten in der Datenbank speichern";
$MESS["SEC_SESSION_ADMIN_DB_NOTE"] = "<p>Die meisten Angriffe auf Web-Anwendungen haben als Ziel, an die Session-Daten eines Users zu gelangen. Der <b>Session-Schutz</b> macht die Übernahme von Sitzungsdaten, vor allem die der Administratoren unmöglich.</p><p><b>Session-Schutz</b> erweitert die standardisierten Einstellungen im System und beinhaltet:<ul style=\"font-size:100%\"><li>Austausch der Sitzungs-ID mehrmals pro Sitzung</li><li>Session-Daten werden in der Modul-Datenbanktabelle gespeichert</li></ul><p>Session-Daten werden in der Modul-Datenbanktabelle gespeichert, so können diese Daten durch den Einsatz von anderen Skripten auf dem gleichen Server nicht gelesen werden. Konfigurationsfehler des virtuellen Hostings oder Fehler beim Verteilen der Zugriffsrechte auf temporäre Dateien und andere Konfigurationsfehler haben keine Auswirkung auf die Sicherheit der Session-Daten.Außerdem wird die Belastung zwischen dem File-Server und der Datenbank verteilt.</p><p><i>Empfohlen für die hohe Sicherheitsstufe</i></p>";
$MESS["SEC_SESSION_ADMIN_DB_NOTE_V2"] = "
<p>Wenn eine Sitzung in der Datenbank, Redis oder Memcache gespeichert wird, und nicht in den Dateien, wird dadurch ein Zugriff auf diese Daten von einem Script verhindert, das auf anderen virtuellen Servern gehostet werden kann; außerdem hilft es dabei, etwaige Konflikte der Zugriffsrechte bzw. der Serverkonfiguration zu vermeiden. Die Last auf das Dateisystem wird dadurch reduziert, weil die Anfragen an einen Datenbankserver, Redis oder Memcache umgeleitet werden.</p>

<p>Um die Parameter des Sitzungsspeichers zu ändern, werden Sie die Datei <b>.settings.php</b> wie <a href='https://training.bitrix24.com/support/training/course/index.php?COURSE_ID=68&CHAPTER_ID=05962&LESSON_PATH=5936.5959.5962'>hier</a> beschrieben bearbeiten müssen.</p>

<p>Neben den Sicherheitsmaßnahmen können Sie einstellen, dass sich die Sitzungs-ID in einem bestimmten Minutenintervall ändern wird.</p>

<p><i>Es wird für einen hohen Sicherheitslevel empfohlen.</i></p>";
$MESS["SEC_SESSION_ADMIN_DB_OFF"] = "Session-Daten werden nicht in der Modul-Datenbank gespeichert.";
$MESS["SEC_SESSION_ADMIN_DB_ON"] = "Session-Daten werden in der Modul-Datenbank gespeichert.";
$MESS["SEC_SESSION_ADMIN_DB_WARNING"] = "Achtung! Beim Modus-Wechsel werden alle Sessions gelöscht. Alle User müssen sich neu anmelden.";
$MESS["SEC_SESSION_ADMIN_SAVEDB_TAB"] = "In der Datenbank speichern";
$MESS["SEC_SESSION_ADMIN_SAVEDB_TAB_TITLE_V2"] = "Einstellungen des Speichers der Nutzersitzungen";
$MESS["SEC_SESSION_ADMIN_SAVEDB_TAB_V2"] = "Einstellungen des Sitzungsspeichers";
$MESS["SEC_SESSION_ADMIN_SESSID_BUTTON_OFF"] = "ID Wechsel deaktivieren";
$MESS["SEC_SESSION_ADMIN_SESSID_BUTTON_ON"] = "ID-Wechsel aktivieren";
$MESS["SEC_SESSION_ADMIN_SESSID_NOTE"] = "<p>Wenn diese Funktion aktiviert ist, wird die Sitzungs-ID innerhalb des vorgegebenen Zeitraums erneuert. Der Server wird dabei zusätzlich belastet, Session-Diebstahl ist jedoch zwecklos.</p><p><i>Empfohlen für die hohe Sicherheitsstufe.</o></p>";
$MESS["SEC_SESSION_ADMIN_SESSID_OFF"] = "Der Wechsel der Sitzungs-ID ist deaktiviert.";
$MESS["SEC_SESSION_ADMIN_SESSID_ON"] = "Der Wechsel der Sitzungs-ID ist aktiviert.";
$MESS["SEC_SESSION_ADMIN_SESSID_TAB"] = "ID Wechsel";
$MESS["SEC_SESSION_ADMIN_SESSID_TAB_TITLE"] = "Parameter für den Wechsel der Sitzungs-ID konfigurieren";
$MESS["SEC_SESSION_ADMIN_SESSID_TTL"] = "ID-Lebensdauer in Sekunden";
$MESS["SEC_SESSION_ADMIN_SESSID_WARNING"] = "Die Sitzungs-ID ist nicht kompatibel. Die Sitzungs-ID, die von der Funktion session_id zurückgegeben wird, darf 32 Zeichen nicht überschreiten, nur aus lateinischen Buchstaben und Ziffern bestehen.";
$MESS["SEC_SESSION_ADMIN_STORAGE_IN_FILES"] = "Sitzungsdaten werden in den Dateien gespeichert.";
$MESS["SEC_SESSION_ADMIN_STORAGE_NAME_TYPE_DATABASE"] = "Datenbank";
$MESS["SEC_SESSION_ADMIN_STORAGE_NAME_TYPE_FILE"] = "Dateien";
$MESS["SEC_SESSION_ADMIN_STORAGE_NAME_TYPE_MEMCACHE"] = "Memcache";
$MESS["SEC_SESSION_ADMIN_STORAGE_NAME_TYPE_REDIS"] = "Redis";
$MESS["SEC_SESSION_ADMIN_STORAGE_WITH_SESSION_DATA"] = "Sitzungsdaten werden in #NAME# gespeichert.";
$MESS["SEC_SESSION_ADMIN_TITLE"] = "Sitzungsschutz";
