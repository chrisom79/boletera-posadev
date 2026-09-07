<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Orders\Public;

use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\Request\Order\CreateOrderRequest;
use HiEvents\Http\ResponseCodes;
use HiEvents\Resources\Order\OrderResourcePublic;
use HiEvents\Services\Application\Handlers\Order\CreateOrderHandler;
use HiEvents\Services\Application\Handlers\Order\DTO\CreateOrderPublicDTO;
use HiEvents\Services\Application\Handlers\Order\DTO\ProductOrderDetailsDTO;
use HiEvents\Services\Domain\Order\OrderCreateRequestValidationService;
use HiEvents\Services\Infrastructure\Session\CheckoutSessionManagementService;
use Illuminate\Http\JsonResponse;
use Throwable;

class CreateOrderActionPublic extends BaseAction
{
    public function __construct(
        private readonly CreateOrderHandler                  $orderHandler,
        private readonly OrderCreateRequestValidationService $orderCreateRequestValidationService,
        private readonly CheckoutSessionManagementService    $sessionIdentifierService,
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(CreateOrderRequest $request, int $eventId): JsonResponse
    {
        $this->orderCreateRequestValidationService->validateRequestData($eventId, $request->all());
        $sessionId = $this->sessionIdentifierService->getSessionId();

        $order = $this->orderHandler->handle(
            eventId: $eventId,
            createOrderPublicDTO: CreateOrderPublicDTO::fromArray([
                'is_user_authenticated' => $this->isUserAuthenticated(),
                'promo_code' => $request->input('promo_code'),
                'affiliate_code' => $request->input('affiliate_code'),
                'products' => ProductOrderDetailsDTO::collectionFromArray($request->input('products')),
                'session_identifier' => $sessionId,
                // Always use the app's configured locale rather than detecting it from the
                // buyer's browser (Accept-Language): for a single-language event/community,
                // a buyer with an English-language browser should still get emails in the
                // event's language rather than a mismatched auto-detected one.
                'order_locale' => config('app.locale'),
            ])
        );

        $order->setSessionIdentifier($sessionId);

        $response =  $this->resourceResponse(
            resource: OrderResourcePublic::class,
            data: $order,
            statusCode: ResponseCodes::HTTP_CREATED,
        );

        return $response->withCookie(
            cookie: $this->sessionIdentifierService->getSessionCookie(),
        );
    }
}
