<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{


    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $userData = $serializer->deserialize($request->getContent(), User::class, 'json');

        $errors = $validator->validate($userData);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $encodedPassword = $userPasswordHasher->hashPassword($userData, $userData->getPassword());
        $userData->setPassword($encodedPassword);
        $userData->setRoles(['ROLE_USER']);

        $entityManager->persist($userData);
        $entityManager->flush();


        $location = $urlGenerator->generate('app_user_getuser', ['id' => $userData->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse('User registered successfully', Response::HTTP_CREATED, ['location' => $location]);
    }

}
