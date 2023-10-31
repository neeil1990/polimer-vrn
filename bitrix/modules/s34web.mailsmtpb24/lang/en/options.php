<?php
/**
 * Created: 12.03.2021, 14:55
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

$MESS['S34WEB_MAILSMTPB24_MODULE_ERROR'] = 'The module "Sending mail via external SMTP (Bitrix24 box, Online store + CRM)" is not installed!';
$MESS['S34WEB_MAILSMTPB24_MODULE_NOT_FOUND_TITLE'] = 'Error in the module "Sending mail via external SMTP (Bitrix24 box, Online store + CRM)"';
$MESS['S34WEB_MAILSMTPB24_MODULE_NOT_FOUND_TEXT'] = 'The module "Sending mail via external SMTP (Bitrix24 box, Online store + CRM)" was not found!';
$MESS['S34WEB_MAILSMTPB24_MODULE_DEMO_EXPIRED_TITLE'] = 'Error in the module "Sending mail via external SMTP (Bitrix24 box, Online store + CRM)"';
$MESS['S34WEB_MAILSMTPB24_MODULE_DEMO_EXPIRED_TEXT'] = 'The demo mode of the module "Sending mail via external SMTP (Bitrix24 box, Online store + CRM)" has expired!';
$MESS['S34WEB_MAILSMTPB24_MODULE_DEMO_TITLE'] = 'Limited mode of operation of the module "Sending mail via external SMTP (Bitrix24 box, Online store + CRM)" ';
$MESS['S34WEB_MAILSMTPB24_MODULE_DEMO_TEXT'] = 'The module works in demo mode! You can buy the unlimited version!';
$MESS['S34WEB_MAILSMTPB24_PROPS'] = 'Settings';
$MESS['S34WEB_MAILSMTPB24_PROPS_TITLE'] = 'SMTP Mail Submitter Settings';
$MESS['S34WEB_MAILSMTPB24_RIGHTS'] = 'Access';
$MESS['S34WEB_MAILSMTPB24_RIGHTS_TITLE'] = 'Module access level';
$MESS['S34WEB_MAILSMTPB24_UPDATE_BTN'] = 'Save';
$MESS['S34WEB_MAILSMTPB24_RESET_BTN'] = 'Cancel';
$MESS['S34WEB_MAILSMTPB24_DEFAULT_BTN'] = 'Default';
$MESS['S34WEB_MAILSMTPB24_RESTORE_DEFAULTS'] = 'Set default values ';
$MESS['S34WEB_MAILSMTPB24_RESTORE_DEFAULTS_WARNING'] = 'Attention! All settings will be overwritten with default '.
    'values. Proceed?';
$MESS['S34WEB_MAILSMTPB24_OPTIONS_HEADER_MAIN_SETTINGS'] = 'Base settings';
$MESS['S34WEB_MAILSMTPB24_OPTION_ACTIVE_MODULE'] = 'Enable the module to work';
$MESS['S34WEB_MAILSMTPB24_OPTION_SITE_ID'] = 'The main site for the module to work';
$MESS['S34WEB_MAILSMTPB24_OPTION_SITE_ID_VALUE_NAME_NONE'] = 'not set';
$MESS['S34WEB_MAILSMTPB24_OPTION_EXTRANET_SITE_ID'] = 'Extranet site';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_SENDING'] = 'Save logs of sending emails';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_LEVEL'] = 'Log information type ';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_LEVEL_VALUE_NAME_1'] = 'Messages sent by the client';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_LEVEL_VALUE_NAME_2'] = 'Messages sent by the client & responses received from '.
    'the server';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_LEVEL_VALUE_NAME_3'] = 'Messages sent by the client, responses received from '.
    'the server &  information about the initial connection';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_LEVEL_VALUE_NAME_4'] = 'Messages sent by the client, responses received from '.
    'the server, information about the initial connection & low-level problems';
$MESS['S34WEB_MAILSMTPB24_OPTIONS_HEADER_SEND_SETTINGS'] = 'Send settings';
$MESS['S34WEB_MAILSMTPB24_OPTION_SMTP_TIMEOUT'] = 'Connection timeout (seconds)';
$MESS['S34WEB_MAILSMTPB24_OPTIONS_INFO'] = 'Parameter Description';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_REWRITE'] = 'Log rewriting period ';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_REWRITE_VALUE_NAME_1'] = 'Day';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_REWRITE_VALUE_NAME_2'] = 'Week';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_REWRITE_VALUE_NAME_3'] = 'Month';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_REWRITE_VALUE_NAME_4'] = 'Year';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_WEIGHT'] = 'Maximum log file size (Mb)';
$MESS['S34WEB_MAILSMTPB24_OPTION_SELECTION_PRIORITY'] = 'Priority for selecting SMTP accounts';
$MESS['S34WEB_MAILSMTPB24_OPTION_SELECTION_PRIORITY_VALUE_NAME_1'] = 'Custom SMTP Accounts'.
    '(module Mail, CRM), SMTP accounts of the administrative section';
$MESS['S34WEB_MAILSMTPB24_OPTION_SELECTION_PRIORITY_VALUE_NAME_2'] = 'SMTP accounts of the administrative section, User '.
    'SMTP accounts (Mail module, CRM)';
$MESS['S34WEB_MAILSMTPB24_OPTION_EXTENDED_CHECK_CONNECT'] = 'Send a letter when checking the connection';
$MESS['S34WEB_MAILSMTPB24_MANAGEMENT_AND_DEBUGGING'] = 'Management and debugging';
$MESS['S34WEB_MAILSMTPB24_MANAGEMENT_SMTP_ACCOUNTS_BTN'] = 'SMTP Account Management ';
$MESS['S34WEB_MAILSMTPB24_MAIL_SENDING_LOGS_BTN'] = 'Mail sending logs';
$MESS['S34WEB_MAILSMTPB24_OPTION_ACTIVE_MODULE_HINT'] = 'Enabling the module and sending letters via the protocol'.
    'SMTP. After enabling, access to the management of SMTP accounts opens ("Services" -> "Sending mail via SMTP (B24, IM + CRM)" -> '.
    '"SMTP accounts") and logs of sending letters ("Services" -> "Sending mail via SMTP (B24, IM + CRM)" -> "Logs of sending letters").'.
    ' Opens access to sending settings through the "Mail" module and setting up your own SMTP servers in CRM for sending letters.';
$MESS['S34WEB_MAILSMTPB24_OPTION_SITE_ID_HINT'] = 'Selecting the site on which the outgoing mail settings functionality will work. If the site is not selected, the setting for outgoing emails will not be enabled.';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_SENDING_HINT'] = 'Saving logs of connection check and sending letters'.
    '("Services" -> "Sending mail via SMTP (B24, IM + CRM)" -> "Logs of sending letters").';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_LEVEL_HINT'] = 'Information about connecting to the mail server and sending letters,'.
    'output to log files.';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_REWRITE_HINT'] = 'How many days after changing the SMTP account to rewrite '.
    'file for logging operations.';
$MESS['S34WEB_MAILSMTPB24_OPTION_LOG_WEIGHT_HINT'] = 'The maximum size of the log file, after which it is overwritten. '.
    'If you specify a value of 0, then the file size will not be checked. ';
$MESS['S34WEB_MAILSMTPB24_OPTION_SELECTION_PRIORITY_HINT'] = 'This setting item '.
    'makes it possible to select the priority of receiving the settings of SMTP accounts: User SMTP accounts'.
    '(Mail module, CRM), SMTP accounts of the administrative section.';
$MESS['S34WEB_MAILSMTPB24_OPTION_EXTENDED_CHECK_CONNECT_HINT'] = 'The operation of SMTP accounts is checked by successful authorization using the data specified in the settings. '.
    'If you need to check the successful sending of letters from accounts, then you must enable this setting item. Note: '.
    'Sending letters is performed on the 1C-Bitrix mail server, as is done by checking the system on the website (corporate portal). '.
    'If the 1C-Bitrix mail server considers your sending to be spam, an error will be displayed and the account will not be saved!';
