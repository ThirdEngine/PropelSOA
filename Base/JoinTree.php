<?php
/**
* This class defines a join tree as used by the SOA bundle.
*/
namespace ThirdEngine\PropelSOABundleBundle\Base;

use ThirdEngine\PropelSOABundleBundle\Model\PropelSOAModel;

use Traversable;
use BasePeer;
use PropelParser;
use StandardEnglishPluralizer;


class JoinTree
{
  /**
   * This property holds the list of join elements.
   */
  public $joinList = [];

  /**
   * This property holds the list of join and linked data elements.
   */
  public $dataRelationList = [];

  /**
   * This property holds the list of requested linked data.
   */
  public $linkedDataList = [];

  /**
   * This is a JSON parser that can be used by the whole object.
   */
  protected $parser;

  /**
   * This is a cache to keep classInfo objects as they are generated so they do not have to be
   * constantly regenerated when iterating over a join tree.
   */
  public static $classInfoCache = [];


  /**
   * This method will search one of our relation lists and see if a specified relation is already
   * in the list. This will return the index in the array if the element is found and -1 if it is not.
   *
   * @param array $relationList
   * @param string $relation
   * @return int
   */
  public static function searchRelationList($relationList, $relation)
  {
    foreach ($relationList as $key => $existingRelation)
    {
      if ($existingRelation->relation == $relation)
      {
        return $key;
      }
    }

    return -1;
  }

  /**
   * This method will build out our object from a query passed into the service controller.
   *
   * @param $queryDefinition
   */
  public function buildFromPostedObjects($queryDefinition)
  {
    /**
     * Each join in queryDefinition has the relation defined as relation->relation etc
     * for as many depth levels as it needs. The purpose of this section is to unpack
     * those relations into a tree.
     */
    foreach ($queryDefinition->joins as $join)
    {
      $this->addRelationPathToTree($this->joinList, $join->relation, Join::class);
      $this->addRelationPathToTree($this->dataRelationList, $join->relation, Join::class);
    }

    // now we need to add the linked data to the data relation list only, which
    // holds both joins and linked data. this is later used to determine which
    // nodes in the final model data need to be pared out.
    foreach ($queryDefinition->linkedData as $linkedDataRelation)
    {
      $this->addRelationPathToTree($this->dataRelationList, $linkedDataRelation, LinkedData::class);
    }
  }

  /**
   * This method will add a relation path to a relation tree. This is used
   * to build the join and data relation trees.
   *
   * @param $workingList
   * @param $relationPath
   * @param $nodeClass
   */
  protected function addRelationPathToTree(&$workingList, $relationPath, $nodeClass)
  {
    $fullRelationPath = '';
    $relationParts = explode('->', $relationPath);

    foreach ($relationParts as $part)
    {
      // the full relation path is the sequence of how to get to a join from the main query table
      $fullRelationPath .= ($fullRelationPath ? '.' : '') . $part;
      $currentKey = self::searchRelationList($workingList, $part);

      if ($currentKey == -1)
      {
        // this means that $workingList does not already have this relation, so we
        // need to go ahead and create a new join and add it

        $newJoin = new $nodeClass();
        $newJoin->relation = $part;
        $newJoin->fullRelationPath = $fullRelationPath;

        $workingList[] = $newJoin;
        $workingList =& $newJoin->joinList;
      }
      else
      {
        $existingJoin = $workingList[$currentKey];
        $workingList =& $existingJoin->joinList;
      }
    }
  }

  /**
   * This method will take the data we have gathered in the join tree and modify
   * the query object.
   *
   * @param $query
   */
  public function addToQuery($query)
  {
    foreach ($this->joinList as $join) {
      $join->addToQuery($query);
    }
  }

  /**
   * This method will go through both of our data list trees and correct the namespaces to be accurate.
   *
   * @param $query
   */
  public function correctNamespaces($query)
  {
    foreach ($this->joinList as $join)
    {
      $join->correctNamespace($query);
    }

    foreach ($this->dataRelationList as $dataRelation)
    {
      $dataRelation->correctNamespace($query);
    }
  }

  /**
   * This method will output a collection of records with all proper joins in a JSON format. This is
   * a replacement of the exportTo() method because the exportTo can't do recursive display and it
   * also automatically includes self-referential joins that are not desired.
   *
   * @param $collection
   * @return string
   */
  public function outputAsJSON($collection)
  {
    $dataArray = [];

    foreach ($collection as $model)
    {
      $dataArray[] = $this->convertModelToArray($model);
    }

    return $this->JSONFromArray($dataArray);
  }

  /**
   * This method will convert a model to an array with all of the relations that should be there from the join tree.
   *
   * @param PropelSOAModel $model
   * @return array
   */
  protected function convertModelToArray(PropelSOAModel $model)
  {
    $data = $model->toArray(BasePeer::TYPE_PHPNAME, false);

    foreach ($this->dataRelationList as $dataRelation)
    {
      $dataRelation->addJoinedDataToResultArray($model, $data);
    }

    return $data;
  }

  /**
   * This method will wrap the fromArray() method on the PropelParser.
   *
   * @codeCoverageIgnore
   *
   * @param array $dataArray
   * @return string
   */
  protected function JSONFromArray($dataArray)
  {
    return PropelParser::getParser('JSON')->fromArray($dataArray);
  }
}