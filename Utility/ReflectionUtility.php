<?php
/**
 * This class will provide some utility methods to enhance the existing reflection functionality in PHP. This
 * is mainly to shorten already existing operations.
 *
 * @copyright 2014 Latent Codex / Third Engine Software
 */
namespace ThirdEngine\PropelSOABundle\Utility;

use Exception;
use ReflectionMethod;


class ReflectionUtility
{
  /**
   * This method will determine if a class has a particular method and if that class defined the particular
   * method explicitly. Methods that are inherited but not overridden will return false.
   *
   * @param string $classPath
   * @param string $methodName
   *
   * @return bool
   */
  public static function classDefinesMethod($classPath, $methodName)
  {
    try
    {
      $reflectionMethod = new ReflectionMethod(new $classPath(), $methodName);
      return $reflectionMethod->getDeclaringClass()->getName() == ltrim($classPath, '\\');
    }
    catch(Exception $e)
    {
      // do nothing, just drop to return false. This means that the class didn't have the method at all.
    }

    return false;
  }
}