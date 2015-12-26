<?php

namespace SOA\SOABundle\Controller;

use \Engine\EngineBundle\Base\EngineCore;
use \SOA\SOABundle\Model;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \BasePeer;



class ServiceController extends Controller
{
  use EngineCore;

  /**
   * This action is the base DELETE action.
   */
  public function deleteAction(Request $request)
  {
    throw new Exception('The DELETE action for this resource has not been implemented.');
  }

  /**
   * This action is the base GET action.
   */
  public function getAction(Request $request)
  {
    throw new Exception('The GET action for this resource has not been implemented.');
  }

  /**
   * This action is the base GET action.
   */
  public function getByIdAction(Request $request, $id)
  {
    throw new Exception('The GET action to get a record by primary key for this resource has not been implemented.');
  }

  /**
   * This action will display information about our resource including the available HTTP
   * verbs for this resource and other discoverable information.
   */
  public function optionsAction(Request $request)
  {
    throw new Exception('The OPTIONS action for this resource has not been implemented.');
  }

  /**
   * This action will save data about a record to the database. We use POST for both
   * create and update operations.
   */
  public function postAction(Request $request)
  {
    throw new Exception('The POST action for this resource has not been implemented.');
  }

  /**
   * This method will define what fields can be a part of objects built on this resource.
   *
   * @return array
   */
  public function getFieldNames()
  {
    // the default is to have no readable fields
    return array();
  }

  /**
   * This method will take a Request object and pull the JSON submission out of it and return
   * the JSON text as a string.
   *
   * @param Request $request
   * @return string
   */
  public function getJSONFromRequest(Request $request)
  {
    $allQueryParameters = $request->query->all();
    return reset($allQueryParameters);
  }

  /**
   * This method will take a Request object and return the stdClass object that represents
   * the JSON data that was encoded.
   *
   * @param Request $request
   * @return stdClass
   */
  public function getDataFromRequest(Request $request)
  {
    $json = $this->getJSONFromRequest($request);
    return $json ? json_decode($json) : new \stdClass();
  }

  /**
   * This method will return a Symfony response with specified JSON
   *
   * @param mixed $data
   * @param int   $httpStatus
   */
  protected function respondJson($data, $httpStatus = 200)
  {
    $response = new Response(json_encode($data), $httpStatus);
    return $response;
  }
}
