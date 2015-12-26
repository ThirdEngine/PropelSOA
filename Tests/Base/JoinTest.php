<?php
namespace SOA\SOABundle\Tests\Base;

use ThirdEngine\Factory\Factory;
use SOA\SOABundle\Base\Join;
use SOA\SOABundle\Base\SymfonyClassInfo;
use SOA\SOABundle\Model\PropelSOAModel;
use SOA\SOABundle\Model\PropelSOAPeer;
use SOA\SOABundle\Model\PropelSOAQuery;
use SOA\SOABundle\Tests\TestUtility;

use BasePeer;
use ModelCriteria;
use TableMap;
use RelationMap;
use Symfony\Bundle\FrameworkBundle\Tests;


class JoinTest extends Tests\TestCase
{
  public function testConstructorSetsJoinRelationType()
  {
    $join = new Join();
    $this->assertEquals('join', $join->relationType);
  }

  public function testCorrectNamepsaceSetsClassInfoAndCallsChildrenMethod()
  {
    $projectTableMap = $this->getMock(TableMap::class, ['getClassName']);
    $projectTableMap->expects($this->once())
      ->method('getClassName')
      ->will($this->returnValue("Business\\PlanningBundle\\Model\\Project"));


    $partToProjectRelation = $this->getMock(RelationMap::class, ['getRightTable'], [], '', false);
    $partToProjectRelation->expects($this->once())
      ->method('getRightTable')
      ->will($this->returnValue($projectTableMap));

    $partTableMap = $this->getMock(TableMap::class, ['getRelation']);
    $partTableMap->expects($this->once())
      ->method('getRelation')
      ->with($this->equalTo('Project'))
      ->will($this->returnValue($partToProjectRelation));

    $widgetToPartRelation = $this->getMock(RelationMap::class, ['getRightTable'], [], '', false);
    $widgetToPartRelation->expects($this->once())
      ->method('getRightTable')
      ->will($this->returnValue($partTableMap));

    $widgetTableMap = $this->getMock(TableMap::class, ['getRelation']);
    $widgetTableMap->expects($this->once())
      ->method('getRelation')
      ->with($this->equalTo('Part'))
      ->will($this->returnValue($widgetToPartRelation));

    $widgetQuery = $this->getMock(ModelCriteria::class, ['getTableMap'], [], '', false);
    $widgetQuery->expects($this->once())
      ->method('getTableMap')
      ->will($this->returnValue($widgetTableMap));

    $childRelationMock = $this->getMock(Join::class, ['correctNamespace']);
    $childRelationMock->expects($this->once())
      ->method('correctNamespace')
      ->with($this->equalTo($widgetQuery));

    $join = new Join();
    $join->fullRelationPath = 'Part.Project';
    $join->joinList = [$childRelationMock];

    $join->correctNamespace($widgetQuery);

    $this->assertEquals('Business', $join->namespace);
    $this->assertEquals('Planning', $join->bundle);
    $this->assertEquals('Project', $join->entity);
  }

  public function testAddToQueryUsesCompleteRelationWhenOnlyTwoRelations()
  {
    $widgetQuery = $this->getMock(ModelCriteria::class, ['leftJoinWith'], [], '', false);
    $widgetQuery->expects($this->once())
      ->method('leftJoinWith')
      ->with($this->equalTo('Part.Project'));

    $childJoinMock = $this->getMock(Join::class, ['addToQuery']);
    $childJoinMock->expects($this->once())
      ->method('addToQuery')
      ->with($this->equalTo($widgetQuery));

    $join = new Join();
    $join->fullRelationPath = 'Part.Project';
    $join->joinList = [$childJoinMock];

    $join->addToQuery($widgetQuery);
  }

  public function testAddToQueryUsesLastTwoRelationElements()
  {
    $widgetQuery = $this->getMock(ModelCriteria::class, ['leftJoinWith'], [], '', false);
    $widgetQuery->expects($this->once())
      ->method('leftJoinWith')
      ->with($this->equalTo('Project.Plan'));

    $childJoinMock = $this->getMock(Join::class, ['addToQuery']);
    $childJoinMock->expects($this->once())
      ->method('addToQuery')
      ->with($this->equalTo($widgetQuery));

    $join = new Join();
    $join->fullRelationPath = 'Part.Project.Plan';
    $join->joinList = [$childJoinMock];

    $join->addToQuery($widgetQuery);
  }

