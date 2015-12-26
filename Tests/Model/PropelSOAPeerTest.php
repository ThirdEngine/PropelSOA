<?php
namespace SOA\SOABundle\Tests\Model;

use SOA\SOABundle\Model\PropelSOAPeer;

use PropelCollection;
use Symfony\Bundle\FrameworkBundle\Tests;


class PropelSOAPeerTest extends Tests\TestCase
{
  public function testPopulateLinkedDataCallsSetLinkedDataOnOneLevelRelation()
  {
    $linkedDataRelations = [
      'WidgetRating',
    ];

    $widget1Model = $this->getMock(stdClass::class, ['setLinkedData', 'getWidgetRating']);
    $widget1Model->expects($this->once())
      ->method('setLinkedData')
      ->with($this->equalTo('WidgetRating'));
    $widget1Model->expects($this->any())
      ->method('getWidgetRating')
      ->will($this->returnValue(33));

    $widgets = new PropelCollection();
    $widgets->setData([$widget1Model]);

    $soaPeer = new PropelSOAPeer();
    $soaPeer->populateLinkedData($widgets, $linkedDataRelations);
  }

  public function testPopulateLinkedDataCallsSetLinkedDataOnTwoLevelRelations()
  {
    $linkedDataRelations = [
      'Statistic->Rating',
    ];

    $widget1Model = $this->getMock(
      stdClass::class,
      ['setLinkedData', 'getStatistic', 'getRating']
    );

    $widget1Model->expects($this->once())
      ->method('setLinkedData')
      ->with($this->equalTo('Rating'));
    $widget1Model->expects($this->any())
      ->method('getStatistic')
      ->will($this->returnValue($widget1Model));
    $widget1Model->expects($this->any())
      ->method('getRating')
      ->will($this->returnValue(33));


    $widgets = new PropelCollection();
    $widgets->setData([$widget1Model]);

    $soaPeer = new PropelSOAPeer();
    $soaPeer->populateLinkedData($widgets, $linkedDataRelations);
  }

  public function testPopulateLinkedDataDoesNotCallSetLinkedDataWhenEventualRecordNotPresent()
  {
    $linkedDataRelations = [
      'Part->CompletionDate',
    ];

    $partMock = $this->getMock(stdClass::class, ['setLinkedData', 'getCompletionDate']);
    $partMock->expects($this->any())
      ->method('getCompletionDate')
      ->will($this->returnValue('2015-01-18'));
    $partMock->expects($this->any())
      ->method('setLinkedData')
      ->with($this->equalTo('CompletionDate'));

    $partsCollection = new PropelCollection();
    $partsCollection->setData([$partMock]);

    $widget1Model = $this->getMock(stdClass::class, ['getParts']);
    $widget1Model->expects($this->any())
      ->method('getParts')
      ->will($this->returnValue($partsCollection));

    // This will demonstrate the case where a relation is not present for this model
    $widget2Model = $this->getMock(
      stdClass::class,
      ['setLinkedData', 'getParts']
    );

    $widget2Model->expects($this->any())
      ->method('getParts')
      ->will($this->returnValue([]));
    $widget2Model->expects($this->never())
      ->method('setLinkedData');

    $widgets = new PropelCollection();
    $widgets->setData([$widget1Model, $widget2Model]);

    $soaPeer = new PropelSOAPeer();
    $soaPeer->populateLinkedData($widgets, $linkedDataRelations);
  }

  public function testDoesNothingWhenNoTargetModelsFound()
  {
    $linkedDataRelation = 'Part->CompletionDate';
    $models = [];

    $peerMock = $this->getMock(PropelSOAPeer::class, ['getTargetModels']);
    $peerMock->expects($this->once())
      ->method('getTargetModels')
      ->with($this->equalTo($models), $this->equalTo([]))
      ->will($this->returnValue([]));

    $peerMock->prepopulateForLinkedData($models, [$linkedDataRelation]);
  }

  public function testModelDoesNotHydrateWhenNoHydrationDefined()
  {
    $linkedDataRelation = 'Part';
    $hydrationMethod = 'hydrateSomeStuff';

    $peerMock = $this->getMock(PropelSOAPeer::class, []);
    $peerMock->strategiesForHydratingLInkedData = [];

    $widgetMock = $this->getMock(PropelSOAModel::class, ['getPeer', $hydrationMethod]);
    $widgetMock->expects($this->any())
      ->method('getPeer')
      ->will($this->returnValue($peerMock));
    $widgetMock->expects($this->never())
      ->method($hydrationMethod);

    $models = [$widgetMock];
    $peerMock->prepopulateForLinkedData($models, [$linkedDataRelation]);
  }

  public function testModelHydratesWhenHydrationDefined()
  {
    $linkedDataRelation = 'Part->CompletionDate';
    $hydrationMethod = 'hydrateSomeStuff';

    $partPeerMock = $this->getMock(PropelSOAPeer::class, ['test']);
    $partPeerMock->strategiesForHydratingLinkedData = ['CompletionDate' => $hydrationMethod];

    $partMock = $this->getMock(PropelSOAModel::class, ['getPeer', $hydrationMethod]);
    $partMock->expects($this->any())
      ->method('getPeer')
      ->will($this->returnValue($partPeerMock));
    $partMock->expects($this->once())
      ->method($hydrationMethod);

    $widgetPeerMock = $this->getMock(PropelSOAPeer::class, ['test']);
    $widgetPeerMock->strategiesForHydratingLinkedData = [];

    $widgetMock = $this->getMock(PropelSOAModel::class, ['getPeer', 'getPart']);
    $widgetMock->expects($this->any())
      ->method('getPeer')
      ->will($this->returnValue($widgetPeerMock));
    $widgetMock->expects($this->once())
      ->method('getPart')
      ->will($this->returnValue($partMock));

    $models = [$widgetMock];
    $widgetPeerMock->prepopulateForLinkedData($models, [$linkedDataRelation]);
  }
}