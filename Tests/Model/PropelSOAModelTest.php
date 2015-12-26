<?php
namespace ThirdEngine\PropelSOABundle\Tests\Model;

use ThirdEngine\PropelSOABundle\Model\PropelSOAPeer;
use ThirdEngine\PropelSOABundle\Model\PropelSOAModel;

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