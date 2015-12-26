<?php
/**
 * This class will provide some utility methods to help parse docblocks and take the appropriate actions.
 *
 * @copyright 2014 Latent Codex / Third Engine Software
 */
namespace SOA\SOABundle\Utility;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationReader;
use Composer\Autoload\ClassLoader;


class DocBlockUtility
{
  /**
   * This method will set the docblock annotations that we need Doctrine to ignore.
   *
   * @codeCoverageIgnore
   * @param ClassLoader $loader
   */
  public static function allowAnnotations($loader)
  {
    AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

    // this will keep the doctrine DocParser from throwing exceptions at our @route annotations in PropelSOA
    // enable controllers
    $reader = new AnnotationReader();
    AnnotationReader::addGlobalIgnoredName('Route');
    AnnotationReader::addGlobalIgnoredName('Method');
    AnnotationReader::addGlobalIgnoredName('route');
    AnnotationReader::addGlobalIgnoredName('QueryParameter');
    AnnotationReader::addGlobalIgnoredName('willAccept');
    AnnotationReader::addGlobalIgnoredName('wontAccept');
  }

  /**
   * This method will get the value from a doc block for a specific annotation. This is for a case
   * where there should be only one result.
   *
   * @param string $docComment
   * @param string $annotation
   * @return string
   */
  public function getAnnotationValue($docComment, $annotation)
  {
    $annotationValues = $this->getAllAnnotationValues($docComment, $annotation);
    return count($annotationValues) > 0 ? reset($annotationValues) : null;
  }

  /**
   * This method will get an array with all values in a doc block for a specific annotation.
   *
   * @param string $docComment
   * @param string $annotation
   * @return array
   */
  public function getAllAnnotationValues($docComment, $annotation)
  {
    // this will allow either "param" or "@param" to be supplied, this is just for convenience
    $annotation = '@' . ltrim($annotation, '@');
    $lines = explode("\n", $docComment);

    $values = [];

    foreach ($lines as $line)
    {
      $tagIndex = strpos($line, $annotation);

      if ($tagIndex !== false)
      {
        $valueStartPosition = $tagIndex + strlen($annotation);
        $value = substr($line, $valueStartPosition);

        $values[] = trim($value);
      }
    }

    return $values;
  }
}






















