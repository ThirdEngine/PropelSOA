<?php
/**
 * This class defines a join as used by the SOA bundle.
 */
namespace ThirdEngine\PropelSOABundleBundle\Base;

use ThirdEngine\PropelSOABundleBundle\Model\PropelSOAModel;
use ThirdEngine\Factory\Factory;

use BasePeer;
use RelationMap;
use StandardEnglishPluralizer;


class Join extends DataRelation
{
  /**
   * @var RelationMap
   */
  protected $relationMap;

  /**
   * @var string
   */
  protected $joinedDataKey;

  /**
   * @var string
   */
  protected $pluralRelationName;


  /**
   * This method will default our relationType to be "join"
   */
  public function __construct()
  {
    $this->relationType = 'join';
  }

  /**
   * This method will get the last two relations at most to run through the join with method.
   *
   * @param string $fullRelationPath
   * @return string
   */
  protected function getJoinWithPath($fullRelationPath)
  {
    $parts = explode('.', $fullRelationPath);
    if (count($parts) <= 2)
    {
      // this is a close join, so there's no reason to pull out the beginning pieces, they are still necessary
      return $fullRelationPath;
    }

    $joinCount = count($parts);
    return $parts[$joinCount - 2] . '.' . $parts[$joinCount - 1];
  }

  /**
   * This method will fix the namespaces for this data relation and any children relations.
   *
   * @param $query
   */
  public function correctNamespace($query)
  {
    // we need to make sure that our path information is populated correctly, which means
    // we need to traverse through the table maps

    $fullRelationPathParts = explode('.', $this->fullRelationPath);
    $currentTableMap = $query->getTableMap();

    foreach ($fullRelationPathParts as $nextStepRelation)
    {
      $nextLevelRelationMap = $currentTableMap->getRelation($nextStepRelation);
      $currentTableMap = $nextLevelRelationMap->getRightTable();
    }


    // we now have the table map of the table this join is pulling in. we can use that model class name
    // to get the correct symfony path information with the SymfonyClassInfo class

    $classInfo = new SymfonyClassInfo();
    $classInfo->parseClassPath($currentTableMap->getClassName());

    $this->namespace = $classInfo->namespace;
    $this->bundle    = $classInfo->bundle;
    $this->entity    = $classInfo->entity;

    if ($this->joinList)
    {
      foreach ($this->joinList as $childRelation)
      {
        $childRelation->correctNamespace($query);
      }
    }
  }

  /**
   * This method will add our join to the query object.
   *
   * @param $query
   */
  public function addToQuery($query)
  {
    // add the main join for our relation
    $query->leftJoinWith($this->getJoinWithPath($this->fullRelationPath));

    // see if we have any child joins
    if ($this->joinList)
    {
      foreach ($this->joinList as $join)
      {
        $join->addToQuery($query);
      }
    }
  }

  /**
   * This method will add the data pulled in by our join to an array representation of a model.
   *
   * @param PropelSOAModel $model
   * @param array $data
   */
  public function addJoinedDataToResultArray(PropelSOAModel $model, array &$data)
  {
    if ($this->isNaturallySingularRelation($model))
    {
      $joinedData = $this->getJoinedDataForSingularRelationship($model);
    }
    elseif ($this->isForcedIntoOneToOne($model))
    {
      $joinedData = $this->getJoinedDataForForcedSingularRelationship($model);
    }
    else
    {
      $joinedData = $this->getJoinedDataForPluralRelationship($model);
    }

    $joinedDataKey = $this->getJoinedDataKey($model);
    $data[$joinedDataKey] = $joinedData;
  }

  /**
   * This method will get joined data for a plural relation. This will be an array
   * of sets of model data.
   *
   * @param PropelSOAModel $model
   * @return array
   */
  protected function getJoinedDataForPluralRelationship(PropelSOAModel $model)
  {
    $joinedData = [];

    $getMethod = 'get' . $this->getPluralRelationName();
    $relatedCollection = $model->$getMethod();

    foreach ($relatedCollection as $relatedModel)
    {
      $relatedModelData = $relatedModel->toArray(BasePeer::TYPE_PHPNAME, false);
      $this->applyChildJoins($relatedModel, $relatedModelData);

      $joinedData[] = $relatedModelData;
    }

    return $joinedData;
  }

