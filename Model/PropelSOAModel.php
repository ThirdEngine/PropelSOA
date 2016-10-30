<?php
/**
 * This class defines a base class for model objects. This will allow setup of
 * extra information that can be queried for models.
 */
namespace ThirdEngine\PropelSOABundle\Model;

use ThirdEngine\Factory\Factory;
use ThirdEngine\PropelSOABundle\Base\SymfonyClassInfo;

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
   * This method will return this tables TableMap object.
   *
   * @return TableMap
   */
  public function getTableMap()
  {
    $classInfo = Factory::createNewObject(SymfonyClassInfo::class);
    $classInfo->parseClassPath(get_class($this));

    $queryClass = $classInfo->getClassPath('query');
    $query = Factory::createNewQueryObject($queryClass);

    return $query->getTableMap();
  }

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