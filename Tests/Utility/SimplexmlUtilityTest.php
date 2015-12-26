<?php
namespace SOA\SOABundle\Tests\Utility;

use SOA\SOABundle\Utility\SimplexmlUtility;

use SimpleXMLElement;
use DOMElement;
use Symfony\Bundle\FrameworkBundle\Tests;


class SimplexmlUtilityTest extends Tests\TestCase
{
  public function testLoadStringCreatesSimpleXMLElement()
  {
    $simplexmlUtility = new SimplexmlUtility();
    $simplexml = $simplexmlUtility->loadString('<?xml version="1.0" encoding="UTF-8"?><document/>');

    $this->assertInstanceOf(SimpleXMLElement::class, $simplexml);
  }

  public function testDomImportReturnsDOMElement()
  {
    $simplexmlUtility = new SimplexmlUtility();
    $simplexml = $simplexmlUtility->loadString('<?xml version="1.0" encoding="UTF-8"?><document/>');
    $domElement = $simplexmlUtility->domImport($simplexml);

    $this->assertInstanceOf(DOMElement::class, $domElement);
  }

  public function testRemoveNodeRemovesCurrentNodeFromParent()
  {
    $simplexmlUtility = new SimplexmlUtility();
    $simplexml = $simplexmlUtility->loadString('<?xml version="1.0" encoding="UTF-8"?><document><book>Atlas Shrugged</book><book>Moby Dick</book></document>');

    $simplexmlUtility->removeNode($simplexml->book[0]);
    $bookName = (string) $simplexml->book[0];

    $this->assertEquals('Moby Dick', $bookName);
  }
}