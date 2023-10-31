<?
$MESS["BP_DBLA_NAME"] = "Zweistufige Bestätigung";
$MESS["BP_DBLA_DESC"] = "Empfohlen für Situationen, wenn über ein Dokument erst nach einer Expertenbewertung abgestimmt wird. Während der ersten Prozessstufe wird das Dokument durch die Experten bewertet. Wenn einer der Experten das Dokument ablehnt, geht Dokument an den Verfasser zurück. Wird es positiv gewertet, geht es an die endgültige Abstimmung durch die Gruppe ausgewählter Mitarbeiter durch einfache Mehrheit. Wenn die endgültige Abstimmung scheitert geht das Dokument wieder an den Verfasser zurück.";
$MESS["BP_DBLA_T"] = "Regelmäßiger Workflow";
$MESS["BP_DBLA_TASK"] = "Dokument \"{=Document:NAME}\" genehmigen";
$MESS["BP_DBLA_TASK_DESC"] = "Sie müssen das Dokument \"\"{=Document:NAME}\"\" bestätigen oder ablehnen.

Klicken Sie auf den Link um fortzuführen: #BASE_HREF##TASK_URL#

Autor: {=Document:CREATED_BY}";
$MESS["BP_DBLA_M"] = "E-Mail-Nachricht";
$MESS["BP_DBLA_APPROVE"] = "Bitte Dokument bestätigen oder ablehnen.";
$MESS["BP_DBLA_APPROVE_TEXT"] = "Sie müssen das Dokument \"\"{=Document:NAME}\"\" bestätigen oder ablehnen.

Autor: {=Document:CREATED_BY}";
$MESS["BP_DBLA_APPROVE_TITLR"] = "Genehmigung des Dokuments: Stufe1";
$MESS["BP_DBLA_S_1"] = "Reihenfolge der Aktivitäten";
$MESS["BP_DBLA_MAIL_SUBJ"] = "Das Dokument ist die erste Stufe durchlaufen";
$MESS["BP_DBLA_MAIL_TEXT"] = "Das Dokument \"\"{=Document:NAME}\"\" hat die erste Bestätigungsstufe absolviert.

Das Dokument wurde bestätigt.

{=ApproveActivity1:Comments}";
$MESS["BP_DBLA_MAIL2_SUBJ"] = "Bitte stimmen Sie für \"{=Document:NAME}\" ab";
$MESS["BP_DBLA_MAIL2_TEXT"] = "Sie müssen das Dokument \"\"{=Document:NAME}\"\" bestätigen oder ablehnen.

Klicken Sie auf den Link um fortzuführen: #BASE_HREF##TASK_URL#

Autor: {=Document:CREATED_BY}";
$MESS["BP_DBLA_APPROVE2"] = "Bitte Dokument bestätigen oder ablehnen.";
$MESS["BP_DBLA_APPROVE2_TEXT"] = "Sie müssen das Dokument \"\"{=Document:NAME}\"\" bestätigen oder ablehnen.

Autor: {=Document:CREATED_BY}";
$MESS["BP_DBLA_APPROVE2_TITLE"] = "Genehmigung des Dokuments: Stufe1";
$MESS["BP_DBLA_MAIL3_SUBJ"] = "Abstimmung von on \"{=Document:NAME}: Das Dokument hat passiert.";
$MESS["BP_DBLA_MAIL3_TEXT"] = "Abstimmung von \"\"{=Document:NAME}\"\" ist abgeschlossen.

Das Dokument wurde bestätigt durch {=ApproveActivity2:ApprovedPercent}% aller Stimmen.

Bestätigt: {=ApproveActivity2:ApprovedCount}
Abgelehnt: {=ApproveActivity1:NotApprovedCount}\"

{=ApproveActivity2:Comments}";
$MESS["BP_DBLA_APP"] = "Genehmigt";
$MESS["BP_DBLA_APP_S"] = "Status: Bestätigt";
$MESS["BP_DBLA_PUB_TITLE"] = "Dokument veröffentlichen";
$MESS["BP_DBLA_NAPP"] = "Abstimmung von on \"{=Document:NAME}: Das Dokument wurde abgelehnt.";
$MESS["BP_DBLA_NAPP_TEXT"] = "Abstimmung von \"\"{=Document:NAME}\"\" ist abgeschlossen.

Das Dokument wurde abgelehnt.

Bestätigt: {=ApproveActivity2:ApprovedCount}
Abgelehnt: {=ApproveActivity2:NotApprovedCount}\"

{=ApproveActivity2:Comments}";
$MESS["BP_DBLA_NAPP_DRAFT"] = "Zur Überarbeitung senden";
$MESS["BP_DBLA_NAPP_DRAFT_S"] = "Status: Gesendet zur Überarbeitung";
$MESS["BP_DBLA_MAIL4_SUBJ"] = "Abstimmung von on \"{=Document:NAME}: Das Dokument wurde abgelehnt.";
$MESS["BP_DBLA_MAIL4_TEXT"] = "Die erste Stufe der Bestätigung von \"\"{=Document:NAME}\"\" ist abgeschlossen.

Das Dokument wurde abgelehnt.

{=ApproveActivity1:Comments}";
$MESS["BP_DBLA_PARAM1"] = "Personen, die auf der ersten Stufe abstimmen";
$MESS["BP_DBLA_PARAM2"] = "Personen, die auf der zweiten Stufe abstimmen";
?>