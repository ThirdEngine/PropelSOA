<?php
namespace ThirdEngine\PropelSOABundle\Tests\Base;


use ThirdEngine\PropelSOABundle\Base\ModelConverter;
use ThirdEngine\PropelSOABundle\Model\PropelSOAModel;

use Symfony\Bundle\FrameworkBundle\Tests;

use BasePeer;
use ColumnMap;
use DateTime;
use TableMap;



class ModelConverterTest extends Tests\TestCase
{
  public function testConvertModelToDataReturnsResultsOfToArray()
  {
    $data = [
      'TableId' => 3849,
      'Name' => 'My Table',
    ];

    $modelMock = $this->getMock(PropelSOAModel::class, ['toArray']);
    $modelMock->expects($this->any())
      ->method('toArray')
      ->with(BasePeer::TYPE_PHPNAME, false)
      ->willReturn($data);

    $modelConverter = new ModelConverter();
    $this->assertEquals($data, $modelConverter->convertModelToData($modelMock));
  }

  public function testConvertModelToDataLeavesDateTimeAsObject()
  {
    $now = new DateTime();

    $data = [
      'TableId' => 3849,
      'Created' => $now,
    ];

    $columnMapMock = $this->getMock(ColumnMap::class, ['getType'], [], '', false);
    $columnMapMock->expects($this->any())
      ->method('getType')
      ->willReturn('DATETIME');

    $tableMapMock = $this->getMock(TableMap::class, ['getColumnByPhpName'], [], '', false);
    $tableMapMock->expects($this->any())
      ->method('getColumnByPhpName')
      ->with('Created')
      ->willReturn($columnMapMock);

    $modelMock = $this->getMock(PropelSOAModel::class, ['toArray', 'getTableMap']);
    $modelMock->expects($this->any())
      ->method('toArray')
      ->with(BasePeer::TYPE_PHPNAME, false)
      ->willReturn($data);
    $modelMock->expects($this->any())
      ->method('getTableMap')
      ->willReturn($tableMapMock);

    $modelConverter = new ModelConverter();
    $returnedData = $modelConverter->convertModelToData($modelMock);

    $this->assertEquals($data['TableId'], $returnedData['TableId']);
    $this->assertEquals($now->format('c'), $returnedData['Created']);
  }

  public function testConvertModelToDataChangesDateToString()
  {
    $now = new DateTime();
    $importantDay = new DateTime('2009-03-08');

    $data = [
      'TableId' => 3849,
      'ImportantDay' => $importantDay,
    ];

    $columnMapMock = $this->getMock(ColumnMap::class, ['getType'], [], '', false);
    $columnMapMock->expects($this->any())
      ->method('getType')
      ->willReturn('DATE');

    $tableMapMock = $this->getMock(TableMap::class, ['getColumnByPhpName'], [], '', false);
    $tableMapMock->expects($this->any())
      ->method('getColumnByPhpName')
      ->with('ImportantDay')
      ->willReturn($columnMapMock);

    $modelMock = $this->getMock(PropelSOAModel::class, ['toArray', 'getTableMap']);
    $modelMock->expects($this->any())
      ->method('toArray')
      ->with(BasePeer::TYPE_PHPNAME, false)
      ->willReturn($data);
    $modelMock->expects($this->any())
      ->method('getTableMap')
      ->willReturn($tableMapMock);

    $modelConverter = new ModelConverter();
    $returnedData = $modelConverter->convertModelToData($modelMock);

    $this->assertEquals($data['TableId'], $returnedData['TableId']);
    $this->assertEquals('2009-03-08', $returnedData['ImportantDay']);
  }
}