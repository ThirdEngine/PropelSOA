<?php
namespace SOA\SOABundle\Tests\Base;

use ThirdEngine\Factory\Factory;
use SOA\SOABundle\Base\JoinTree;
use SOA\SOABundle\Base\Join;
use SOA\SOABundle\Base\LinkedData;
use SOA\SOABundle\Tests\TestUtility;
use SOA\SOABundle\Base\SymfonyClassInfo;
use SOA\SOABundle\Model\PropelSOAModel;

use ArrayObject;
use BasePeer;
use ModelCriteria;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Tests;


class JoinTreeTest extends Tests\TestCase
{
  public function testSearchRelationListReturnsNegativeOneWhenRelationNotFound()
  {
    $relation1 = new stdClass();
    $relation1->relation = 'relation1';

    $relation2 = new stdClass();
    $relation2->relation = 'relation2';

    $this->assertEquals(-1, JoinTree::searchRelationList([$relation1, $relation2], 'SomeRelation'));
  }

  public function testSearchRelationListReturnsKeyOfMatchingRelation()
  {
    $relation1 = new stdClass();
    $relation1->relation = 'relation1';

    $relation2 = new stdClass();
    $relation2->relation = 'relation2';

    $relationList = [
      'r1' => $relation1,
      'r2' => $relation2,
    ];

    $this->assertEquals('r2', JoinTree::searchRelationList($relationList, $relation2->relation));
  }

  public function testAddToQueryCallsAddToQueryForJoinList()
  {
    $queryMock = $this->getMock(ModelCriteria::class, ['test'], [], '', false);

    $join1Mock = $this->getMock(Join::class, ['addToQuery']);
    $join1Mock->expects($this->once())
      ->method('addToQuery')
      ->with($this->equalTo($queryMock));

    $join2Mock = $this->getMock(Join::class, ['addToQuery']);
    $join2Mock->expects($this->once())
      ->method('addToQuery')
      ->with($this->equalTo($queryMock));

    $joinTree = new JoinTree();
    $joinTree->joinList = [$join1Mock, $join2Mock];

    $joinTree->addToQuery($queryMock);
  }

  public function testCorrectNamespaceCorrectsForAllJoinsAndDataRelations()
  {
    $queryMock = $this->getMock(ModelCriteria::class, ['test'], [], '', false);

    $join1Mock = $this->getMock(Join::class, ['correctNamespace']);
    $join1Mock->expects($this->once())
      ->method('correctNamespace')
      ->with($this->equalTo($queryMock));

    $join2Mock = $this->getMock(Join::class, ['correctNamespace']);
    $join2Mock->expects($this->once())
      ->method('correctNamespace')
      ->with($this->equalTo($queryMock));

    $linkedDataMock = $this->getMock(LinkedData::class, ['correctNamespace']);
    $linkedDataMock->expects($this->once())
      ->method('correctNamespace')
      ->with($this->equalTo($queryMock));

    $joinTree = new JoinTree();
    $joinTree->joinList = [$join1Mock, $join2Mock];
    $joinTree->dataRelationList = [$linkedDataMock];

    $joinTree->correctNamespaces($queryMock);
  }

  public function testBuildFromPostedObjectsDoesNotCreateDuplicates()
  {
    $join1 = new stdClass();
    $join1->relation = 'Widget->Part';

    $join2 = new stdClass();
    $join2->relation = 'Widget->Vendor';

    $queryDefinition = new stdClass();
    $queryDefinition->linkedData = [];
    $queryDefinition->joins = [$join1, $join2];

    $joinTree = new JoinTree();
    $joinTree->buildFromPostedObjects($queryDefinition);

    $this->assertEquals(1, count($joinTree->joinList));
    $widgetJoin = reset($joinTree->joinList);
    $this->assertEquals('Widget', $widgetJoin->relation);

    $this->assertEquals(2, count($widgetJoin->joinList));
    $this->assertEquals('Part', $widgetJoin->joinList[0]->relation);
    $this->assertEquals('Vendor', $widgetJoin->joinList[1]->relation);
  }

  public function testBuildFromPostedObjectsAddsLinkedDataRelation()
  {
    $join = new stdClass();
    $join->relation = 'Widget->Part';

    $queryDefinition = new stdClass();
    $queryDefinition->linkedData = ['Widget->Cost'];
    $queryDefinition->joins = [$join];

    $joinTree = new JoinTree();
    $joinTree->buildFromPostedObjects($queryDefinition);

    $this->assertEquals(1, count($joinTree->joinList[0]->joinList));
    $this->assertEquals(2, count($joinTree->dataRelationList[0]->joinList));
  }

  public function testOutputAsJSONAddsToArrayResultAndAddsJoinedDataToArray()
  {
    $modelData = ['id' => 3332];

    $modelMock = $this->getMock(PropelSOAModel::class, ['toArray']);
    $modelMock->expects($this->once())
      ->method('toArray')
      ->with($this->equalTo(BasePeer::TYPE_PHPNAME), $this->equalTo(false))
      ->willReturn($modelData);

    $testUtility = new TestUtility();
    $modelCollection = $testUtility->convertToCollection([$modelMock]);

    $joinMock = $this->getMock(Join::class, ['addJoinedDataToResultArray']);
    $joinMock->expects($this->once())
      ->method('addJoinedDataToResultArray')
      ->with($this->equalTo($modelMock), $this->anything())
      ->will($this->returnCallback(function ($model, &$data) {
        $data['value'] = 'hello';
      }));

    $joinTree = $this->getMock(JoinTree::class, ['JSONFromArray']);
    $joinTree->dataRelationList = [$joinMock];

    $joinTree->expects($this->once())
      ->method('JSONFromArray')
      ->with($this->equalTo([['id' => $modelData['id'], 'value' => 'hello']]));

    $joinTree->outputAsJSON($modelCollection);
  }
}