  public function testAddJoinedDataToResultArrayAddsCorrectDataForNaturallySingularRelation()
  {
    $data = [];
    $relation = 'Widget';
    $relationType = RelationMap::MANY_TO_ONE;

    $widgetData = ['WidgetId' => 3392];

    $widgetModel = $this->getMock(PropelSOAModel::class, ['toArray']);
    $widgetModel->expects($this->once())
      ->method('toArray')
      ->with($this->equalTo(\BasePeer::TYPE_PHPNAME), $this->equalTo(false))
      ->willReturn($widgetData);

    $childJoinMock = $this->getMock(Join::class, ['addJoinedDataToResultArray']);
    $childJoinMock->expects($this->once())
      ->method('addJoinedDataToResultArray')
      ->with($this->equalTo($widgetModel), $this->equalTo($widgetData));

    $model = $this->getMock(PropelSOAModel::class, ['getWidget']);
    $model->expects($this->once())
      ->method('getWidget')
      ->willReturn($widgetModel);

    $relationMapMock = $this->getMock(RelationMap::class, ['getType'], [], '', false);
    $relationMapMock->expects($this->any())
      ->method('getType')
      ->willReturn($relationType);

    $tableMapMock = $this->getMock(TableMap::class, ['getRelation'], [], '', false);
    $tableMapMock->expects($this->once())
      ->method('getRelation')
      ->with($this->equalTo($relation))
      ->willReturn($relationMapMock);

    $queryMock = $this->getMock(PropelSOAQuery::class, ['getTableMap'], [], '', false);
    $queryMock->expects($this->once())
      ->method('getTableMap')
      ->willReturn($tableMapMock);

    $classInfoMock = $this->getMock(SymfonyClassInfo::class, ['parseClassPath', 'getClassPath']);
    $classInfoMock->expects($this->once())
      ->method('parseClassPath')
      ->with($this->equalTo(get_class($model)));
    $classInfoMock->expects($this->once())
      ->method('getClassPath')
      ->with($this->equalTo('query'))
      ->willReturn(PropelSOAQuery::class);

    Factory::injectObject(SymfonyClassInfo::class, $classInfoMock);
    Factory::injectObject(PropelSOAQuery::class, $queryMock);

    $join = new Join();
    $join->relation = $relation;
    $join->joinList = [$childJoinMock];

    $join->addJoinedDataToResultArray($model, $data);
  }

  public function testAddJoinedDataToResultArrayAddsNullKeyWhenRelatedDataNotPresent()
  {
    $data = [];
    $relation = 'Widget';
    $relationType = RelationMap::MANY_TO_ONE;

    $widgetData = ['WidgetId' => 3392];

    $model = $this->getMock(PropelSOAModel::class, ['getWidget']);
    $model->expects($this->once())
      ->method('getWidget')
      ->willReturn(null);

    $relationMapMock = $this->getMock(RelationMap::class, ['getType'], [], '', false);
    $relationMapMock->expects($this->any())
      ->method('getType')
      ->willReturn($relationType);

    $tableMapMock = $this->getMock(TableMap::class, ['getRelation'], [], '', false);
    $tableMapMock->expects($this->once())
      ->method('getRelation')
      ->with($this->equalTo($relation))
      ->willReturn($relationMapMock);

    $queryMock = $this->getMock(PropelSOAQuery::class, ['getTableMap'], [], '', false);
    $queryMock->expects($this->once())
      ->method('getTableMap')
      ->willReturn($tableMapMock);

    $classInfoMock = $this->getMock(SymfonyClassInfo::class, ['parseClassPath', 'getClassPath']);
    $classInfoMock->expects($this->once())
      ->method('parseClassPath')
      ->with($this->equalTo(get_class($model)));
    $classInfoMock->expects($this->once())
      ->method('getClassPath')
      ->with($this->equalTo('query'))
      ->willReturn(PropelSOAQuery::class);

    Factory::injectObject(SymfonyClassInfo::class, $classInfoMock);
    Factory::injectObject(PropelSOAQuery::class, $queryMock);

    $join = new Join();
    $join->relation = $relation;
    $join->joinList = [];

    $join->addJoinedDataToResultArray($model, $data);
  }

  public function testAddJoinedDataToResultArrayTakesFirstResultWhenForcedToOneToOne()
  {
    $data = [];
    $relation = 'Widget';
    $relationType = RelationMap::ONE_TO_MANY;

    $testUtility = new TestUtility();
    $widgetData = ['WidgetId' => 3392];


    $widgetMock = $this->getMock(PropelSOAModel::class, ['toArray']);
    $widgetMock->expects($this->once())
      ->method('toArray')
      ->with($this->equalTo(BasePeer::TYPE_PHPNAME), $this->equalTo(false))
      ->willReturn($widgetData);

    $modelMock = $this->getMock(PropelSOAModel::class, ['getWidgets']);
    $modelMock->expects($this->once())
      ->method('getWidgets')
      ->willReturn($testUtility->convertToCollection([$widgetMock]));

    $relationMapMock = $this->getMock(RelationMap::class, ['getType'], [], '', false);
    $relationMapMock->expects($this->once())
      ->method('getType')
      ->willReturn($relationType);

    $peer = new PropelSOAPeer();
    $peer->oneToOneRelations = [$relation];

    $classInfoMock = $this->getMock(SymfonyClassInfo::class, ['parseClassPath', 'getClassPath']);
    $classInfoMock->expects($this->once())
      ->method('parseClassPath')
      ->with($this->equalTo(get_class($modelMock)));
    $classInfoMock->expects($this->once())
      ->method('getClassPath')
      ->with($this->equalTo('peer'))
      ->willReturn(PropelSOAPeer::class);

    Factory::injectObject(PropelSOAPeer::class, $peer);
    Factory::injectObject(SymfonyClassInfo::class, $classInfoMock);

    $join = new Join();
    $join->joinList = [];
    $join->relation = $relation;

    $testUtility->setProtectedProperty($join, 'relationMap', $relationMapMock);
    $testUtility->setProtectedProperty($join, 'joinedDataKey', $relation);

    $join->addJoinedDataToResultArray($modelMock, $data);
    $this->assertEquals($data, ['Widget' => $widgetData]);
  }

