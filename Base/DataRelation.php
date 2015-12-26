<?php
/**
 * This class defines a data relation. This could either be the path to linked
 * data or to a real table join.
 */
namespace ThirdEngine\PropelSOABundleBundle\Base;

use ThirdEngine\PropelSOABundleBundle\Model\PropelSOAModel;


class DataRelation
{
  /**
   * This is the name of the relation.
   */
  public $relation = '';
  public $fullRelationPath = '';

  /**
   * This is the list of joins that belong to this join.
   */
  public $joinList = [];

  public $relationType = null;
  public $namespace = '';
  public $bundle = '';
  public $entity = '';


  /**
   * This method will fix the namespaces for this data relation and any children relations.
   *
   * @param $query
   */
  public function correctNamespace($query)
  {
  }

  /**
   * This method will add the data pulled in by our join to an array representation of a model.
   *
   * @codeCoverageIgnore
   *
   * @param PropelSOAModel $model
   * @param array $data
   */
  public function addJoinedDataToResultArray(PropelSOAModel $model, array &$data)
  {

  }
}