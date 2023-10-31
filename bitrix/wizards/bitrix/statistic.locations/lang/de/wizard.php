<?
$MESS["STATWIZ_NO_MODULE_ERROR"] = "Das Web Analytics Modul ist nicht installiert. Der Assistent kann nicht ausgeführt werden.";
$MESS["STATWIZ_FILES_NOT_FOUND"] = "Keine Dateien gefunden. Laden Sie Dateien von www.maxmind.com oder ip-to-country.webhosting.info in den definierten Ordner, und starten Sie der Assistenten nochmal.";
$MESS["STATWIZ_STEP1_TITLE"] = "IP-Adressenindexierung-Asistent";
$MESS["STATWIZ_STEP1_CONTENT"] = "Wilkommen beim IP-Adressenindexierung-Asistent! Dieser Assistent hilft Ihnen bei der Erstellung des IP-Adressenindex für Länder und Städte.<br />Aktion auswählen:";
$MESS["STATWIZ_STEP1_COUNTRY"] = "IP-Adressenindex für die <b>Stadt</b>.";
$MESS["STATWIZ_STEP1_CITY"] = "IP-Adressenindex für die <b>Stadt</b> und das <b>Land</b>.";
$MESS["STATWIZ_STEP1_COUNTRY_NOTE_V2"] = "Unterstützte Formate:
<ul>
<li><a target=\"_blank\" href=\"#GEOIP_HREF#\">GeoIP Country</a>.</li>
<li><a target=\"_blank\" href=\"#GEOIPLITE_HREF#\">GeoLite Country</a>.</li>
</ul>";
$MESS["STATWIZ_STEP1_CITY_NOTE"] = "Unterstützte Formate:
<ul>
<li><a target=\"_blank\" href=\"#GEOIP_HREF#\">GeoIP City</a>.</li>
<li><a target=\"_blank\" href=\"#GEOIPLITE_HREF#\">GeoLite City</a>.</li>
<li><a target=\"_blank\" href=\"#IPGEOBASE_HREF#\">IpGeoBase</a>.</li>
</ul>";
$MESS["STATWIZ_STEP1_COMMON_NOTE"] = "Bitte Archive entpacken und Dateien in das #PATH# Katalog hochlagen. Danach können Sie zum nächsten Schritt übergehen.";
$MESS["STATWIZ_STEP2_TITLE"] = "Auswahl der CSV-Dateien";
$MESS["STATWIZ_STEP2_COUNTRY_CHOOSEN"] = "Sie haben den IP-Adressenindex für das <b>Land</b> gewählt.";
$MESS["STATWIZ_STEP2_CITY_CHOOSEN"] = "Sie haben den IP-Adressenindex für das <b>Land</b> und die <b>Stadt</b>gewählt.";
$MESS["STATWIZ_STEP2_CONTENT"] = "Suche im Ordner \"/bitrix/modules/statistic/ip2country\" ausgeführt.";
$MESS["STATWIZ_STEP2_FILE_NAME"] = "Dateiname";
$MESS["STATWIZ_STEP2_FILE_SIZE"] = "Größe";
$MESS["STATWIZ_STEP2_DESCRIPTION"] = "Beschreibung ";
$MESS["STATWIZ_STEP2_FILE_TYPE_MAXMIND_IP_COUNTRY"] = "GeoIP Country oder GeoLite Country Datenbank.";
$MESS["STATWIZ_STEP2_FILE_TYPE_IP_TO_COUNTRY"] = "ip-to-country Datenbank.";
$MESS["STATWIZ_STEP2_FILE_TYPE_MAXMIND_IP_LOCATION"] = "Zweiter Teil der IP-Bereiche der GeoIP City oder GeoLite City Datenbank. Muss nach dem ersten Teil geladen werden.";
$MESS["STATWIZ_STEP2_FILE_TYPE_MAXMIND_CITY_LOCATION"] = "Erster Teil der IP-Bereiche der GeoIP City oder GeoLite City Datenbank. Muss zuerst geladen werden.";
$MESS["STATWIZ_STEP2_FILE_TYPE_IPGEOBASE"] = "IP-Bereiche der IpGeoBase Datenbank (Nur Russland). Der Länderindex muss zuerst geladen werden.";
$MESS["STATWIZ_STEP2_FILE_TYPE_UNKNOWN"] = "Unbekanntes Format.";
$MESS["STATWIZ_STEP2_FILE_ERROR"] = "Keine Datei zum Upload.";
$MESS["STATWIZ_STEP3_TITLE"] = "Index wurde erstellt.";
$MESS["STATWIZ_STEP3_LOADING"] = "Bearbeitung läuft...";
$MESS["STATWIZ_FINALSTEP_TITLE"] = "Assistent abgeschlossen";
$MESS["STATWIZ_FINALSTEP_BUTTONTITLE"] = "Schließen";
$MESS["STATWIZ_FINALSTEP_COUNTRIES"] = "Länder: #COUNT#. ";
$MESS["STATWIZ_FINALSTEP_CITIES"] = "Städte: #COUNT#. ";
$MESS["STATWIZ_FINALSTEP_CITY_IPS"] = "IP-Bereiche: #COUNT#. ";
$MESS["STATWIZ_CANCELSTEP_TITLE"] = "Assistent abgebrochen";
$MESS["STATWIZ_CANCELSTEP_BUTTONTITLE"] = "Schließen";
$MESS["STATWIZ_CANCELSTEP_CONTENT"] = "Assistent abgebrochen.";
$MESS["STATWIZ_STEP2_FILE_TYPE_IPGEOBASE2"] = "Der zweite Teil der Datenbank IpGeoBase. Hier sind Übereinstimmungen der IP-Adressen mit den Ortsbestimmungen enthalten. Dieser Teil muss nach dem ersten Teil geladen werden.";
$MESS["STATWIZ_STEP2_FILE_TYPE_IPGEOBASE2_CITY"] = "Der erste Teil der Datenbank IpGeoBase. Hier sind Ortsbestimmungen enthalten. Laden Sie zuerst den Länderindex, um die Länder zu bestimmen.";
?>