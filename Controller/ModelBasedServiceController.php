<?php

namespace ThirdEngine\PropelSOABundleBundle\Controller;

use ThirdEngine\PropelSOABundleBundle\Model;
use ThirdEngine\PropelSOABundleBundle\Http\PropelSOASuccessResponse;
use ThirdEngine\PropelSOABundleBundle\Interfaces\Collectionable;
use ThirdEngine\PropelSOABundleBundle\Base\JoinTree;

use stdClass;
use DateTime;
use BasePeer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;


abstract class ModelBasedServiceController extends ServiceController implements Collectionable
{
  /**
   * This stores the class info for the attached model classes.
   *
   * @var SymfonyClassInfo
   */
  protected $classInfo;

  /**
   * This property  is used for storing validation errors
   */
  protected $validationErrors;

  /**
   * This action will get data from the database out our resource.
   *
   * @param Request $request
   */
  public function getAction(Request $request)
  {
    $this->getJSONFromRequest($request);
    $this->setupClassInfo();

    $modelClass = $this->classInfo->getClassPath('model');
    $queryClass = $this->classInfo->getClassPath('query');
    $peerClass = $this->classInfo->getClassPath('peer');

    $query = $queryClass::create();


    // modify the query based on the posted data

    $queryDefinition = $this->getDataFromRequest($request);
    $this->joinTree = new JoinTree();

    if ($queryDefinition)
    {
      $this->addJoins($query, $queryDefinition);
      $this->addFilters($query, $queryDefinition);
    }

    $results = $query->find();

    // now, if we have linked data, we need to add that
    if ($queryDefinition && isset($queryDefinition->linkedData))
    {
      $results = $this->addLinkedData($results, $queryDefinition);
    }
    if (method_exists($this, 'afterGet'))
    {
      $results = $this->afterGet($results);
    }

    return $this->outputResultsAsJson($results);
  }

  /**
   * This method will populate our $classInfo object with information
   * about our related model.
   */
  abstract public function setupClassInfo();

  /**
   * This method will add any joins to the query.
   *
   * @param $query
   * @param $queryDefinition
   */
  protected function addJoins($query, $queryDefinition)
  {
    if (!$queryDefinition || !isset($queryDefinition->joins))
    {
      return;
    }

    $this->joinTree->buildFromPostedObjects($queryDefinition);
    $this->joinTree->correctNamespaces($query);
    $this->joinTree->addToQuery($query);
  }

  /**
   * This method will add any filters that were specified in the $_POST.
   *
   * @param $query
   * @param $queryDefinition
   */
  protected function addFilters($query, $queryDefinition)
  {
    if (!$queryDefinition || !isset($queryDefinition->filters))
    {
      return;
    }

    foreach ($queryDefinition->filters as $filterDefinition)
    {
      $methodName = 'add' . ucfirst($filterDefinition->filterType) . 'Filter';
      $this->$methodName($query, $filterDefinition);
    }
  }

  /**
   * This method will add linked data to results of a query.
   *
   * @param $results
   * @param $queryDefinition
   *
   * @return array
   */
  public function addLinkedData($results, $queryDefinition)
  {
    $this->setupClassInfo();
    $peerClass = $this->classInfo->getClassPath('peer');

//    $peer = new $peerClass();
//    $peer->prepopulateForLinkedData($results, $queryDefinition->linkedData);
//    $peer->populateLinkedData($results, $queryDefinition->linkedData);

    return $results;
  }

  /**
   * This method will print a result set as json.
   *
   * @param $results
   */
  protected function outputResultsAsJson($results, $status = 200)
  {
    $peerClass = $this->classInfo->getClassPath('peer');

    $finalResults = new stdClass();
    $finalResults->results = json_decode($this->joinTree->outputAsJSON($results));
    $finalResults->joinTree = $this->joinTree;

    return new PropelSOASuccessResponse($finalResults);
  }

  /**
   * This method will return the list of field names that should belong to an object
   * from this resource. For model-based resources, this will be the fields
   * available in the model.
   *
   * @return array
   */
  public function getFieldNames()
  {
    $this->setupClassInfo();
    $peerClass = $this->classInfo->getClassPath('peer');

    return $peerClass::getFieldNames();
  }

  /**
   * This method will return the list of linked data fields that can be joined in even
   * though they are not actual table relations.
   *
   * @return array
   */
  public function getLinkedDataFieldNames()
  {
    $this->setupClassInfo();
    $peerClass = $this->classInfo->getClassPath('peer');

    $peer = new $peerClass();
    return array_keys($peer->linkedData);
  }

  /**
   * This method will return the three class paths.
   *
   * @return array
   */
  protected function getClassPaths()
  {
    return [
      $this->classInfo->getClassPath('peer'),
      $this->classInfo->getClassPath('query'),
      $this->classInfo->getClassPath('model'),
    ];
  }

  /**
   * This action will save data to a record of our model.
   */
  public function postAction(Request $request)
  {
    $model = $this->getObjectFromRequest($request);

    // save the record and return the record's primary key
    if (method_exists($this, 'beforeSave'))
    {
      $model = $this->beforeSave($model);
    }

    $model->save();

    if (method_exists($this, 'afterSave'))
    {
      $model = $this->afterSave($model);
    }

    list($peerClass, $queryClass, $modelClass) = $this->getClassPaths();
    $query = new $queryClass();

    $pkFieldName = $this->getPrimaryKeyFieldName($query);
    $getPkMethod = 'get' . $pkFieldName;
    $returnData = ['pk' => $model->$getPkMethod()];

    $rawData = $request->getContent();
    $post = json_decode($rawData);

    return new PropelSOASuccessResponse($returnData, $this->isNewRecord($post, $pkFieldName)? 201 : 200);
  }

