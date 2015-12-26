<?php

namespace ThirdEngine\PropelSOABundleBundle\Command;

use ThirdEngine\Factory\Factory;
use ThirdEngine\PropelSOABundleBundle\Utility\CommandUtility;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SoafyModelsCommand extends ContainerAwareCommand
{
  /**
   * This is an OutputInterface where we can send display information.
   *
   * @var OutputInterface
   */
  protected $output;

  /**
   * This is an InputInterface where we can get to information sent to our command.
   *
   * @var InputInterface
   */
  protected $input;

  /**
   * @var CommandUtility
   */
  protected $commandUtility;

  /**
   * These arrays contain the pieces to the fully qualified class names for the base classes that we will make all
   * model system classes extend. These will default to being PropelSOA classes, though other people can extend
   * PropelSOA classes themselves and pass those derived classes into our command.
   */
  protected $queryBaseParts = ['SOA', 'SOABundle', 'Model', 'PropelSOAQuery'];
  protected $peerBaseParts  = ['SOA', 'SOABundle', 'Model', 'PropelSOAPeer'];
  protected $modelBaseParts = ['SOA', 'SOABundle', 'Model', 'PropelSOAModel'];

  protected function configure()
  {
    $this
      ->setName('soa:soafymodels')
      ->setDescription('Change model classes to have PropelSOA features')
      ->addArgument('querybase', InputArgument::OPTIONAL, 'What is the fully qualified path of the base query class?')
      ->addArgument('peerbase',  InputArgument::OPTIONAL, 'What is the fully qualified path of the base peer class?')
      ->addArgument('modelbase', InputArgument::OPTIONAL, 'What is the fully qualified path of the base model class?')
    ;
  }


  /**
   * This method will determine the base classes that we want all model system classes throughout
   * the application to inherit from.
   */
  protected function determineBaseClasses()
  {
    $queryBase = $this->input->getArgument('querybase');
    $peerBase  = $this->input->getArgument('peerbase');
    $modelBase = $this->input->getArgument('modelbase');


    // if override values were supplied, replace the defaults

    if ($queryBase)
    {
      $this->queryBaseParts = explode('/', $queryBase);
    }
    if ($peerBase)
    {
      $this->peerBaseParts = explode('/', $peerBase);
    }
    if ($modelBase)
    {
      $this->modelBaseParts = explode('/', $modelBase);
    }
  }


  /**
   * This method will execute the main trunk of execution for this command including
   * finding model system files throughout the application.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->input  = $input;
    $this->output = $output;

    $this->commandUtility = Factory::createNewObject(CommandUtility::class);
    $this->determineBaseClasses();

    // find all model directories in the project
    $directoryList = [];
    $listDirectoryCommand = 'find ./src -type d |grep -v "SOA/SOABundle" |grep "Model/om$"';
    $this->commandUtility->exec($listDirectoryCommand, $directoryList);


    $listPeerCommand  = 'find %s -maxdepth 1 -type f |grep "Peer.php$"';
    $listQueryCommand = 'find %s -maxdepth 1 -type f |grep "Query.php$"';
    $listModelCommand = 'find %s -maxdepth 1 -type f |grep -v "Query.php$" |grep -v "Peer.php$"';

    // this is how many duplicated slashes it takes to get through all of the condensation, why can't
    // namespace paths be with pipes or something instead??
    $separator = '\\\\\\\\';

    foreach ($directoryList as $directory)
    {
      $this->replaceInFiles(
        sprintf($listPeerCommand, $directory),
        'abstract class \(.*\)Peer',
        '& extends ' . $separator . implode($separator, $this->peerBaseParts)
      );

      $this->replaceInFiles(
        sprintf($listQueryCommand, $directory),
        'extends ModelCriteria',
        'extends ' . $separator . implode($separator, $this->queryBaseParts)
      );

      $this->replaceInFiles(
        sprintf($listModelCommand, $directory),
        'extends BaseObject',
        'extends ' . $separator . implode($separator, $this->modelBaseParts)
      );
    }
  }


  /**
   * This method will run a find command and call the appropriate method to override the found classes
   * with a specified base class.
   *
   * @param string $findCommand
   * @param array  $baseClassParts
   * @param string $searchPattern
   */
  protected function replaceInFiles($findCommand, $searchPattern, $replace)
  {
    $fileList = [];
    $this->commandUtility->exec($findCommand, $fileList);

    $replaceCommand = "sed -i -e \"s/%s/%s/g\" %s";

    foreach ($fileList as $file)
    {
      $command = sprintf($replaceCommand, $searchPattern, $replace, $file, $file);
      $this->commandUtility->exec($command);
    }
  }
}