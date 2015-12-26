<?php
namespace SOA\SOABundle\Tests\Utility;

use SOA\SOABundle\Utility\DocBlockUtility;

use Symfony\Bundle\FrameworkBundle\Tests;


class DocBlockUtilityTest extends Tests\TestCase
{
  public function testGetAllAnnotationValuesReturnsEmptyArrayWhenTagNotFound()
  {
    $docBlock = " * @param nothing\n"
      . " * @param something else\n";

    $docBlockUtility = new DocBlockUtility();
    $this->assertEquals([], $docBlockUtility->getAllAnnotationValues($docBlock, '@method'));
  }

  public function testGetAllAnnotationValuesReturnsSingleElementForOneMatchingTag()
  {
    $docBlock = " * @param nothing\n"
      . " * @param something else\n"
      . " * @method myMethod\n";

    $docBlockUtility = new DocBlockUtility();
    $this->assertEquals(['myMethod'], $docBlockUtility->getAllAnnotationValues($docBlock, '@method'));
  }

  public function testGetAllAnnotationValuesReturnsAllMatchingElements()
  {
    $docBlock = " * @param nothing\n"
      . " * @param something else\n"
      . " * @method myMethod\n";

    $docBlockUtility = new DocBlockUtility();
    $this->assertEquals(['nothing', 'something else'], $docBlockUtility->getAllAnnotationValues($docBlock, '@param'));
  }

  public function testGetAnnotationValueReturnsNullWhenTagNotFound()
  {
    $docBlock = " * @param nothing\n"
      . " * @param something else\n";

    $docBlockUtility = new DocBlockUtility();
    $this->assertEquals(null, $docBlockUtility->getAnnotationValue($docBlock, '@method'));
  }

  public function testGetAnnotationValueReturnsFirstMatch()
  {
    $docBlock = " * @param nothing\n"
      . " * @param something else\n";

    $docBlockUtility = new DocBlockUtility();
    $this->assertEquals('nothing', $docBlockUtility->getAnnotationValue($docBlock, '@param'));
  }
}