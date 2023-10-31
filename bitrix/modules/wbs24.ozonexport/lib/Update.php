<?php
namespace Wbs24\Ozonexport;

use Bitrix\Main\Localization\Loc;

class Update
{
    protected const MODULE_ID = "wbs24.ozonexport";

    protected $moduleId;
    protected $wrappers;
    protected $Notify;

    public function __construct($objects = [])
    {
        $this->loadClass();
        $this->moduleId = self::MODULE_ID;
        $this->wrappers = new Wrappers($objects);
        $this->Notify = $objects['Notify'] ?? new Notify();
    }

    public static function OnAfterUserAuthorizeHandler($arUser)
    {
        if (defined('ADMIN_SECTION')) {
            $update = new Update();
            $update->notifyAboutUpdate();
        }
    }

    public function notifyAboutUpdate()
    {
        $message = $this->getUpdateMessage();
        if ($message) $this->Notify->addAdminNotify($message);
    }

    public function setModileId($moduleId)
    {
        $this->moduleId = $moduleId;
    }

    public function getUpdateMessage($moduleId = false)
    {
        if ($moduleId) $this->setModileId($moduleId);
        $lastVersion = $this->getLastVersion($moduleId);
        $updateLinkAsHtml = $this->getUpdateLinkAsHtml();

        $message = '';
        if ($lastVersion) {
            $message = Loc::getMessage("UPDATE_MESSAGE")." ".$lastVersion.", ".$updateLinkAsHtml;
        }

        return $message;
    }

    public function getLastVersion($moduleId = false)
    {
        if ($moduleId) $this->setModileId($moduleId);
        $updateList = $this->getUpdateList();
        $lastVersion = $this->getLastVersionFromUpdateList($updateList);

        return $lastVersion;
    }

    protected function getUpdateLinkAsHtml()
    {
        $updateLink = $this->getUpdateLink();

        return '<a href="'.$updateLink.'">'.Loc::getMessage("UPDATE_RUN").'</a>';
    }

    protected function getUpdateLink()
    {
        return '/bitrix/admin/update_system_partner.php?'.http_build_query([
            'tabControl_active_tab' => 'tab2',
            'addmodule' => $this->moduleId,
        ]);
    }

    protected function getUpdateList()
    {
        return $this->wrappers->CUpdateClientPartner->GetUpdatesList('', false, 'Y', [$this->moduleId]);
    }

    protected function getLastVersionFromUpdateList($updateList)
    {
        $lastVersion = false;

        if (!empty($updateList['MODULE']) && is_array($updateList['MODULE'])) {
            foreach ($updateList['MODULE'] as $update) {
                $isTargetModule = (isset($update['@']['ID']) && $update['@']['ID'] == $this->moduleId);
                $hasNewVersion = (isset($update['#']['VERSION']) && is_array($update['#']['VERSION']));

                if ($isTargetModule && $hasNewVersion) {
                    foreach ($update['#']['VERSION'] as $updateVersion) {
                        if (isset($updateVersion['@']['ID'])) {
                            $lastVersion = $updateVersion['@']['ID'];
                        }
                    }
                    break;
                }
            }
        }

        return $lastVersion;
    }

    protected function loadClass()
    {
        require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/general/update_client_partner.php');
    }
}
