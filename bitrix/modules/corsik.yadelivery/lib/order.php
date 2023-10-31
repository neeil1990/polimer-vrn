<?php

namespace Corsik\YaDelivery;

use CSaleUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\{Loader, Context};
use Bitrix\Sale\{Fuser, Basket, Registry, PersonType, DiscountCouponsManager};

Loc::loadMessages(__FILE__);

class Order
{
    private $module_id = 'corsik.yadelivery';
    protected $order;
    protected $fUser = false;
    protected $siteId = false;
    protected $userId = false;
    protected $arErrors = [];
    protected $arParams = [];
    private $includeModules = ['sale', 'catalog'];

    /**
     * Необходимо из оформления заказа передавать Person type для правильно расчета
     * Order constructor.
     * @param $personTypeId
     */
    public function __construct($personTypeId)
    {
        $this->includeModules();
        $this->getUsers();
        $this->getSiteId();
        $this->arParams['PERSON_TYPE'] = $personTypeId;
    }

    public function init()
    {
        $basket = $this->getBasket();
        return [
            'weight' => $basket->getWeight(),
            'price' => $basket->getPrice(),
            'fullPrice' => $basket->getBasePrice(),
        ];
    }

    protected function createOrder()
    {

        $registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
        $basketClass = $registry->getBasketClassName();
        $fullBasket = $basketClass::loadItemsForFUser($this->fUser, $this->siteId);
        if ($fullBasket->isEmpty()) {
            return false;
        }

        DiscountCouponsManager::init(DiscountCouponsManager::MODE_CLIENT, ['userId' => $this->userId]);
        $orderClassName = $registry->getOrderClassName();
        $this->order = $orderClassName::create($this->siteId, $this->userId);
        $this->getSiteId();
    }

    protected function initPersonType()
    {
        $personTypes = PersonType::load($this->getSiteId());

        if ($personTypes) {
            foreach ($personTypes as $personType) {
                $personTypeId = intval($personType['ID']);
                $this->order->setPersonTypeId($personTypeId);
                $this->arResult['PERSON_TYPE_ID'] = $personTypeId;
                $this->arResult['PERSON_TYPE'][$personType['ID']] = $personType;
            }
        } else {
            return $this->addErrors(Loc::getMessage('CD_ERROR_PERSON_TYPE'));
        }

        return $this->arResult['PERSON_TYPE'];
    }

    protected function addErrors($message)
    {
        if ($message instanceof Result) {
            $errors = $message->getErrorMessages();
        } else {
            $errors = [$message];
        }

        foreach ($errors as $error) {
            if (!in_array($error, $this->arErrors, true)) {
                $this->arErrors[] = $error;
            }
        }

        return false;
    }

    protected function getSiteId()
    {
        $this->siteId = Context::getCurrent()->getSite();
    }

    protected function includeModules()
    {
        foreach ($this->includeModules as $name) {
            Loader::includeModule($name);
        }
    }

    protected function getUsers()
    {
        $this->fUser = $this->getFuserId();
        $this->userId = $this->getUserId();
    }

    protected function getUserId()
    {
        global $USER;
        $userId = $USER->GetID();
        if (!$userId) {
            $userId = CSaleUser::GetAnonymousUserID();
        }
        $this->userId = $userId;

    }

    protected function getFuserId()
    {
        if ($this->fUser === null)
            $this->loadCurrentFuser();

        return $this->fUser;
    }

    protected function loadCurrentFuser()
    {
        $this->fUser = Fuser::getId(true);
    }

    protected function getBasket()
    {
        $siteId = Context::getCurrent()->getSite();
        return Basket::loadItemsForFUser(Fuser::getId(), $siteId);
    }

}