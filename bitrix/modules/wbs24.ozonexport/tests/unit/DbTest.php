<?php
namespace Wbs24\Ozonexport;

class DbTest extends BitrixTestCase
{
    protected function getMethod($name)
    {
        $class = new \ReflectionClass('Wbs24\\Ozonexport\\Db');
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function testGet()
    {
        // входные параметры
        $table = 'demo';

        // результат для проверки
        $sql = "SELECT * FROM `demo` WHERE `id` > 100 AND `type` = 'new' ORDER BY sort DESC LIMIT 10";
        $sql2 = "SELECT * FROM `demo` ORDER BY sort DESC LIMIT 10";
        $sql3 = "SELECT * FROM `demo` WHERE `id` > 100";

        // заглушки
        $ResultStub = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['Fetch'])
            ->getMock();
        $fetchResults = [
            false,
        ];
        $ResultStub->method('Fetch')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        $DBStub = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['Query'])
            ->getMock();
        $DBStub->expects($this->exactly(3))
            ->method('Query')
            ->withConsecutive( // проверка результата
                [$this->equalTo($sql)],
                [$this->equalTo($sql2)]
            )
            ->willReturn($ResultStub);

        // вычисление результата
        $obj = new Db([
            'DB' => $DBStub,
        ]);
        // 1-ая итерация - результат в $sql
        $obj->get($table, [
            '>id' => 100,
            'type' => 'new',
        ], [
            'order' => 'sort DESC',
            'limit' => 10,
        ]);
        // 2-ая итерация (без WHERE) - результат в $sql2
        $obj->get($table, [], [
            'order' => 'sort DESC',
            'limit' => 10,
        ]);
        // 3-ая итерация (только WHERE) - результат в $sql3
        $obj->get($table, [
            '>id' => 100,
        ]);
    }

    public function testUpdate()
    {
        // входные параметры
        $table = 'demo';

        // результат для проверки
        $sql = "UPDATE `demo` SET `a` = '1', `b` = 'new' WHERE `c` = '2' AND `d` = 'yes'";

        // заглушки
        $ResultStub = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['Fetch'])
            ->getMock();
        $fetchResults = [
            false,
        ];
        $ResultStub->method('Fetch')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        $DBStub = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['Query'])
            ->getMock();
        $DBStub->expects($this->exactly(1))
            ->method('Query')
            ->withConsecutive( // проверка результата
                [$this->equalTo($sql)],
            )
            ->willReturn($ResultStub);

        // вычисление результата
        $obj = new Db([
            'DB' => $DBStub,
        ]);
        $obj->update($table, [
            'a' => 1,
            'b' => 'new',
        ], [
            'c' => 2,
            'd' => 'yes',
        ]);
    }

    public function testGetValuesAsSql()
    {
        // входные параметры
        $data = [
            'artnumber' => 'abc123',
            'json' => '{"json":123}',
            'json2' => '{"json2":123}',
        ];
        $exclude = [
            'artnumber',
        ];

        // результат для проверки
        $expectedDates = "`json` = '".$data['json']."', `json2` = '".$data['json2']."'";

        // заглушки

        // вычисление результата
        $method = $this->getMethod('getValuesAsSql');
        $db = new Db();
        $result = $method->invokeArgs($db, [$data, $exclude]);

        // проверка
        $this->assertEquals($expectedDates, $result);
    }

    public function testGetWhereAsSql()
    {
        // входные параметры
        $where = [
            'artnumber' => 'abc123',
            '<time_of_update' => 1000,
        ];

        // результат для проверки
        $expectedDates = "`artnumber` = '".$where['artnumber']."' AND `time_of_update` < ".$where['<time_of_update'];

        // заглушки

        // вычисление результата
        $method = $this->getMethod('getWhereAsSql');
        $db = new Db();
        $result = $method->invokeArgs($db, [$where]);

        // проверка
        $this->assertEquals($expectedDates, $result);
    }
}
