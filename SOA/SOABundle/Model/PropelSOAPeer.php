<?php
/**
* This class defines a base class for model peer objects. This will allow setup of
* extra information that can be queried for models.
*/
namespace SOA\SOABundle\Model;

use PropelCollection;
use Traversable;
use StandardEnglishPluralizer;

class PropelSOAPeer
{
  /**
   * This is a definition of extra available data on this class in PropelSOA objects. These
   * are defined in code so they are not filterable or sortable on the server side.
   *
   * @var array
   */
  public $linkedData = [];

  /**
   * This is a definition of linked data traversals that should get some preparatory processing. The
   * case where this makes the most sense is if a linked data relation is going to require some information
   * from the database for every record, but it's cheaper to pull it for the whole list at once. This will
   * let you run one query, then pre-populate the child items, then the objects will not need to run
   * individual queries.
   *
   * @var array
   */
  public $strategiesForHydratingLinkedData = [];

  /**
   * This is a definition of relations we want to support as "one to one" relations on this model. There
   * will already be a "one to many" relation built by propel, but that relation is not really correct. This
   * will just assume there is one record and take the singular semantic. An example would be a relation between
   * a user and a person, a user can have one and only one person. Propel makes you link the primary keys (not always
   * desirable) to get the one-to-one relation to come directly from Propel.
   *
   * What you get by default is a relation of "Users" that will give you a collection, when what you really
   * want is a relation called "User" that returns one user or nothing. This array will hold a list of the
   * relations you want to support, assuming that the pluralized version is already a relation on the model.
   */
  public $oneToOneRelations = [];


  /**
   * This method will set any required linked data in a set of models and any of their children.
   *
   * @param $models
   * @param $linkedDataRelations
   */
  public function populateLinkedData($models, $linkedDataRelations)
  {
    foreach ($linkedDataRelations as $linkedDataRelation)
    {
      $relations = explode('->', $linkedDataRelation);
      $linkedDataKey = array_pop($relations);

      $targetModels = $this->getTargetModels($models, $relations);

      foreach ($targetModels as $targetModel)
      {
        if ($targetModel)
        {
          $targetModel->setLinkedData($linkedDataKey);
        }
      }
    }
  }

  /**
   * This method will attempt to expedite linked data operations by allowing bulk processing at higher levels. Things
   * like bulk-querying, etc can help speed up performance by grouping actions.
   *
   * @param $models
   * @param $linkedDataRelations
   */
  public function prepopulateForLinkedData($models, $linkedDataRelations)
  {
    foreach ($linkedDataRelations as $linkedDataRelation)
    {
      $relations = explode('->', $linkedDataRelation);

      /**
       * This operation is basically broken down into left side relations and right side relations. We are going
       * to walk each level of the tree so that we have a set of models, then we will figure out if that set of
       * models needs a hydration run. Once we've looked at that level in the tree, we will move one relation over
       * (move one relation from the right side to the left side) and do the examination again using the
       * next level of models and then one fewer relation.
       *
       * Consider Captain -> Commander -> Lieutenant -> Ensign
       *
       * First, we would examine the set of Captain models to see if they need to hydrate based on
       * Commander -> Lieutenant -> Ensign. Then, we would drill down one level and examine the Commander models for
       * Lieutenant -> Ensign. We can do all remaining levels because this is only useful for linked data, which has
       * no children, so we know it has to include all relations to the end, or it does not make sense.
       */
      for ($includedRelationCount = 0; $includedRelationCount < count($relations); ++$includedRelationCount)
      {
        list($leftSideRelations, $rightSideRelations) = $this->splitRelations($relations, $includedRelationCount);
        $targetModels = $this->getTargetModels($models, $leftSideRelations);

        if (count($targetModels) <= 0) {
          break;
        }

        $hydrationKey = implode('->', $rightSideRelations);
        $peer = $targetModels[0]->getPeer();

        if (isset($peer->strategiesForHydratingLinkedData[$hydrationKey]))
        {
          $hydrationMethod = $peer->strategiesForHydratingLinkedData[$hydrationKey];

          foreach ($targetModels as $model)
          {
            $model->$hydrationMethod();
          }
        }
      }
    }
  }

  /**
   * This method will break the set of relations into "left side" and "right side". We will give it the number
   * of elements to put on the "left side" as a parameter.
   *
   * @param array $relations
   * @param int $leftSideCount
   *
   * @return [leftSideRelations, rightSideRelations]
   */
  protected function splitRelations($relations, $leftSideCount)
  {
    $leftSideRelations = [];
    $rightSideRelations = $relations;

    for ($i = 0; $i < $leftSideCount; ++$i)
    {
      $leftSideRelations[] = array_shift($rightSideRelations);
    }

    return [$leftSideRelations, $rightSideRelations];
  }

  /**
   * This method will examine a model to try and find the child models that should
   * pull a certain piece of linked data.
   *
   * @param $modelsToExamine
   * @param $relations
   * @return array
   */
  protected function getTargetModels($modelsToExamine, $relations)
  {
    if (!$relations)
    {
      // we have no more relations to traverse, so we are at the last point
      return $modelsToExamine;
    }

    $nextLevelModels = [];
    $nextRelation = array_shift($relations);

    // we will perform the reflection once, instead of on each model, which would be wasteful
    $firstModel = reset($modelsToExamine);
    $nextLevelMethod = $this->getNextLevelMethodName($nextRelation, $firstModel);

    foreach ($modelsToExamine as $model)
    {
      $nextLevelModels = array_merge($nextLevelModels, $this->getNextLevelModels($model, $nextLevelMethod));
    }

    return $this->getTargetModels($nextLevelModels, $relations);
  }

  /**
   * This method will determine what method will give us the next relation level. This could vary depending
   * on whether this is a single record or collection coming back with singular vs plural naming.
   *
   * @param string $nextRelation
   * @param mixed $firstModel
   *
   * @return string
   */
  protected function getNextLevelMethodName($nextRelation, $firstModel)
  {
    $nextLevelMethod = 'get' . $nextRelation; // this is the singular item version

    $pluralizer = new StandardEnglishPluralizer();
    $pluralMethod = $pluralizer->getPluralForm($nextLevelMethod);

    if (method_exists($firstModel, $pluralMethod))
    {
      $nextLevelMethod = $pluralMethod;
    }

    return $nextLevelMethod;
  }

  /**
   * This method will get the next level of models from the getter method name and whether
   * there will be one or multiple results.
   *
   * @param $model
   * @param $methodName
   * @return array
   */
  protected function getNextLevelModels($model, $methodName)
  {
    $result = $model->$methodName();
    return $result instanceof Traversable ? iterator_to_array($result) : [$result];
  }
}