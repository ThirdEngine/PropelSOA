<?php
namespace SOA\SOABundle\Tests\Command;

use SOA\SOABundle\Base\SymfonyClassInfo;
use SOA\SOABundle\Command\GenerateJSClassesCommand;
use SOA\SOABundle\Tests\CommandMock;
use ThirdEngine\Factory\Factory;
use SOA\SOABundle\Utility\CommandUtility;
use SOA\SOABundle\Tests\TestUtility;
use SOA\SOABundle\Controller\ModelBasedServiceController;
use SOA\SOABundle\Interfaces\Collectionable;
use SOA\SOABundle\Interfaces\ClientExtendable;

use stdClass;
use ReflectionClass;
use Exception;
use Symfony\Bundle\FrameworkBundle\Tests;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;


class GenerateJSClassesCommandMock extends GenerateJSClassesCommand
{
  use CommandMock;
}

class GenerateJSClassesCommandTest extends Tests\TestCase
{
  public function testConfigureSetsNameAndArguments()
  {
    $commandMock = $this->getMock(GenerateJSClassesCommandMock::class, ['setName', 'setDescription', 'addArgument'], [], '', false);
    $commandMock->expects($this->once())
      ->method('setName')
      ->with($this->equalTo('soa:generatejsclasses'))
      ->will($this->returnValue($commandMock));
    $commandMock->expects($this->once())
      ->method('setDescription')
      ->will($this->returnValue($commandMock));
    $commandMock->expects($this->at(2))
      ->method('addArgument')
      ->with($this->equalTo('url'), $this->equalTo(InputArgument::REQUIRED), $this->anything())
      ->will($this->returnValue($commandMock));
    $commandMock->expects($this->at(3))
      ->method('addArgument')
      ->with($this->equalTo('directory'), $this->equalTo(InputArgument::REQUIRED), $this->anything())
      ->will($this->returnValue($commandMock));

    $commandMock->publicConfigure();
  }

  public function testExecuteThrowsExceptionWhereControllerNotInSrcDirectory()
  {
    $baseUrl = 'http://nowhere.mysite.com';

    $inputMock = $this->getMock(ArrayInput::class, ['getArgument'], [], '', false);
    $inputMock->expects($this->once())
      ->method('getArgument')
      ->with($this->equalTo('url'))
      ->will($this->returnValue($baseUrl));

    $commandUtilityMock = $this->getMock(CommandUtility::class, ['exec']);
    $commandUtilityMock->expects($this->at(0))
      ->method('exec')
      ->will($this->returnCallback(function($command, &$output, &$returnVar = 0) {
        $output[] = 'junk/Controller';
      }));

    $commandUtilityMock->expects($this->at(1))
      ->method('exec')
      ->will($this->returnCallback(function($command, &$output, &$returnVar = 0) {
        $output[] = 'junk/Controller/DataController.php';
      }));

    Factory::injectObject(CommandUtility::class, $commandUtilityMock);

    $command = new GenerateJSClassesCommandMock();

    $this->setExpectedException(Exception::class, 'Could not find the src directory');
    $command->publicExecute($inputMock, new NullOutput());
  }

  public function testExecuteDoesNotAlterCodeWhenControllerFoundIsDeclaredAbstract()
  {
    $baseUrl = 'http://nowhere.mysite.com';
    $controllerClass = 'MyNamespace/ProjectBundle/Controller/DataController';

    $inputMock = $this->getMock(ArrayInput::class, ['getArgument'], [], '', false);
    $inputMock->expects($this->once())
      ->method('getArgument')
      ->with($this->equalTo('url'))
      ->will($this->returnValue($baseUrl));

    $commandUtilityMock = $this->getMock(CommandUtility::class, ['exec']);
    $commandUtilityMock->expects($this->at(0))
      ->method('exec')
      ->will($this->returnCallback(function($command, &$output, &$returnVar = 0) {
        $output[] = 'src/MyNamespace/ProjectBundle/Controller';
      }));

    $commandUtilityMock->expects($this->at(1))
      ->method('exec')
      ->will($this->returnCallback(function($command, &$output, &$returnVar = 0) {
        $output[] = 'src/MyNamespace/ProjectBundle/Controller/DataController.php';
      }));

    $classInfoMock = $this->getMock(SymfonyClassInfo::class, ['getClassPath']);
    $classInfoMock->expects($this->once())
      ->method('getClassPath')
      ->with($this->equalTo('controller'))
      ->will($this->returnValue($controllerClass));

    $reflectionMock = $this->getMock(ReflectionClass::class, ['isAbstract'], [], '', false);
    $reflectionMock->expects($this->once())
      ->method('isAbstract')
      ->will($this->returnValue(true));

    Factory::injectObject(CommandUtility::class, $commandUtilityMock);
    Factory::injectObject(SymfonyClassInfo::class, $classInfoMock);
    Factory::injectObject(ReflectionClass::class, $reflectionMock);

    $command = $this->getMock(GenerateJSClassesCommandMock::class, ['writeCode']);
    $command->expects($this->once())
      ->method('writeCode');

    $command->publicExecute($inputMock, new NullOutput());

    $testUtility = new TestUtility();
    $this->assertEquals('', $testUtility->getProtectedProperty($command, 'code'));
  }

