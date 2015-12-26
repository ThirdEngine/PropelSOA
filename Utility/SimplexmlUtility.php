<?php
/**
 * This class will provide some utility methods that wrap standard simplexml functions.
 */
namespace ThirdEngine\PropelSOABundle\Utility;

use SimpleXMLElement;

class SimplexmlUtility
{
  /**
   * This method will load the contents of a file into a simplexml object.
   *
   * @codeCoverageIgnore
   *
   * @param string $fileName
   * @return SimpleXMLElement
   */
  public function loadFile($fileName)
  {
    return simplexml_load_file($fileName);
  }

  /**
   * This method will load the contents of a string into a simplexml object.
   *
   * @param string $content
   * @return SimpleXMLElement
   */
  public function loadString($content)
  {
    return simplexml_load_string($content);
  }

  /**
   * This method will take a simplexml element and return a DOMElement.
   *
   * @param SimpleXMLElement $simplexml
   * @return DOMElement
   */
  public function domImport(SimpleXMLElement $simplexml)
  {
    return dom_import_simplexml($simplexml);
  }

  /**
   * This method will remove the node passed in from the XML document it belongs to.
   *
   * @param SimpleXMLElement $elementxml
   */
  public function removeNode(SimpleXMLElement $elementxml)
  {
    $dom = $this->domImport($elementxml);
    $dom->parentNode->removeChild($dom);
  }
}