<?php
namespace ThirdEngine\PropelSOABundle\Tests\Base;

use ThirdEngine\Factory\Factory;
use ThirdEngine\PropelSOABundle\Base\SymfonyClassInfo;
use ThirdEngine\PropelSOABundle\Model\PropelSOAModel;
use ThirdEngine\PropelSOABundle\Base\LinkedData;
use ThirdEngine\PropelSOABundle\Model\PropelSOAPeer;

use Symfony\Bundle\FrameworkBundle\Tests;


class LinkedDataTest extends Tests\TestCase
{
  public function testConstructorSetsLinkedDataRelationType()
  {
    $linkedData = new LinkedData();
    $this->assertEquals('linkeddata', $linkedData->relationType);
  }

  public function testCorrectNamespaceDoesNothing()
  {
    $linkedData = new LinkedData();
    $linkedData->correctNamespace(null);
  }

  public function testAddJoinedDataToResultArrayChecksPeerAndAddsResultOfMappedMethod()
  {
    $peerClass = 'MyPeerClass';
    $relation = 'Widget';
    $widgetValue = 'value';

    $peer = new PropelSOAPeer();
    $peer->linkedData = ['Widget' => 'getMyWidgets'];

    $modelMock = $this->getMock(PropelSOAModel::class, ['getMyWidgets']);
    $modelMock->expects($this->once())
      ->method('getMyWidgets')
      ->willReturn($widgetValue);

    $classInfoMock = $this->getMock(SymfonyClassInfo::class, ['parseClassPath', 'getClassPath']);
    $classInfoMock->expects($this->once())
      ->method('parseClassPath')
      ->with($this->equalTo(get_class($modelMock)));
    $classInfoMock->expects($this->once())
      ->method('getClassPath')
      ->with($this->equalTo('peer'))
      ->willReturn($peerClass);

    Factory::injectObject($peerClass, $peer);
    Factory::injectObject(SymfonyClassInfo::class, $classInfoMock);

    $data = [];

    $linkedData = new LinkedData();
    $linkedData->relation = $relation;

    $linkedData->addJoinedDataToResultArray($modelMock, $data);
    $this->assertEquals($widgetValue, $data[$relation]);
  }
}