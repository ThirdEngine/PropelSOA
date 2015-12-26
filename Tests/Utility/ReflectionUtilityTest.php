<?php
namespace ThirdEngine\PropelSOABundle\Tests\Utility;

use ThirdEngine\PropelSOABundle\Utility\ReflectionUtility;

use Symfony\Bundle\FrameworkBundle\Tests;


class ReflectionBaseClass
{
  public function theBaseMethod()
  {
  }
}

class ReflectionDerivedClass extends ReflectionBaseClass
{
  public function theDerivedMethod()
  {
  }
}


class ReflectionUtilityTest extends Tests\TestCase
{
  public function testClassThatDoesNotImplementMethodAtAllReturnsFalse()
  {
    $reflectionUtility = new ReflectionUtility();
    $this->assertFalse($reflectionUtility->classDefinesMethod(ReflectionDerivedClass::class, 'theOtherMethod'));
  }

  public function testClassThatDoesImplementMethodReturnsTrue()
  {
    $reflectionUtility = new ReflectionUtility();
    $this->assertTrue($reflectionUtility->classDefinesMethod(ReflectionDerivedClass::class, 'theDerivedMethod'));
  }

  public function testClassThatInheritsMethodWithoutOverrideReturnsFalse()
  {
    $reflectionUtility = new ReflectionUtility();
    $this->assertFalse($reflectionUtility->classDefinesMethod(ReflectionDerivedClass::class, 'theBaseMethod'));
  }
}