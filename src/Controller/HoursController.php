<?php

namespace App\Controller;

use App\Entity\OpenningHours;
use App\Repository\OpenningHoursRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class HoursController extends AbstractController
{
    #[Route('/api/hours', name: 'app_hours')]
    public function getAllHours(OpenningHoursRepository $hoursRepository, SerializerInterface $serializer): JsonResponse
    {
        $hoursList = $hoursRepository->findAll();

        $jsonHoursList = $serializer->serialize($hoursList, 'json');

        return new JsonResponse($jsonHoursList, Response::HTTP_OK, [], true);
    }

    #[Route('hours/{id}', name: 'app_hour', methods: ['GET'])]
    public function getHours(OpenningHours $hours, SerializerInterface $serializer) {
            $jsonHour = $serializer->serialize($hours, 'json');
            return new JsonResponse($jsonHour, Response::HTTP_OK, [], true);
    }

    #[Route('/admin/hours/{id}', name: 'update_hours', methods: ['PUT'])]
    public function updateReservation(Request $request, \JMS\Serializer\SerializerInterface $serializer, OpenningHours $currentOpenningHours,
                                      EntityManagerInterface $entityManager, UserRepository $userRepository, ValidatorInterface $validator, TagAwareCacheInterface $cache) {
        $newHours = $serializer->deserialize($request->getContent(), OpenningHours::class, 'json');

        $currentOpenningHours->setOpeningHours($newHours->getOpeningHours());
        $currentOpenningHours->setClosingHours($newHours->getClosingHours());
        $currentOpenningHours->setBreak($newHours->getBreak());

        $errors = $validator->validate($currentOpenningHours);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($currentOpenningHours);
        $entityManager->flush();


        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
