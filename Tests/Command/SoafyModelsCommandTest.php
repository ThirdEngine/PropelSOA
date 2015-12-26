<?php
namespace ThirdEngine\PropelSOABundle\Tests\Command;

use ThirdEngine\PropelSOABundle\Base\SymfonyClassInfo;
use ThirdEngine\PropelSOABundle\Command\SoafyModelsCommand;
use ThirdEngine\PropelSOABundle\Tests\CommandMock;
use ThirdEngine\Factory\Factory;
use ThirdEngine\PropelSOABundle\Utility\CommandUtility;
use ThirdEngine\PropelSOABundle\Tests\TestUtility;
use ThirdEngine\PropelSOABundle\Controller\ModelBasedServiceController;
use ThirdEngine\PropelSOABundle\Interfaces\Collectionable;
use ThirdEngine\PropelSOABundle\Interfaces\ClientExtendable;

use stdClass;
use ReflectionClass;
use Exception;
use Symfony\Bundle\FrameworkBundle\Tests;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;


class SoafyModelsCommandMock extends SoafyModelsCommand
{
  use CommandMock;
}

class SoafyModelsCommandTest extends Tests\TestCase
{
  public function testConfigureSetsNameAndArguments()
  {
    $commandMock = $this->getMock(SoafyModelsCommandMock::class, ['setName', 'setDescription', 'addArgument'], [], '', false);
    $commandMock->expects($this->once())
      ->method('setName')
      ->with($this->equalTo('soa:soafymodels'))
      ->will($this->returnValue($commandMock));
    $commandMock->expects($this->once())
      ->method('setDescription')
      ->will($this->returnValue($commandMock));
    $commandMock->expects($this->at(2))
      ->method('addArgument')
      ->with($this->equalTo('querybase'), $this->equalTo(InputArgument::OPTIONAL), $this->anything())
      ->will($this->returnValue($commandMock));
    $commandMock->expects($this->at(3))
      ->method('addArgument')
      ->with($this->equalTo('peerbase'), $this->equalTo(InputArgument::OPTIONAL), $this->anything())
      ->will($this->returnValue($commandMock));
    $commandMock->expects($this->at(4))
      ->method('addArgument')
      ->with($this->equalTo('modelbase'), $this->equalTo(InputArgument::OPTIONAL), $this->anything())
      ->will($this->returnValue($commandMock));

    $commandMock->publicConfigure();
  }

  public function testExecuteReplacesBaseClassesAndLinkedDataMergeInFiles()
  {
    $inputMock = $this->getMock(ArrayInput::class, ['getArgument'], [], '', false);
    $inputMock->expects($this->exactly(3))
      ->method('getArgument')
      ->will($this->returnValueMap([
        ['querybase', 'Project/PlanBundle/WidgetQuery'],
        ['peerbase', 'Project/PlanBundle/WidgetPeer'],
        ['modelbase', 'Project/PlanBundle/Widget'],
      ]));

    $commandUtilityMock = $this->getMock(CommandUtility::class, ['exec']);

    // list directories command
    $commandUtilityMock->expects($this->at(0))
      ->method('exec')
      ->with($this->equalTo('find ./src -type d |grep -v "SOA/SOABundle" |grep "Model/om$"'))
      ->will($this->returnCallback(function ($command, &$output, &$returnVar = 0) {
        $output[] = 'src/Project/PlanBundle/Model/om';
      }));

    // list peers command
    $commandUtilityMock->expects($this->at(1))
      ->method('exec')
      ->with($this->equalTo('find src/Project/PlanBundle/Model/om -maxdepth 1 -type f |grep "Peer.php$"'))
      ->will($this->returnCallback(function ($command, &$output, &$returnVar = 0) {
        $output[] = 'src/Project/PlanBundle/Model/om/WidgetPeer.php';
      }));

    // replace in peers
    $commandUtilityMock->expects($this->at(2))
      ->method('exec');

    // list queries command
    $commandUtilityMock->expects($this->at(3))
      ->method('exec')
      ->with($this->equalTo('find src/Project/PlanBundle/Model/om -maxdepth 1 -type f |grep "Query.php$"'))
      ->will($this->returnCallback(function ($command, &$output, &$returnVar = 0) {
        $output[] = 'src/Project/PlanBundle/Model/om/WidgetQuery.php';
      }));

    // replace in queries
    $commandUtilityMock->expects($this->at(4))
      ->method('exec');

    // list models command
    $commandUtilityMock->expects($this->at(5))
      ->method('exec')
      ->with($this->equalTo('find src/Project/PlanBundle/Model/om -maxdepth 1 -type f |grep -v "Query.php$" |grep -v "Peer.php$"'))
      ->will($this->returnCallback(function ($command, &$output, &$returnVar = 0) {
        $output[] = 'src/Project/PlanBundle/Model/om/Widget.php';
      }));

    // replace in models
    $commandUtilityMock->expects($this->at(6))
      ->method('exec');

    Factory::injectObject(CommandUtility::class, $commandUtilityMock);

    $command = new SoafyModelsCommandMock();
    $command->publicExecute($inputMock, new NullOutput());
  }
}