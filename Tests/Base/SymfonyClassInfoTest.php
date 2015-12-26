<?php
namespace ThirdEngine\PropelSOABundle\Tests\Base;

use ThirdEngine\PropelSOABundle\Base\SymfonyClassInfo;

use Exception;
use Symfony\Bundle\FrameworkBundle\Tests;


class SymfonyClassInfoTest extends Tests\TestCase
{
  public function testCreateClassInfoSetsData()
  {
    $namespace = 'namespace';
    $bundle = 'bundle';
    $entity = 'entity';

    $classInfo = SymfonyClassInfo::createClassInfo($namespace, $bundle, $entity);

    $this->assertEquals($namespace, $classInfo->namespace);
    $this->assertEquals($bundle, $classInfo->bundle);
    $this->assertEquals($entity, $classInfo->entity);
  }

  public function testGetClassPathThrowsExceptionForUnknownClassType()
  {
    $classInfo = SymfonyClassInfo::createClassInfo('namespace', 'bundle', 'entity');

    $this->setExpectedException(Exception::class, 'NO_SUCH_CLASS_TYPE');
    $classInfo->getClassPath('NO_SUCH_CLASS_TYPE');
  }

  public function testGetClassPathReturnsModelPath()
  {
    $namespace = 'namespace';
    $bundle = 'bundle';
    $entity = 'entity';

    $classInfo = SymfonyClassInfo::createClassInfo($namespace, $bundle, $entity);
    $this->assertEquals(
      "\\namespace\\bundleBundle\\Model\\entity",
      $classInfo->getClassPath(SymfonyClassInfo::SYMFONY_CLASSINFO_MODEL)
    );
  }

  public function testGetClassPathReturnsPeerPath()
  {
    $namespace = 'namespace';
    $bundle = 'bundle';
    $entity = 'entity';

    $classInfo = SymfonyClassInfo::createClassInfo($namespace, $bundle, $entity);
    $this->assertEquals(
      "\\namespace\\bundleBundle\\Model\\entityPeer",

      $classInfo->getClassPath(SymfonyClassInfo::SYMFONY_CLASSINFO_PEER)
    );
  }

  public function testGetClassPathReturnsQueryPath()
  {
    $namespace = 'namespace';
    $bundle = 'bundle';
    $entity = 'entity';

    $classInfo = SymfonyClassInfo::createClassInfo($namespace, $bundle, $entity);
    $this->assertEquals(
      "\\namespace\\bundleBundle\\Model\\entityQuery",

      $classInfo->getClassPath(SymfonyClassInfo::SYMFONY_CLASSINFO_QUERY)
    );
  }

  public function testGetClassPathReturnsControllerPath()
  {
    $namespace = 'namespace';
    $bundle = 'bundle';
    $entity = 'entity';

    $classInfo = SymfonyClassInfo::createClassInfo($namespace, $bundle, $entity);
    $this->assertEquals(
      "\\namespace\\bundleBundle\\Controller\\entityController",

      $classInfo->getClassPath(SymfonyClassInfo::SYMFONY_CLASSINFO_CONTROLLER)
    );
  }

  public function testParseClassPathGetsNamespaceBundleAndEntity()
  {
    $classInfo = new SymfonyClassInfo();
    $classInfo->parseClassPath("SOA\\SOABundle\\Model\\ItemQuery");

    $this->assertEquals('SOA', $classInfo->namespace);
    $this->assertEquals('SOA', $classInfo->bundle);
    $this->assertEquals('Item', $classInfo->entity);
  }
}