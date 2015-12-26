<?php
/**
 * This class defines a linked data relation as used by the SOA bundle.
 */
namespace ThirdEngine\PropelSOABundle\Base;

use ThirdEngine\PropelSOABundle\Model\PropelSOAModel;
use ThirdEngine\Factory\Factory;


class LinkedData extends DataRelation
{
  /**
   * This method will default our relationType to be "linkeddata"
   */
  public function __construct()
  {
    $this->relationType = 'linkeddata';
  }

  /**
   * This method will add the data pulled in by our join to an array representation of a model.
   *
   * @param PropelSOAModel $model
   * @param array $data
   */
  public function addJoinedDataToResultArray(PropelSOAModel $model, array &$data)
  {
    $classInfo = Factory::createNewObject(SymfonyClassInfo::class);
    $classInfo->parseClassPath(get_class($model));

    $peerClass = $classInfo->getClassPath('peer');
    $peer = Factory::createNewObject($peerClass);

    $getMethod = $peer->linkedData[$this->relation];
    $data[$this->relation] = $model->$getMethod();
  }
}