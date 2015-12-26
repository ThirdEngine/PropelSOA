<?php

namespace SOA\SOABundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use \BasePeer;
use \SOA\SOABundle\Utility\ReflectionUtility;
use \SOA\SOABundle\Utility\DocBlockUtility;


class ObjectGeneratorController extends Controller
{
  /**
   * This is class info that will help us be able to instantiate the various classes that we need.
   *
   * @var SymfonyClassInfo
   */
  protected $controllerClassInfo;


  /**
   * This action will generate an object that represents one record.
   *
   * @param $namespace
   * @param $bundle
   * @param $entity
   */
  public function getObjectAction($namespace, $bundle, $entity)
  {
    return $this->getObjectCode($namespace, $bundle, $entity, false);
  }

  /**
   * This action will generate a partial object that represents one record.
   *
   * @param $namespace
   * @param $bundle
   * @param $entity
   */
  public function getPartialObjectAction($namespace, $bundle, $entity)
  {
    return $this->getObjectCode($namespace, $bundle, $entity, true);
  }

  /**
   * This action generate a query object that can retrieve record objects.
   *
   * @param $namespace
   * @param $bundle
   * @param $entity
   */
  public function getQueryAction($namespace, $bundle, $entity)
  {
    $this->setupClassInfo($namespace, $bundle, $entity);

    $viewData = array(
      'generatedClassName' => $namespace . $bundle . 'Bundle' . $entity . 'Query',

      'namespace' => $namespace,
      'bundle'    => $bundle,
      'entity'    => $entity,
      'route'     => $this->getResourceRoute(),
    );

    return $this->render('SOASOABundle:ObjectGenerator:getQuery.js.twig', $viewData);
  }

  /**
   * This action will generate a collection object that stores record objects.
   *
   * @param $namespace
   * @param $bundle
   * @param $entity
   */
  public function getCollectionAction($namespace, $bundle, $entity)
  {
    $this->setupClassInfo($namespace, $bundle, $entity);

    $viewData = array(
      'generatedClassName' => $namespace . $bundle . 'Bundle' . $entity . 'Collection',

      'namespace' => $namespace,
      'bundle'    => $bundle,
      'entity'    => $entity,
      'route'     => $this->getResourceRoute(),
    );

    return $this->render('SOASOABundle:ObjectGenerator:getCollection.js.twig', $viewData);
  }

  /**
   * This method will render a PropelSOA object implementation.
   *
   * @param  $namespace
   * @param  $bundle
   * @param  $entity
   * @param  $partial
   * @return string
   */
  protected function getObjectCode($namespace, $bundle, $entity, $partial)
  {
    $objectSuffix = $partial ? 'PartialObject' : 'Object';

    $this->setupClassInfo($namespace, $bundle, $entity);
    $resourceControllerClass = $this->controllerClassInfo->getClassPath('controller');
    $resourceController = new $resourceControllerClass();

    $viewData = array(
      'generatedClassName' => $namespace . $bundle . 'Bundle' . $entity . $objectSuffix,

      'namespace'    => $namespace,
      'bundle'       => $bundle,
      'entity'       => $entity,
      'route'        => $this->getResourceRoute(),
      'objectSuffix' => $objectSuffix,

      'fieldNames'           => $this->getAvailableFields($namespace, $bundle, $entity),
      'saveFieldNames'       => $this->getAvailableSaveFields($namespace, $bundle, $entity),
      'linkedDataFieldNames' => $resourceController->getLinkedDataFieldNames(),

      // This section contains information about the commit actions that send data back to the API
      'defineSaveMethod' => ReflectionUtility::classDefinesMethod($resourceControllerClass, 'postAction'),

      // This is the list of custom actions that need to be defined
      'customCommitActions' => $this->getCustomCommitActions(),

      // This is a mapping of custom commit action resource routes
      'customCommitActionRouteMap' => $this->getCustomCommitActionRouteMap(),

      // This will return the data properties each custom action should allow
      'customCommitActionPropertyMap' => $this->getCustomCommitActionPropertyMap($namespace, $bundle, $entity),
    );

    return $this->render('SOASOABundle:ObjectGenerator:getObject.js.twig', $viewData);
  }

  /**
   * This method will return the field names for the associated controller.
   *
   * @param string $namespace
   * @param string $bundle
   * @param string $entity
   *
   * @return array
   */
  protected function getAvailableFields($namespace, $bundle, $entity)
  {
    $this->setupClassInfo($namespace, $bundle, $entity);
    $resourceControllerClass = $this->controllerClassInfo->getClassPath('controller');
    $resourceController = new $resourceControllerClass();

    return $resourceController->getFieldNames();
  }

  /**
   * This method will return the field names that should be available to the default save action without
   * any special transformations dictated by postAction annotations.
   *
   * @param string $namespace
   * @param string $bundle
   * @param string $entity
   *
   * @return array
   */
  protected function getBasicSaveFields($namespace, $bundle, $entity)
  {
    $availableFields = $this->getAvailableFields($namespace, $bundle, $entity);
    $availableFields = $this->removeDatabaseLevelDates($availableFields);

    return $availableFields;
  }

  /**
   * This method will return the field names that should be available to the default save action.
   *
   * @param string $namespace
   * @param string $bundle
   * @param string $entity
   *
   * @return array
   */
  protected function getAvailableSaveFields($namespace, $bundle, $entity)
  {
    $availableFields = $this->getBasicSaveFields($namespace, $bundle, $entity);

    $resourceControllerClass = $this->controllerClassInfo->getClassPath('controller');
    if (ReflectionUtility::classDefinesMethod($resourceControllerClass, 'postAction'))
    {
      $resourceController = new $resourceControllerClass();
      $controllerReflectionClass = new \ReflectionClass($resourceController);

      $postActionMethod = $controllerReflectionClass->getMethod('postAction');
      $availableFields = $this->adjustFieldListByAnnotation($postActionMethod, $availableFields);
    }

    return $availableFields;
  }

