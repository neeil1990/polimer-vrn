<?
$MESS["BPT_SM_NAME"] = "Einfache Genehmigung/Abstimmung";
$MESS["BPT_SM_DESC"] = "Empfohlen für Situationen, wenn die Entscheidung durch die einfache Mehrheit getroffen wird. Sie können abstimmende Personen angeben und ihnen die Möglichkeit geben ihre Stimmen zu kommentieren. Wenn die Abstimmung abgeschlossen ist, werden alle Teilnehmer über das Ergebnis informiert.";
$MESS["BPT_SM_TITLE1"] = "Regelmäßiger Workflow";
$MESS["BPT_SM_TASK1_TITLE"] = "Dokument \"{=Document:NAME}\" genehmigen";
$MESS["BPT_SM_TASK1_TEXT"] = "Sie müssen das Dokument \"\"{=Document:NAME}\"\" bestätigen oder ablehnen.

Klicken Sie auf den Link um fortzuführen: #BASE_HREF##TASK_URL#

Autor: {=Document:CREATED_BY}";
$MESS["BPT_SM_ACT_TITLE"] = "E-Mail-Nachricht";
$MESS["BPT_SM_APPROVE_NAME"] = "Bitte Dokument bestätigen oder ablehnen.";
$MESS["BPT_SM_APPROVE_DESC"] = "Sie müssen das Dokument \"\"{=Document:NAME}\"\" bestätigen oder ablehnen.

Autor: {=Document:CREATED_BY}";
$MESS["BPT_SM_APPROVE_TITLE"] = "Abstimmen";
$MESS["BPT_SM_ACT_NAME_1"] = "Reihenfolge der Aktivitäten";
$MESS["BPT_SM_MAIL1_SUBJ"] = "Abstimmung von on \"{=Document:NAME}: Das Dokument hat passiert.";
$MESS["BPT_SM_MAIL1_TEXT"] = "Abstimmung von \"\"{=Document:NAME}\"\" ist abgeschlossen.

Das Dokument wurde bestätigt durch {=ApproveActivity1:ApprovedPercent}% aller Stimmen.

Bestätigt: {=ApproveActivity1:ApprovedCount}
Abgelehnt: {=ApproveActivity1:NotApprovedCount}\"";
$MESS["BPT_SM_MAIL1_TITLE"] = "Das Dokument wurde genehmigt";
$MESS["BPT_SM_STATUS"] = "Genehmigt";
$MESS["BPT_SM_STATUS2"] = "Status: Bestätigt";
$MESS["BPT_SM_PUB"] = "Dokument veröffentlichen";
$MESS["BPT_SM_MAIL2_SUBJ"] = "Abstimmung von on \"{=Document:NAME}: Das Dokument wurde abgelehnt.";
$MESS["BPT_SM_MAIL2_TEXT"] = "Abstimmung von \"\"{=Document:NAME}\"\" ist abgeschlossen.

Das Dokument wurde abgelehnt.

Bestätigt: {=ApproveActivity1:ApprovedCount}
Abgelehnt: {=ApproveActivity1:NotApprovedCount}\"";
$MESS["BPT_SM_MAIL2_TITLE"] = "Das Dokument wurde abgelehnt";
$MESS["BPT_SM_MAIL2_STATUS"] = "Abgelehnt";
$MESS["BPT_SM_MAIL2_STATUS2"] = "Status: Abgelehnt";
$MESS["BPT_SM_PARAM_NAME"] = "Abstimmende Personen";
$MESS["BPT_SM_PARAM_DESC"] = "Benutzer, die an der Abstimmung teilnehmen.";
?>