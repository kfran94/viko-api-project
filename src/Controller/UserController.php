<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;


class UserController extends AbstractController
{
    #[Route('/api/users', name: 'app_user_getalluserss')]
    public function getAllUsers(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $userList = $userRepository->findAll();

        $jsonUserList = $serializer->serialize($userList, 'json');

        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/user', name: 'app_user_getuser', methods: ['POST'])]
    public function getUserByEmail(Request $request, UserRepository $userRepository, \JMS\Serializer\SerializerInterface $serializer) {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return new JsonResponse('Email parameter is required', Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'];
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse('User not found', Response::HTTP_NOT_FOUND);
        }

        $context = SerializationContext::create()->setGroups(["getUsers"]);
        $jsonUser = $serializer->serialize($user, 'json', $context);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }
}