  public function testExecuteAppendsGeneratedCode()
  {
    $baseUrl = 'http://site.com';
    $controllerClass = 'MyNamespace/ProjectBundle/Controller/DataController';

    $inputMock = $this->getMock(ArrayInput::class, ['getArgument'], [], '', false);
    $inputMock->expects($this->exactly(2))
      ->method('getArgument')
      ->will($this->returnValueMap([
        ['url', $baseUrl],
        ['directory', 'js/src'],
      ]));

    $commandUtilityMock = $this->getMock(CommandUtility::class, ['exec', 'fileGetContents', 'filePutContents']);

    $commandUtilityMock->expects($this->at(0))
      ->method('exec')
      ->will($this->returnCallback(function($command, &$output, &$returnVar = 0) {
        $output[] = 'src/MyNamespace/ProjectBundle/Controller';
      }));
    $commandUtilityMock->expects($this->at(1))
      ->method('exec')
      ->will($this->returnCallback(function($command, &$output, &$returnVar = 0) {
        $output[] = 'src/MyNamespace/ProjectBundle/Controller/DataController.php';
      }));

    $commandUtilityMock->expects($this->exactly(3))
      ->method('fileGetContents')
      ->will($this->returnValueMap([
        ['http://site.com/app_dev.php/query', 'query'],
        ['http://site.com/app_dev.php/collection', 'collection'],
        ['http://site.com/app_dev.php/partial', 'partial'],
      ]));

    $commandUtilityMock->expects($this->once())
      ->method('filePutContents')
      ->with($this->equalTo('./js/src/generatedscript.js'), $this->equalTo('querypartialcollection'));

    $classInfoMock = $this->getMock(SymfonyClassInfo::class, ['getClassPath']);
    $classInfoMock->expects($this->once())
      ->method('getClassPath')
      ->with($this->equalTo('controller'))
      ->will($this->returnValue($controllerClass));

    $reflectionMock = $this->getMock(ReflectionClass::class, ['isAbstract', 'isSubclassOf', 'implementsInterface'], [], '', false);
    $reflectionMock->expects($this->once())
      ->method('isAbstract')
      ->will($this->returnValue(false));
    $reflectionMock->expects($this->once())
      ->method('isSubclassOf')
      ->with($this->equalTo(ModelBasedServiceController::class))
      ->will($this->returnValue(true));
    $reflectionMock->expects($this->exactly(2))
      ->method('implementsInterface')
      ->will($this->returnValueMap([
        [Collectionable::class, true],
        [ClientExtendable::class, true],
      ]));

    $routerParams = [
      'namespace' => 'MyNamespace',
      'bundle' => 'Project',
      'entity' => 'Data',
    ];

    $routerMock = $this->getMock(stdClass::class, ['generate']);
    $routerMock->expects($this->exactly(3))
      ->method('generate')
      ->will($this->returnValueMap([
        ['propelsoa_generatequery_route', $routerParams, '/query'],
        ['propelsoa_generatecollection_route', $routerParams, '/collection'],
        ['propelsoa_generatepartialobject_route', $routerParams, '/partial'],
      ]));

    $containerMock = $this->getMock(stdClass::class, ['get']);
    $containerMock->expects($this->once())
      ->method('get')
      ->with($this->equalTo('router'))
      ->will($this->returnValue($routerMock));

    Factory::injectObject(CommandUtility::class, $commandUtilityMock);
    Factory::injectObject(SymfonyClassInfo::class, $classInfoMock);
    Factory::injectObject(ReflectionClass::class, $reflectionMock);

    $command = $this->getMock(GenerateJSClassesCommandMock::class, ['getContainer']);
    $command->expects($this->once())
      ->method('getContainer')
      ->will($this->returnValue($containerMock));

    $command->publicExecute($inputMock, new NullOutput());
  }
}