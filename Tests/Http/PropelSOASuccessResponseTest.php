<?php
namespace ThirdEngine\PropelSOABundle\Tests\Http;

use ThirdEngine\PropelSOABundle\Http\PropelSOASuccessResponse;
use ThirdEngine\PropelSOABundle\Tests\TestUtility;

use Exception;
use Symfony\Bundle\FrameworkBundle\Tests;


class PropelSOASuccessResponseTest extends Tests\TestCase
{
  public function testGetTypeReturnsError()
  {
    $errorResponse = new PropelSOASuccessResponse(null, 200);
    $this->assertEquals('success', $errorResponse->getType());
  }

  public function testNewSuccessResponseThrowsExceptionIfGivenErrorfulStatusCode()
  {
    $this->setExpectedException(Exception::class, 'status code');
    new PropelSOASuccessResponse(null, 400);
  }

  public function testGetFinalDataIncludesData()
  {
    $message = 'My Message';
    $code = 'MY_CODE';
    $data = ['data'];

    $testUtility = new TestUtility();
    $successResponse = new PropelSOASuccessResponse(null, 200);

    $testUtility->setProtectedProperty($successResponse, 'message', $message);
    $testUtility->setProtectedProperty($successResponse, 'code', $code);

    $finalData = $testUtility->callProtectedMethod($successResponse, 'getFinalData', [$data]);

    $this->assertEquals($message, $finalData['Message']);
    $this->assertEquals($code, $finalData['Code']);
    $this->assertEquals($data, $finalData['Data']);
    $this->assertEquals('success', $finalData['Type']);
  }
}