<?php

namespace App\Controller;

use App\Service\GreetingService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TestController
{
    public function __construct(private GreetingService $greeter)
    {
    }

    public function index(): Response
    {
        return new Response($this->greeter->greet('world'));
    }

    public function hello(string $name): JsonResponse
    {
        return new JsonResponse([
            'message' => $this->greeter->greet($name),
            'route' => 'hello',
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        return new JsonResponse([
            'value' => $request->request->getString('value'),
            'method' => $request->getMethod(),
        ]);
    }
}
