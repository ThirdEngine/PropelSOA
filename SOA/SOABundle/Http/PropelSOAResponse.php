<?php
/**
 * This class serves as a base class for the standard PropelSOA responses.
 */

namespace SOA\SOABundle\Http;

use Symfony\Component\HttpFoundation\Response;


abstract class PropelSOAResponse extends Response
{
  /**
   * This holds a message that will get sent through and generally displayed to a user.
   *
   * @var string
   */
  protected $message;

  /**
   * This holds a code that can identify where the response came from in such a way that
   * it is more friendly to be read by an automatic system.
   *
   * @var string
   */
  protected $code;

  /**
   * This holds the data that the API needs to send back through the response. Note that this
   * is just one piece of the final response payload.
   *
   * @var mixed
   */
  protected $data;


  /**
   * This method allows setting of any of our properties.
   *
   * @param $name
   * @param $value
   */
  public function __set($name, $value)
  {
    $this->$name = $value;

    // refresh the actual content by re-passing in the data
    $this->setContent($this->data);
  }

  /**
   * This method allows anyone to read our properties if they want to.
   *
   * @param $name
   * @return mixed
   */
  public function __get($name)
  {
    return $this->$name;
  }

  /**
   * This method will get the final data to add to the response content.
   *
   * @param $data
   * @return array
   */
  protected function getFinalData($data)
  {
    return [
      'Type' => $this->getType(),
      'Message' => $this->message,
      'Code' => $this->code,

      'Data' => $data,
    ];
  }

  /**
   * This method will override the set content for any of our response types. Our actual content
   * goes in specific places.
   *
   * @codeCoverageIgnore
   * @param data
   */
  public function setContent($data)
  {
    parent::setContent(json_encode($this->getFinalData($data)));
  }

  /**
   * This method will return the basic type of response we have in a string that is easily readable. This should
   * either be "success", "warning", "info", or "error".
   *
   * @return string
   */
  abstract public function getType();
}