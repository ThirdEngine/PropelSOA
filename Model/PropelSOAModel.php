<?php
/**
 * This class defines a base class for model objects. This will allow setup of
 * extra information that can be queried for models.
 */
namespace ThirdEngine\PropelSOABundleBundle\Model;

use Engine\EngineBundle\Utility\DateTimeUtility;

use BasePeer;
use BaseObject;


class PropelSOAModel extends BaseObject
{
  /**
   * This is an array of linked data that has been determined.
   *
   * @var array
   */
  public $linkedData = [];


  /**
   * This method will set a particular piece of linked data on our model.
   *
   * @param $linkedDataKey
   */
  public function setLinkedData($linkedDataKey)
  {
    $peer = $this->getPeer();
    $method = $peer->linkedData[$linkedDataKey];

    $this->linkedData[$linkedDataKey] = $this->$method();
  }
}