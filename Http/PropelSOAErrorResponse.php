<?php
/**
 * This class serves represents an error response from a service controller.
 */
namespace ThirdEngine\PropelSOABundle\Http;

use Exception;
use Symfony\Component\HttpFoundation\Response;


class PropelSOAErrorResponse extends PropelSOAResponse
{
  /**
   * This array is a set of our field specific error data.
   *
   * @var array
   */
  protected $fieldErrors = [];


  /**
   * Constructor override since we always set the content to json and it is more convenient for us to be able to put a message in the constructor.
   * @param string $message
   * @param int $status
   * @param array $headers
   */
  public function __construct($message = null, $status = 200, $headers = [])
  {
    if ($message)
    {
      $this->message = $message;
    }

    parent::__construct(null, $status, $headers);
  }

  /**
   * This method will enforce that the status code will always be in the >= 400 range.
   *
   * @param $status
   */
  public function setStatusCode($status, $text = null)
  {
    if ($status < 400)
    {
      throw new Exception('Error operations should have a status code in the 4xx or 5xx range.');
    }

    parent::setStatusCode($status, $text);
  }

  /**
   * This method will return that we are an error type.
   *
   * @return string
   */
  public function getType()
  {
    return 'error';
  }

  /**
   * This method will add a field-specific error to our data set.
   *
   * @param string $fieldName
   * @param string $rule
   * @param string $message
   */
  public function addFieldError($fieldName, $rule, $message)
  {
    $fieldErrors = $this->fieldErrors;

    if (!isset($fieldErrors[$fieldName]))
    {
      $fieldErrors[$fieldName] = [];
    }

    $fieldErrors[$fieldName][] = ['rule' => $rule, 'message' => $message];
    $this->__set('fieldErrors', $fieldErrors);
  }

  /**
   * This method will override the set content for any of our response types. Our actual content
   * goes in specific places.
   *
   * @param data
   * @return array
   */
  protected function getFinalData($data)
  {
    return [
      'Type' => $this->getType(),
      'Message' => $this->message,
      'Code' => $this->code,

      'Data' => $data,
      'FieldErrors' => $this->fieldErrors,
    ];
  }
}