  /**
   * This method will return the controller actions defined in the controller class. This assumes
   * that controllers DO NOT extend each other, which they should not.
   *
   * @return array
   */
  protected function getCustomCommitActions()
  {
    $customCommitActions = [];
    $expectedList = ['postAction', 'deleteAction', 'getAction', 'getByIdAction', 'validateAction', 'rulesAction'];

    $resourceControllerClass = $this->controllerClassInfo->getClassPath('controller');
    $resourceController = new $resourceControllerClass();

    $controllerReflectionClass = new \ReflectionClass($resourceController);
    foreach ($controllerReflectionClass->getMethods() as $reflectionMethod)
    {
      if (!$reflectionMethod->isPublic())
      {
        // methods that aren't public can't be controller actions
        continue;
      }

      if (!ReflectionUtility::classDefinesMethod($resourceControllerClass, $reflectionMethod->getName()))
      {
        // methods that aren't overridden in the specific controller cannot be service actions
        continue;
      }

      if (!preg_match('/Action$/', $reflectionMethod->getName()))
      {
        // all controller actions end with the string "Action"
        continue;
      }

      if (in_array($reflectionMethod->getName(), $expectedList))
      {
        // these are one of the standard actions that are handled specifically by the framework. we
        // do not want to redefine these as custom actions
        continue;
      }

      $actionInfo = new \stdClass();
      $actionInfo->reflectionMethod = $reflectionMethod;
      $actionInfo->actionMethodName = preg_replace('/Action$/', '', $reflectionMethod->getName());
      $actionInfo->buildMethodName = 'build' . ucfirst($actionInfo->actionMethodName) . 'Data';

      $customCommitActions[] = $actionInfo;
    }

    return $customCommitActions;
  }

  /**
   * This method will return a map of custom commit action method names to the resource route the related
   * requests should be sent to.
   *
   * @return array
   */
  protected function getCustomCommitActionRouteMap()
  {
    $docBlockUtility = new DocBlockUtility();
    $routeMap = [];

    foreach (self::getCustomCommitActions() as $actionInfo)
    {
      $docComment = $actionInfo->reflectionMethod->getDocComment();
      $routeValue = $docBlockUtility->getAnnotationValue($docComment, '@route');

      $routeMap[$actionInfo->actionMethodName] = $routeValue;
    }

    return $routeMap;
  }

  /**
   * This method will adjust a field list based on annotation instructions like "willAccept" or
   * "wontAccept".
   *
   * @param ReflectionMethod $reflectionMethod
   * @param array $fields
   * @return array
   */
  protected function adjustFieldListByAnnotation($reflectionMethod, $fields)
  {
    $docBlockUtility = new DocBlockUtility();
    $docComment = $reflectionMethod->getDocComment();

    $fieldsToAdd = $docBlockUtility->getAllAnnotationValues($docComment, '@willAccept');
    $fields = array_merge($fields, $fieldsToAdd);

    $fieldsToRemove = $docBlockUtility->getAllAnnotationValues($docComment, '@wontAccept');
    $fields = array_diff($fields, $fieldsToRemove);

    return $fields;
  }

  /**
   * This method will get the properties that each custom commit action will allow. This starts
   * with the standard list, removes any that are tagged as "wontAccept" and then adds any that
   * are tagged as "willAccept".
   *
   * @param string $namespace
   * @param string $bundle
   * @param string $entity
   *
   * @return array
   */
  protected function getCustomCommitActionPropertyMap($namespace, $bundle, $entity)
  {
    $propertyMap = [];

    foreach ($this->getCustomCommitActions() as $actionInfo)
    {
      $fields = $this->getBasicSaveFields($namespace, $bundle, $entity);
      $fields = $this->adjustFieldListByAnnotation($actionInfo->reflectionMethod, $fields);

      $propertyMap[$actionInfo->actionMethodName] = $fields;
    }

    return $propertyMap;
  }

  /**
   * This method will remove the database-level dates from the field names array.
   *
   * @param array $fieldNames
   * @return array
   */
  protected function removeDatabaseLevelDates(array $fieldNames)
  {
    // copy the array
    $fieldNamesWithoutDates = $fieldNames;

    foreach ($fieldNamesWithoutDates as $key => $value)
    {
      if (in_array($value, ['DateCreated', 'DateModified']))
      {
        unset($fieldNamesWithoutDates[$key]);
      }
    }

    return $fieldNamesWithoutDates;
  }

  /**
   * This method will setup our class info based on supplied parameters.
   *
   * @param $namespace
   * @param $bundle
   * @param $entity
   */
  public function setupClassInfo($namespace, $bundle, $entity)
  {
    $this->controllerClassInfo = new \SOA\SOABundle\Base\SymfonyClassInfo();

    $this->controllerClassInfo->namespace = $namespace;
    $this->controllerClassInfo->bundle    = $bundle;
    $this->controllerClassInfo->entity    = $entity;
  }


  /**
   * This method will determine and return the resource route for the resource controller.
   *
   * @return string
   */
  public function getResourceRoute()
  {
    $docBlockUtility = new DocBlockUtility();

    $controllerClass = $this->controllerClassInfo->getClassPath('controller');
    $reflectionObject = new \ReflectionClass($controllerClass);

    $docBlock = $reflectionObject->getDocComment();
    $routeValue = $docBlockUtility->getAnnotationValue($docBlock, '@route');

    if ($routeValue === null)
    {
      throw new \Exception('Every resource controller must define a @route tag.');
    }

    return $routeValue;
  }
}
