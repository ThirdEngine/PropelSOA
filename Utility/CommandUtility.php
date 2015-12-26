<?php
/**
 * This class will house methods that are useful for dealing with command line tools. This is mostly for wrappers
 * around php functions for testing.
 *
 * @copyright 2015 Latent Codex / Third Engine Software
 */
namespace SOA\SOABundle\Utility;

class CommandUtility
{
  /**
   * This method wraps the exec() php function.
   *
   * @codeCoverageIgnore
   *
   * @param string $command
   * @param array $output
   * @param int $returnVar
   *
   * @return string
   */
  public function exec($command, &$output = [], &$returnVar = 0)
  {
    return exec($command, $output, $returnVar);
  }

  /**
   * This method wraps the file_get_contents() php function.
   *
   * @codeCoverageIgnore
   *
   * @param string $source
   * @return string
   */
  public function fileGetContents($source)
  {
    return file_get_contents($source);
  }

  /**
   * This method wraps the file_put_contents() php function.
   *
   * @codeCoverageIgnore
   *
   * @param string $fileName
   * @param string $content
   */
  public function filePutContents($fileName, $content)
  {
    file_put_contents($fileName, $content);
  }
}