  public function testAddJoinedDataToResultArrayAddsNulledKeyWhenForcedOneToOneAndReturnedCollectionIsEmpty()
  {
    $data = [];
    $relation = 'Widget';
    $relationType = RelationMap::ONE_TO_MANY;

    $testUtility = new TestUtility();
    $widgetData = ['WidgetId' => 3392];


    $modelMock = $this->getMock(PropelSOAModel::class, ['getWidgets']);
    $modelMock->expects($this->once())
      ->method('getWidgets')
      ->willReturn($testUtility->convertToCollection([]));

    $relationMapMock = $this->getMock(RelationMap::class, ['getType'], [], '', false);
    $relationMapMock->expects($this->once())
      ->method('getType')
      ->willReturn($relationType);

    $peer = new PropelSOAPeer();
    $peer->oneToOneRelations = [$relation];

    $classInfoMock = $this->getMock(SymfonyClassInfo::class, ['parseClassPath', 'getClassPath']);
    $classInfoMock->expects($this->once())
      ->method('parseClassPath')
      ->with($this->equalTo(get_class($modelMock)));
    $classInfoMock->expects($this->once())
      ->method('getClassPath')
      ->with($this->equalTo('peer'))
      ->willReturn(PropelSOAPeer::class);

    Factory::injectObject(PropelSOAPeer::class, $peer);
    Factory::injectObject(SymfonyClassInfo::class, $classInfoMock);

    $join = new Join();
    $join->joinList = [];
    $join->relation = $relation;

    $testUtility->setProtectedProperty($join, 'relationMap', $relationMapMock);
    $testUtility->setProtectedProperty($join, 'joinedDataKey', $relation);

    $join->addJoinedDataToResultArray($modelMock, $data);
    $this->assertEquals($data, ['Widget' => null]);
  }

  public function testAddJoinedDataToResultArrayAddsArrayForPluralRelation()
  {
    $data = [];
    $relation = 'Widget';
    $relationType = RelationMap::ONE_TO_MANY;

    $testUtility = new TestUtility();
    $widgetData = ['WidgetId' => 3392];


    $widgetMock = $this->getMock(PropelSOAModel::class, ['toArray']);
    $widgetMock->expects($this->once())
      ->method('toArray')
      ->with($this->equalTo(BasePeer::TYPE_PHPNAME), $this->equalTo(false))
      ->willReturn($widgetData);

    $modelMock = $this->getMock(PropelSOAModel::class, ['getWidgets']);
    $modelMock->expects($this->once())
      ->method('getWidgets')
      ->willReturn($testUtility->convertToCollection([$widgetMock]));

    $relationMapMock = $this->getMock(RelationMap::class, ['getType'], [], '', false);
    $relationMapMock->expects($this->any())
      ->method('getType')
      ->willReturn($relationType);

    $peer = new PropelSOAPeer();
    $peer->oneToOneRelations = [];

    $classInfoMock = $this->getMock(SymfonyClassInfo::class, ['parseClassPath', 'getClassPath']);
    $classInfoMock->expects($this->any())
      ->method('parseClassPath')
      ->with($this->equalTo(get_class($modelMock)));
    $classInfoMock->expects($this->any())
      ->method('getClassPath')
      ->with($this->equalTo('peer'))
      ->willReturn(PropelSOAPeer::class);

    Factory::injectObject(PropelSOAPeer::class, $peer);
    Factory::injectObject(SymfonyClassInfo::class, $classInfoMock);

    $join = new Join();
    $join->joinList = [];
    $join->relation = $relation;

    $testUtility->setProtectedProperty($join, 'relationMap', $relationMapMock);

    $join->addJoinedDataToResultArray($modelMock, $data);
    $this->assertEquals($data, ['Widgets' => [$widgetData]]);
  }
}


















