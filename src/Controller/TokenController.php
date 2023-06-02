<?php

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TokenController extends AbstractController
{
    private $jwtManager;

    public function __construct(JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
    }


    #[Route('api/refresh-token', name: 'refresh_token', methods: ['POST'])]
    public function refreshTokenAction(Request $request)
    {
        $token = $request->headers->get('Authorization');

        if (!$token) {
            return new JsonResponse(['error' => 'Token missing'], 401);
        }

        // Supprimez le préfixe "Bearer " du token
        $token = substr($token, 7);

        try {
            $refreshedToken = $this->jwtManager->refresh($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Unable to refresh token'], 401);
        }

        return new JsonResponse(['token' => $refreshedToken]);
    }
}