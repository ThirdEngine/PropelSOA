<?php
/**
 * This trait will provide some methods that make it easier to directly call protected command methods
 * in unit tests. This is meant to be included in test doubles that extends the Symfony command object.
 *
 * @copyright 2015 Latent Codex / Third Engine Software
 */
namespace SOA\SOABundle\Tests;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


trait CommandMock
{
  public function publicConfigure()
  {
    $this->configure();
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  public function publicExecute(InputInterface $input, OutputInterface $output)
  {
    $this->execute($input, $output);
  }
}