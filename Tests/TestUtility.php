<?php
/**
 * This is not a unit test, but a bag of helper methods for writing tests.
 */
namespace ThirdEngine\PropelSOABundle\Tests;

use ReflectionProperty;
use ReflectionMethod;
use PropelCollection;


class TestUtility
{
  /**
   * This method will set the value of a protected property in an object.
   *
   * @param object $object
   * @param string $propertyName
   * @param mixed $value
   */
  public function setProtectedProperty($object, $propertyName, $value)
  {
    $reflectionProperty = new ReflectionProperty($object, $propertyName);
    $reflectionProperty->setAccessible(true);
    $reflectionProperty->setValue($object, $value);
  }

  /**
   * This method will set the value of a protected property in an object.
   *
   * @param object $object
   * @param string $propertyName
   */
  public function getProtectedProperty($object, $propertyName)
  {
    $reflectionProperty = new ReflectionProperty($object, $propertyName);
    $reflectionProperty->setAccessible(true);

    return $reflectionProperty->getValue($object);
  }

  /**
   * This method will call a protected method.
   *
   * @param object $object
   * @param string $methodName
   * @param array $args
   *
   * @return mixed
   */
  public function callProtectedMethod($object, $methodName, $args = [])
  {
    $reflectionMethod = new ReflectionMethod(get_class($object), $methodName);
    $reflectionMethod->setAccessible(true);

    return $reflectionMethod->invokeArgs($object, $args);
  }

  /**
   * This method will create a PropelCollection object from an array.
   *
   * @param array $elements
   * @return PropelCollection
   */
  public function convertToCollection(array $elements)
  {
    $collection = new PropelCollection();
    $collection->setData($elements);

    return $collection;
  }
}