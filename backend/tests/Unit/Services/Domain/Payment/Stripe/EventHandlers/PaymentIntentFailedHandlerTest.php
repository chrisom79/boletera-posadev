<?php

namespace Tests\Unit\Services\Domain\Payment\Stripe\EventHandlers;

use HiEvents\DomainObjects\Generated\OrderDomainObjectAbstract;
use HiEvents\DomainObjects\Generated\StripePaymentDomainObjectAbstract;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\OrderItemDomainObject;
use HiEvents\DomainObjects\Status\OrderPaymentStatus;
use HiEvents\DomainObjects\StripePaymentDomainObject;
use HiEvents\Repository\Eloquent\StripePaymentsRepository;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use HiEvents\Services\Domain\Payment\Stripe\EventHandlers\PaymentIntentFailedHandler;
use HiEvents\Services\Domain\Payment\Stripe\StripePaymentUpdateFromPaymentIntentService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Event;
use Mockery as m;
use Psr\Log\LoggerInterface;
use Stripe\PaymentIntent;
use Tests\TestCase;

class PaymentIntentFailedHandlerTest extends TestCase
{
    private PaymentIntentFailedHandler $handler;
    private OrderRepositoryInterface $orderRepository;
    private StripePaymentsRepository $stripePaymentsRepository;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = m::mock(OrderRepositoryInterface::class);
        $this->stripePaymentsRepository = m::mock(StripePaymentsRepository::class);
        $this->logger = m::mock(LoggerInterface::class);

        $databaseManager = m::mock(DatabaseManager::class);
        $databaseManager->shouldReceive('transaction')
            ->andReturnUsing(fn($callback) => $callback());

        // StripePaymentUpdateFromPaymentIntentService is a readonly class, which Mockery cannot
        // subclass, so we use a real instance backed by the same mocked repository instead.
        $stripePaymentUpdateFromPaymentIntentService = new StripePaymentUpdateFromPaymentIntentService(
            $this->stripePaymentsRepository,
        );

        $this->handler = new PaymentIntentFailedHandler(
            $this->orderRepository,
            $this->stripePaymentsRepository,
            $databaseManager,
            $stripePaymentUpdateFromPaymentIntentService,
            $this->logger,
        );
    }

    public function testHandleEventIgnoresStaleFailureForAlreadyPaidOrder(): void
    {
        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_123',
            'status' => 'requires_payment_method',
        ]);

        $order = m::mock(OrderDomainObject::class);
        $order->shouldReceive('getPaymentStatus')->andReturn(OrderPaymentStatus::PAYMENT_RECEIVED->name);

        $stripePayment = m::mock(StripePaymentDomainObject::class);
        $stripePayment->shouldReceive('getOrder')->andReturn($order);
        $stripePayment->shouldReceive('getOrderId')->andReturn(1);

        $this->stripePaymentsRepository->shouldReceive('loadRelation')
            ->andReturnSelf();
        $this->stripePaymentsRepository->shouldReceive('findFirstWhere')
            ->with([StripePaymentDomainObjectAbstract::PAYMENT_INTENT_ID => 'pi_123'])
            ->andReturn($stripePayment);

        $this->logger->shouldReceive('info')->once();

        $this->stripePaymentsRepository->shouldNotReceive('updateWhere');
        $this->orderRepository->shouldNotReceive('loadRelation');

        $this->handler->handleEvent($paymentIntent);

        $this->assertTrue(true);
    }

    public function testHandleEventMarksOrderAsFailedWhenNotAlreadyPaid(): void
    {
        Event::fake();

        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_123',
            'status' => 'requires_payment_method',
        ]);

        $order = m::mock(OrderDomainObject::class);
        $order->shouldReceive('getPaymentStatus')->andReturn(OrderPaymentStatus::AWAITING_PAYMENT->name);

        $stripePayment = m::mock(StripePaymentDomainObject::class);
        $stripePayment->shouldReceive('getOrder')->andReturn($order);
        $stripePayment->shouldReceive('getOrderId')->andReturn(1);

        $this->stripePaymentsRepository->shouldReceive('loadRelation')
            ->andReturnSelf();
        $this->stripePaymentsRepository->shouldReceive('findFirstWhere')
            ->with([StripePaymentDomainObjectAbstract::PAYMENT_INTENT_ID => 'pi_123'])
            ->andReturn($stripePayment);

        $this->stripePaymentsRepository->shouldReceive('updateWhere')
            ->once()
            ->with(
                m::on(fn($attrs) =>
                    $attrs[StripePaymentDomainObjectAbstract::AMOUNT_RECEIVED] === $paymentIntent->amount_received
                    && $attrs[StripePaymentDomainObjectAbstract::PAYMENT_METHOD_ID] === null
                    && $attrs[StripePaymentDomainObjectAbstract::CHARGE_ID] === null
                ),
                [
                    StripePaymentDomainObjectAbstract::PAYMENT_INTENT_ID => 'pi_123',
                    StripePaymentDomainObjectAbstract::ORDER_ID => 1,
                ],
            );

        $updatedOrder = m::mock(OrderDomainObject::class);

        $this->orderRepository->shouldReceive('loadRelation')
            ->with(OrderItemDomainObject::class)
            ->andReturnSelf();
        $this->orderRepository->shouldReceive('updateFromArray')
            ->once()
            ->with(1, [
                OrderDomainObjectAbstract::PAYMENT_STATUS => OrderPaymentStatus::PAYMENT_FAILED->name,
            ])
            ->andReturn($updatedOrder);

        $this->logger->shouldNotReceive('info');

        $this->handler->handleEvent($paymentIntent);

        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }
}
