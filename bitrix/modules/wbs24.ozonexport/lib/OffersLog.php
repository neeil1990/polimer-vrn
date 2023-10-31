<?php
namespace Wbs24\Ozonexport;

class OffersLog
{
    protected $param;
    protected $table = 'wbs24_ozonexport_offers_log';
    protected $offersLogOn;
    protected $profileId;
    protected $nullOfferLifetime;

    protected $db;

    function __construct($param = [])
    {
        $this->setParam($param);

        $objects = $this->param['objects'] ?? [];
        $this->db = $objects['Db'] ?? new Db();

        $this->offersLogOn = $this->param['offersLogOn'] ?? false;
        $this->profileId = $this->param['profileId'] ?? 0;
        if (!$this->offersLogOn) $this->profileId = 0;
        $nullOfferLifetimeDays = $this->param['nullOfferLifetimeDays'] ?? 1;
        $this->nullOfferLifetime = intval($nullOfferLifetimeDays) * 86400;
    }

    public function setParam($param)
    {
        foreach ($param as $name => $value) {
            $this->param[$name] = $value;
        }
    }

    public function startOffersLog()
    {
        if (!$this->profileId) return;

        $data = [
            'normal_export_time' => 0,
        ];
        $where = [
            'profile_id' => $this->profileId,
        ];
        $this->db->update($this->table, $data, $where);
    }

    public function clearOffersLog()
    {
        $this->clearOldOffersLog(true);
    }

    public function clearOldOffersLog($all = false)
    {
        if (!$this->profileId) return;

        $minNullExportTime = time() - $this->nullOfferLifetime;
        $where = [
            'profile_id' => $this->profileId,
        ];
        if (!$all) {
            $where['<null_export_time'] = $minNullExportTime;
            $where['>null_export_time'] = 0;
        }
        $this->db->clear($this->table, $where);
    }

    public function addOfferToLog($offerInfo)
    {
        if (!$this->profileId) return;

        $offerInfo['profile_id'] = $this->profileId;
        $offerInfo['normal_export_time'] = time();
        $offerInfo['null_export_time'] = 0;

        $this->db->set($this->table, $offerInfo);
    }

    public function getNullOffersAsXml($nullStocksXml)
    {
        $offers = $this->getNullOffers();
        $xml = '';

        foreach ($offers as $offer) {
            $xml .= '<offer id="'.$offer['offer_id'].'">'."\n";
            $xml .= "<price>".$offer['price']."</price>\n";
            $xml .= "<oldprice>0</oldprice>\n";
            $xml .= $nullStocksXml;
            $xml .= "</offer>\n";
        }

        return $xml;
    }

    protected function getNullOffers()
    {
        if (!$this->profileId) return [];

        $where = [
            'profile_id' => $this->profileId,
            'normal_export_time' => 0,
        ];
        $param = [
            'order' => 'id',
        ];
        $offers = $this->db->get($this->table, $where, $param);

        $data = [
            'null_export_time' => time(),
        ];
        $where = [
            'profile_id' => $this->profileId,
            'normal_export_time' => 0,
        ];
        $this->db->update($this->table, $data, $where);

        return $offers;
    }
}
