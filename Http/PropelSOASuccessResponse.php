<?php
/**
 * This class serves represents a successful response from a service controller.
 */

namespace ThirdEngine\PropelSOABundle\Http;

use Symfony\Component\HttpFoundation\Response;


class PropelSOASuccessResponse extends PropelSOAResponse
{
  /**
   * This method will enforce that the status code will always be in the 200 range.
   *
   * @param $status
   */
  public function setStatusCode($status, $text = null)
  {
    if ($status < 200 || $status > 299)
    {
      throw new \Exception('Success operations should have a 2xx status code.');
    }

    parent::setStatusCode($status, $text);
  }

  /**
   * This method will return that we are a successful type.
   *
   * @return string
   */
  public function getType()
  {
    return 'success';
  }
}