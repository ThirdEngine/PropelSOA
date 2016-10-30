<?php
namespace ThirdEngine\PropelSOABundle\Tests\Model;

use ThirdEngine\Factory\Factory;
use ThirdEngine\PropelSOABundle\Base\SymfonyClassInfo;
use ThirdEngine\PropelSOABundle\Model\PropelSOAPeer;
use ThirdEngine\PropelSOABundle\Model\PropelSOAModel;
use ThirdEngine\PropelSOABundle\Model\PropelSOAQuery;

use PropelCollection;
use TableMap;
use Symfony\Bundle\FrameworkBundle\Tests;


class PropelSOAModelTest extends Tests\TestCase
{
  public function testSetLinkedDataCallsAssociatedMethodAndSavesResultInLinkedDataArray()
  {
    $rating = 33;

    $peer = new PropelSOAPeer();
    $peer->linkedData = ['Rating' => 'getRating'];

    $modelMock = $this->getMock(PropelSOAModel::class, ['getPeer', 'getRating']);
    $modelMock->expects($this->once())
      ->method('getPeer')
      ->will($this->returnValue($peer));
    $modelMock->expects($this->once())
      ->method('getRating')
      ->will($this->returnValue($rating));

    $modelMock->setLinkedData('Rating');
    $this->assertEquals($rating, $modelMock->linkedData['Rating']);
  }

  public function testGetTableMapReturnsTableMapFromRelatedQueryObject()
  {
    $queryClassName = 'QueryClass';
    $tableMap = 'tablemap';
    

    $modelMock = $this->getMock(PropelSOAModel::class, ['nosuchmethod']);

    $classInfoMock = $this->getMock(SymfonyClassInfo::class, ['parseClassPath', 'getClassPath']);
    $classInfoMock->expects($this->once())
      ->method('parseClassPath')
      ->with(get_class($modelMock));
    $classInfoMock->expects($this->once())
      ->method('getClassPath')
      ->willReturn($queryClassName);

    $queryMock = $this->getMock(PropelSOAQuery::class, ['getTableMap'], [], '', false);
    $queryMock->expects($this->any())
      ->method('getTableMap')
      ->willReturn($tableMap);

    Factory::injectObject(SymfonyClassInfo::class, $classInfoMock);
    Factory::injectQueryObject($queryClassName, $queryMock);

    $this->assertEquals($tableMap, $modelMock->getTableMap());
  }
}