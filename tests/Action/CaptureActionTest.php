<?php
namespace Combodo\StripeV3\Tests\Action\Api;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\Capture;
use Payum\Core\Tests\GenericActionTest;
use Combodo\StripeV3\Action\CaptureAction;
use Combodo\StripeV3\Constants;
use Combodo\StripeV3\Request\Api\CreateCharge;
use Combodo\StripeV3\Request\Api\ObtainToken;

class CaptureActionTest extends GenericActionTest
{
    protected $requestClass = Capture::class;

    protected $actionClass = CaptureAction::class;

    /**
     * @test
     */
    public function shouldImplementGatewayAwareInterface()
    {
        $rc = new \ReflectionClass(CaptureAction::class);

        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

    /**
     * @test
     */
    public function shouldDoNothingIfPaymentHasStatus()
    {
        $model = [
            'status' => Constants::STATUS_SUCCEEDED,
        ];

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
        ;

        $action = new CaptureAction();
        $action->setGateway($gatewayMock);

        $action->execute(new Capture($model));
    }

    /**
     * @test
     */
    public function shouldSubExecuteObtainTokenRequestIfTokenNotSet()
    {
        $model = array();

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf(ObtainToken::class))
        ;

        $action = new CaptureAction();
        $action->setGateway($gatewayMock);

        $action->execute(new Capture($model));
    }

    /**
     * @test
     */
    public function shouldSubExecuteObtainTokenRequestWithCurrentModel()
    {
        $model = new \ArrayObject(['foo' => 'fooVal']);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->will($this->returnCallback(function (ObtainToken $request) use ($model) {
                $this->assertInstanceOf(ArrayObject::class, $request->getModel());
                $this->assertSame(['foo' => 'fooVal'], (array) $request->getModel());
            }))
        ;

        $action = new CaptureAction();
        $action->setGateway($gatewayMock);

        $action->execute(new Capture($model));
    }

    /**
     * @test
     */
    public function shouldNotSubExecuteCreateChargeIfAlreadyCharged()
    {
        $model = [
            'card' => 'theToken',
            'status' => Constants::STATUS_PAID,
        ];

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
        ;

        $action = new CaptureAction();
        $action->setGateway($gatewayMock);

        $action->execute(new Capture($model));
    }
    
    /**
     * @test
     */
    public function shouldNotSubExecuteCreateChargeIfCustomerSetButAlreadyCharged()
    {
        $model = [
            'customer' => 'theCustomerId',
            'status' => Constants::STATUS_SUCCEEDED,
        ];

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
        ;

        $action = new CaptureAction();
        $action->setGateway($gatewayMock);

        $action->execute(new Capture($model));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->createMock(GatewayInterface::class);
    }
}
