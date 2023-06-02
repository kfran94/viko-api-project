<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;


class ReservationController extends AbstractController
{
    #[Route('/api/reservations', name: 'app_reservations')]
    public function getAllReservations(ReservationRepository $reservationRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllReservations";

        $jsonReservationList = $cache->get($idCache, function (ItemInterface $item) use ($reservationRepository, $serializer) {
            $item->tag("reservationsCache");
            $reservationList = $reservationRepository->findAll();
            $context = SerializationContext::create()->setGroups(['getReservations']);
            return $serializer->serialize($reservationList, 'json', $context);
        });
        return new JsonResponse($jsonReservationList, Response::HTTP_OK, [], true);
    }


    #[Route('/api/reservations/{id}', name: 'detail_reservation', methods: ['GET'])]
    public function getReservation(Reservation $reservation, SerializerInterface $serializer) {
        $context = SerializationContext::create()->setGroups(["getReservations"]);
        $jsonReservation = $serializer->serialize($reservation, 'json', $context);
        return new JsonResponse($jsonReservation, Response::HTTP_OK, [], true);
    }

    #[Route('/admin/reservations/{id}', name: 'delete_reservation', methods: ['DELETE'])]
    public function deleteReservation(Reservation $reservation, EntityManagerInterface $entityManager): JsonResponse{
        $entityManager->remove($reservation);
        $entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/admin/reservations/create', name: 'create_reservation', methods: ['POST'])]
    public function createReservation(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager,
                                      UrlGeneratorInterface $urlGenerator, UserRepository $userRepository, ValidatorInterface $validator, MailerService $mailerService): JsonResponse
    {
        $reservation = $serializer->deserialize($request->getContent(), Reservation::class, 'json');

        $content = $request->toArray();
        $clientId = $content['clientId'] ?? -1;

        $client = $userRepository->find($clientId);
        if(!$client){
            return new JsonResponse("Client not found", Response::HTTP_NOT_FOUND);
        }

        $reservation->setClientId($client);

        $errors = $validator->validate($reservation);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManager->persist($reservation);
        $entityManager->flush();
//        $dateMail = $reservation->getDate()->format('d-m-Y H:i');
//        $emailContent = "Une nouvelle réservation a été effectuée par Mr/Mme " . $client->getName() . " pour le " . $dateMail;
//
//        $mailerService->sendEmail($emailContent);
        $context = SerializationContext::create()->setGroups(["getReservations"]);
        $jsonReservation = $serializer->serialize($reservation, 'json', $context);

        $location = $urlGenerator->generate('detail_reservation', ['id'=> $reservation->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonReservation, Response::HTTP_CREATED, ["location" => $location], true);
    }

    #[Route('/admin/reservations/{id}', name: 'update_reservation', methods: ['PUT'])]
    public function updateReservation(Request $request,SerializerInterface $serializer, Reservation $currentReservation,
                                      EntityManagerInterface $entityManager, UserRepository $userRepository, ValidatorInterface $validator, TagAwareCacheInterface $cache) {
        $newReservation = $serializer->deserialize($request->getContent(), Reservation::class, 'json');

        $currentReservation->setDate($newReservation->getDate());
        $currentReservation->setServices($newReservation->getServices());

        $errors = $validator->validate($currentReservation);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }


        $client = $currentReservation->getClientId();

        $currentReservation->setClientId($client);

        $entityManager->persist($currentReservation);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/reservations/search', name: 'search_reservations', methods: ['POST'])]
    public function searchReservations(Request $request, ReservationRepository $reservationRepository, SerializerInterface $serializer): JsonResponse
    {
        $content = $request->getContent();
        $data = json_decode($content, true);

        $date = $data['date'] ?? null;

        if (!$date) {
            return new JsonResponse('Missing date parameter', Response::HTTP_BAD_REQUEST);
        }

        $startDate = new \DateTime($date);
        $startDate->setTime(0, 0, 0); // Début de la journée

        $endDate = new \DateTime($date);
        $endDate->setTime(23, 59, 59); // Fin de la journée

        $reservations = $reservationRepository->createQueryBuilder('r')
            ->where('r.date >= :start_date AND r.date <= :end_date')
            ->setParameter('start_date', $startDate)
            ->setParameter('end_date', $endDate)
            ->getQuery()
            ->getResult();

        $context = SerializationContext::create()->setGroups(['getReservations']);
        $jsonReservations = $serializer->serialize($reservations, 'json', $context);

        return new JsonResponse($jsonReservations, Response::HTTP_OK, [], true);
    }


}
