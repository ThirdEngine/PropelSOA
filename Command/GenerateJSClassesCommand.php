<?php
namespace ThirdEngine\PropelSOABundle\Command;

use ThirdEngine\Factory\Factory;
use ThirdEngine\PropelSOABundle\Base\SymfonyClassInfo;
use ThirdEngine\PropelSOABundle\Utility\CommandUtility;
use ThirdEngine\PropelSOABundle\Interfaces\ClientExtendable;
use ThirdEngine\PropelSOABundle\Controller\ModelBasedServiceController;
use ThirdEngine\PropelSOABundle\Interfaces\Collectionable;

use ReflectionClass;
use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateJSClassesCommand extends ContainerAwareCommand
{
  /**
   * @var string
   */
  protected $code = '';

  /**
   * @var CommandUtility
   */
  protected $commandUtility;


  protected function configure()
  {
    $this
      ->setName('soa:generatejsclasses')
      ->setDescription('Generate JS Classes for every model')
      ->addArgument('url', InputArgument::REQUIRED, 'What URL should be used to generate the script files?')
      ->addArgument('directory', InputArgument::REQUIRED, 'What directory under the home directory should the generated script go into?')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $baseUrl = $input->getArgument('url');
    $directoryList = [];

    $listDirectoryCommand = 'find ./src -type d |grep -v "SOA/SOABundle" |grep "Controller$"';
    $listControllerCommand = 'find %s -maxdepth 1 -type f |grep "Controller.php$"';

    $this->commandUtility = Factory::createNewObject(CommandUtility::class);
    $this->commandUtility->exec($listDirectoryCommand, $directoryList);

    foreach ($directoryList as $directory)
    {
      $controllerFileList = [];
      $this->commandUtility->exec(sprintf($listControllerCommand, $directory), $controllerFileList);

      foreach ($controllerFileList as $controllerFile)
      {
        $this->generateScript($input, $output, $controllerFile, $baseUrl);
      }
    }

    $this->writeCode($input, $output);
  }

  /**
   * This method will download one script file and save it to the proper destination.
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   * @param $controllerFilePath
   * @param $baseUrl
   */
  protected function generateScript(InputInterface $input, OutputInterface $output, $controllerFilePath, $baseUrl)
  {
    $pieces = explode('/', $controllerFilePath);
    $srcIndex = array_search('src', $pieces);

    if ($srcIndex === false)
    {
      throw new Exception('Could not find the src directory');
    }


    $params = [];
    $params['namespace'] = $pieces[$srcIndex + 1];
    $params['bundle'] = str_replace('Bundle', '', $pieces[$srcIndex + 2]);

    $lastIndex = count($pieces) - 1;
    $className = str_replace('.php', '', $pieces[$lastIndex]);
    $params['entity'] = str_replace('Controller', '', $className);


    // check to see if this controller is really a service resource

    $classInfo = $this->classInfo = Factory::createNewObject(SymfonyClassInfo::class);
    $classInfo->namespace = $params['namespace'];
    $classInfo->bundle    = $params['bundle'];
    $classInfo->entity    = $params['entity'];

    $routesToGenerate = [];
    $controllerClass = $classInfo->getClassPath('controller');
    $reflectionObject = Factory::createNewObject(ReflectionClass::class, [$controllerClass]);

    // if the controller is abstract, skip this entirely, don't implement any functionality for
    // the controller in JavaScript
    if ($reflectionObject->isAbstract())
    {
      return;
    }


    // based on reflection, we will determine which types of objects should get standard class
    // definitions in the javascript. note that objects that do not get standard definitions
    // can get custom object definitions where the developer can make the javascript do similar
    // tasks but in a non-standard way.

    if ($reflectionObject->isSubclassOf(ModelBasedServiceController::class))
    {
      // model-based controllers get query and object definitions generated. this is because
      // we can predict the data they should contain by inspecting the model, and we know
      // generally how to query for this data in a standard way through Propel

      $routesToGenerate['query']  = 'propelsoa_generatequery_route';
      $routesToGenerate['object'] = 'propelsoa_generateobject_route';
    }

    if ($reflectionObject->implementsInterface(Collectionable::class))
    {
      // a controller signals that it wants us to build a standard collection object for it
      // by implementing the Collectionable interface

      $routesToGenerate['collection'] = 'propelsoa_generatecollection_route';
    }


    // for a lot of objects, the basic generated object is not enough in javascript. we want our
    // javascript objects to be strong and contain all of their related logic and data. what
    // this means is that the generated object is a good starting point, but not the final
    // result. we can indicate with an interface that we want the generated object to not be
    // the final object loaded by getNewObject() in propelsoa.

    if ($reflectionObject->implementsInterface(ClientExtendable::class))
    {
      $routesToGenerate['object'] = 'propelsoa_generatepartialobject_route';
    }


    $router = $this->getContainer()->get('router');

    foreach ($routesToGenerate as $routeKey)
    {
      $url = $baseUrl . '/app_dev.php' . $router->generate($routeKey, $params);
      $this->code .= $this->commandUtility->fileGetContents($url);
    }
  }

  /**
   * This method will write the code file with all of the generated javascript objects.
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function writeCode(InputInterface $input, OutputInterface $output)
  {
    $directory = $input->getArgument('directory');
    $fileName = './' . $directory . '/generatedscript.js';

    $output->writeLn($fileName);
    $this->commandUtility->filePutContents($fileName, $this->code);
  }
}