  /**
   * This method will return the joined data for a relation that should be plural,
   * but due to a peer configuration we will treat it as a singular relation.
   *
   * @param PropelSOAModel $model
   * @return array
   */
  protected function getJoinedDataForForcedSingularRelationship(PropelSOAModel $model)
  {
    $getMethod = 'get' . $this->getPluralRelationName();
    $relatedCollection = $model->$getMethod();

    if (count($relatedCollection) == 0)
    {
      return null;
    }

    $relatedModel = $relatedCollection->getFirst();
    $joinedData = $relatedModel->toArray(BasePeer::TYPE_PHPNAME, false);
    $this->applyChildJoins($relatedModel, $joinedData);

    return $joinedData;
  }

  /**
   * This method will return the joined data for a naturally singular relationship.
   *
   * @param PropelSOAModel $model
   * @return array
   */
  protected function getJoinedDataForSingularRelationship(PropelSOAModel $model)
  {
    $getMethod = 'get' . $this->relation;
    $relatedModel = $model->$getMethod();

    if ($relatedModel == null)
    {
      return null;
    }

    $joinedData = $relatedModel->toArray(BasePeer::TYPE_PHPNAME, false);
    $this->applyChildJoins($relatedModel, $joinedData);

    return $joinedData;
  }

  /**
   * This method will apply our child joins to a recently retrieved set of
   * joined data. This makes the process walk the entire tree of relations.
   *
   * @param PropelSOAModel $relatedModel
   * @param array $joinedData
   */
  protected function applyChildJoins(PropelSOAModel $relatedModel, array &$joinedData)
  {
    foreach ($this->joinList as $childJoin)
    {
      $childJoin->addJoinedDataToResultArray($relatedModel, $joinedData);
    }
  }

  /**
   * This method will determine if our relation is a naturally singular relation. The
   * big note here is that this will return false for relations that should be
   * forced into a singular relationship due a definition in the peer class saying
   * we want to treat this as a one-to-one relation.
   *
   * @param PropelSOAModel $model
   * @return bool
   */
  protected function isNaturallySingularRelation(PropelSOAModel $model)
  {
    $singularKeyTypes = [RelationMap::MANY_TO_ONE, RelationMap::ONE_TO_ONE];
    $relationMap = $this->getRelationMap($model);

    return in_array($relationMap->getType(), $singularKeyTypes);
  }

  /**
   * This method will determine if our relation is naturally plural but should
   * be forced into a one-to-one relation anyway. This relationship will always
   * just take the first returned record and count that as "the one".
   *
   * @param PropelSOAModel $model
   * @return bool
   */
  protected function isForcedIntoOneToOne(PropelSOAModel $model)
  {
    $classInfo = Factory::createNewObject(SymfonyClassInfo::class);
    $classInfo->parseClassPath(get_class($model));

    $peerClass = $classInfo->getClassPath('peer');
    $peer = Factory::createNewObject($peerClass);

    return in_array($this->relation, $peer->oneToOneRelations);
  }

  /**
   * This method will determine what key the joined data should be
   * filed under when joined with the parent model's data.
   *
   * @param PropelSOAModel $model
   * @return string
   */
  protected function getJoinedDataKey(PropelSOAModel $model)
  {
    if (isset($this->joinedDataKey))
    {
      return $this->joinedDataKey;
    }

    if ($this->isNaturallySingularRelation($model) || $this->isForcedIntoOneToOne($model))
    {
      $this->joinedDataKey = $this->relation;
      return $this->joinedDataKey;
    }

    $this->joinedDataKey = $this->getPluralRelationName();
    return $this->joinedDataKey;
  }

  /**
   * This method will return our relation map if a model is supplied.
   *
   * @param PropelSOAModel $model
   * @return RelationMap
   */
  protected function getRelationMap(PropelSOAModel $model)
  {
    if (isset($this->relationMap))
    {
      return $this->relationMap;
    }

    $classInfo = Factory::createNewObject(SymfonyClassInfo::class);
    $classInfo->parseClassPath(get_class($model));

    $queryClass = $classInfo->getClassPath('query');
    $query = Factory::createNewObject($queryClass);

    $tableMap = $query->getTableMap();
    $this->relationMap = $tableMap->getRelation($this->relation);

    return $this->relationMap;
  }

  /**
   * This method will get our relation key pluralized.
   *
   * @return string
   */
  protected function getPluralRelationName()
  {
    if (isset($this->pluralRelationName))
    {
      return $this->pluralRelationName;
    }

    $pluralizer = Factory::createNewObject(StandardEnglishPluralizer::class);
    $this->pluralRelationName = $pluralizer->getPluralForm($this->relation);

    return $this->pluralRelationName;
  }
}