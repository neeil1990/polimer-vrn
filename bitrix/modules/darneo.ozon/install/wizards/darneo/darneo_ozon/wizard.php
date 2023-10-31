<?

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/install/wizard_sol/wizard.php';

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class SelectSiteStep extends CSelectSiteWizardStep
{
    public function InitStep(): void
    {
        parent::InitStep();

        $wizard = &$this->GetWizard();
        $wizard->solutionName = 'darneo.ozon';

        $this->SetNextStep('data_install');
    }
}

class DataInstallStep extends CDataInstallWizardStep
{
    public function CorrectServices(&$arServices): void
    {
        $wizard = &$this->GetWizard();
    }
}

class FinishStep extends CFinishWizardStep
{

}