  /**
   * getObjectFromRequest - does all the setup necessary to allow the correct model to be used as associated
   * with the model based controller.
   *
   * @param Request $request
   *
   * @return mixed
   */
  protected function getObjectFromRequest(Request $request)
  {
    $this->setupClassInfo();
    list($peerClass, $queryClass, $modelClass) = $this->getClassPaths();

    $rawData = $request->getContent();
    $post = json_decode($rawData);


    // first, we need to be able to tell if a primary key was supplied. if so, we will update
    // a record, but if not, we will create a new record
    $query = new $queryClass();
    $pkFieldName = $this->getPrimaryKeyFieldName($query);

    // next, we will get a valid object that we will be able to use to update data for this record
    $model = $this->getWorkingModel($post, $query, $modelClass, $pkFieldName);
    $this->setModelPropertiesFromPost($post, $query, $model, $pkFieldName);

    return $model;
  }

  /**
   * This method will check the post and update the model values with corresponding post data.
   *
   * @param mixed $post
   * @param ModelCriteria $query
   * @param mixed $model
   * @param string $pkFieldName
   */
  protected function setModelPropertiesFromPost($post, $query, $model, $pkFieldName)
  {
    if (!is_object($post))
    {
      return;
    }

    foreach ($query->getTableMap()->getColumns() as $column)
    {
      $fieldName = $column->getPhpName();
      $setMethod = 'set' . $fieldName;

      if (!property_exists($post, $fieldName))
      {
        // this value was not passed in, so don't try to update it
        continue;
      }

      // we also need a special check for the primary key, since we do not want to
      // insert with setting a primary key value

      if ($pkFieldName == $fieldName && !$post->$fieldName)
      {
        continue;
      }

      $model->$setMethod($this->getModelPropertyValue($post, $fieldName, $column));
    }
  }

  /**
   * This method will return a value for a specified model property.
   *
   * @param mixed $post
   * @param string $fieldName
   * @param mixed $column
   *
   * @return mixed
   */
  protected function getModelPropertyValue($post, $fieldName, $column)
  {
    $value = $post->$fieldName;
    $dateTimeColumnTypes = array('TIMESTAMP', 'DATE');

    if (in_array($column->getType(), $dateTimeColumnTypes))
    {
      $value = strtoupper($value) == 'NOW' ? new DateTime() : new DateTime($value);
    }

    return $value;
  }

  /**
   * This method will return a new model object or the existing model based on db data. This will also
   * set the create date if there is a setter for it when we create a new record.
   *
   * @param mixed $post
   * @param ModelCriteria $query
   * @param string $modelClass
   * @param string $pkFieldName
   *
   * @return object
   */
  protected function getWorkingModel($post, $query, $modelClass, $pkFieldName)
  {
    $newRecord = $this->isNewRecord($post, $pkFieldName);
    $model = $newRecord ? new $modelClass() : $query->findPk($post->$pkFieldName);

    if ($newRecord && method_exists($model, 'setCreateDate'))
    {
      $model->setCreateDate(new DateTime());
    }

    return $model;
  }

  /**
   * This method will check to see if the posted data is meant to create a new record.
   *
   * @param mixed $post
   * @param string $pkFieldName
   *
   * @return bool
   */
  protected function isNewRecord($post, $pkFieldName)
  {
    return !isset($post->$pkFieldName) || !$post->$pkFieldName;
  }

  /**
   * This method will determine the primary key field name from a query object.
   *
   * @param ModelCriteria $query
   * @return string
   */
  protected function getPrimaryKeyFieldName($query)
  {
    $primaryKeyColumns = $query->getTableMap()->getPrimaryKeyColumns();
    $primaryKeyColumn = reset($primaryKeyColumns);

    return $primaryKeyColumn->getPhpName();
  }

  /**
   * This method will delete a record.
   */
  public function deleteAction(Request $request)
  {
    $this->setupClassInfo();

    $modelClass = $this->classInfo->getClassPath('model');
    $queryClass = $this->classInfo->getClassPath('query');
    $peerClass = $this->classInfo->getClassPath('peer');

    $pk = $request->get('recordId');

    $model = $queryClass::create()->findPk($pk);
    $model->delete();

    $returnData = array('pk' => $pk);
    return new PropelSOASuccessResponse($returnData);
  }

  /**
   * This method will add an equality filter to the query.
   *
   * @param $query
   * @param $filterDefinition
   */
  protected function addEqualFilter($query, $filterDefinition)
  {
    $filterMethod = 'filterBy' . $filterDefinition->field;
    $query->$filterMethod($filterDefinition->filterValue);
  }

  /**
   * This method will add an IN statement to the query.
   *
   * @param $query
   * @param $filterDefinition
   */
  protected function addInFilter($query, $filterDefinition)
  {
    $filterMethod = 'filterBy' . $filterDefinition->field;
    $query->$filterMethod($filterDefinition->filterValue);
  }
}
