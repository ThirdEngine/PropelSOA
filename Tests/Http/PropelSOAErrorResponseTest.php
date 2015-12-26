<?php
namespace ThirdEngine\PropelSOABundle\Tests\Http;

use ThirdEngine\PropelSOABundle\Http\PropelSOAErrorResponse;
use ThirdEngine\PropelSOABundle\Tests\TestUtility;
use ThirdEngine\PropelSOABundle\Http\PropelSOAResponse;

use Exception;
use Symfony\Bundle\FrameworkBundle\Tests;


class PropelSOAErrorResponseTest extends Tests\TestCase
{
  public function testGetTypeReturnsError()
  {
    $errorResponse = new PropelSOAErrorResponse(null, 404);
    $this->assertEquals('error', $errorResponse->getType());
  }

  public function testNewErrorResponseThrowsExceptionIfGivenSuccessfulStatusCode()
  {
    $this->setExpectedException(Exception::class, 'status code');
    new PropelSOAErrorResponse(null, 200);
  }

  public function testAddFieldErrorAddsRulesAndMessages()
  {
    $errorResponse = new PropelSOAErrorResponse(null, 401);

    $errorResponse->addFieldError('TestField1', 'Rule1', 'Message1');
    $errorResponse->addFieldError('TestField1', 'Rule2', 'Message2');
    $errorResponse->addFieldError('TestField2', 'Rule3', 'Message3');

    $fieldErrors = $errorResponse->fieldErrors;
    $this->assertEquals('Rule1', $fieldErrors['TestField1'][0]['rule']);
    $this->assertEquals('Message1', $fieldErrors['TestField1'][0]['message']);
    $this->assertEquals('Rule2', $fieldErrors['TestField1'][1]['rule']);
    $this->assertEquals('Message2', $fieldErrors['TestField1'][1]['message']);
    $this->assertEquals('Rule3', $fieldErrors['TestField2'][0]['rule']);
    $this->assertEquals('Message3', $fieldErrors['TestField2'][0]['message']);
  }

  public function testGetFinalDataIncludesFieldErrors()
  {
    $message = 'My Message';
    $code = 'MY_CODE';
    $data = ['data'];

    $testUtility = new TestUtility();
    $errorResponse = new PropelSOAErrorResponse(null, 404);

    $testUtility->setProtectedProperty($errorResponse, 'message', $message);
    $testUtility->setProtectedProperty($errorResponse, 'code', $code);
    $errorResponse->addFieldError('TestField1', 'Rule1', 'Message1');

    $finalData = $testUtility->callProtectedMethod($errorResponse, 'getFinalData', [$data]);

    $this->assertEquals($message, $finalData['Message']);
    $this->assertEquals($code, $finalData['Code']);
    $this->assertEquals($data, $finalData['Data']);
    $this->assertEquals('error', $finalData['Type']);
    $this->assertEquals('Rule1', $finalData['FieldErrors']['TestField1'][0]['rule']);
  }

  public function testConstructorCanSetMessage()
  {
    $message = 'My Message';

    $response = new PropelSOAErrorResponse($message, 400);
    $this->assertEquals($message, $response->message);
  }
}