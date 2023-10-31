<?php
namespace Wbs24\Ozonexport;

class OffersLogTest extends BitrixTestCase
{
    public function testAddOfferToLog()
    {
        // входные параметры
        $profileId = 1;
        $offerInfo = [
            'offer_id' => 1,
            'price' => 100,
        ];

        // результат для проверки
        $table = 'wbs24_ozonexport_offers_log';
        $expectedOfferInfo = array_merge($offerInfo, [
            'profile_id' => $profileId,
            'normal_export_time' => time(),
            'null_export_time' => 0,
        ]);

        // заглушки
        $DbStub = $this->getMockBuilder(Db::class)
            ->setMethods(['set'])
            ->getMock();
        $DbStub->expects($this->exactly(1))
            ->method('set')
            ->withConsecutive( // проверка результата
                [$this->equalTo($table), $this->equalTo($expectedOfferInfo)],
            )
            ->willReturn($ResultStub);

        // вычисление результата
        $obj = new OffersLog([
            'offersLogOn' => true,
            'profileId' => $profileId,
            'objects' => [
                'Db' => $DbStub,
            ],
        ]);
        $obj->addOfferToLog($offerInfo);
    }
}
