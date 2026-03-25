<?php

declare(strict_types=1);

namespace App\Listener;


use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
class ExceptionListener
{
    public function __construct(
        private readonly string $environment,
    )
    {
    }

    /**
     * @param ExceptionEvent $event
     * @return void
     */
    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $statusCode = $this->resolveStatusCode($exception);

        $response = [
            'error' => $exception->getMessage(),
            'status' => $statusCode,
        ];

        if ($this->environment === 'dev') {
            $response['trace'] = $exception->getTrace();
        }

        $event->setResponse(new JsonResponse($response, $statusCode));
    }

    /**
     * @param \Throwable $exception
     * @return int
     */
    private function resolveStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}
