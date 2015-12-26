<?php
namespace SOA\SOABundle\Tests\Model;

use SOA\SOABundle\Model\PropelSOAPeer;
use SOA\SOABundle\Model\PropelSOAModel;

use PropelCollection;
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
}