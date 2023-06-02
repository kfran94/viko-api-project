<?php

namespace App\Controller;

use App\Entity\OpenningHours;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class AdminParamsController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('admin/create-default-opening-hours', name: 'create_default_opening_hours')]
    #[IsGranted("ROLE_ADMIN")]
    public function createDefaultOpeningHours(): Response
    {
        $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $openingTime = new \DateTime('08:00');
        $breakTime = new \DateTime('12:00');
        $closingTime = new \DateTime('20:00');

        foreach ($days as $day) {
            $openingHours = new OpenningHours();
            $openingHours->setDay($day)
                ->setOpeningHours($openingTime)
                ->setClosingHours($closingTime)
                ->setBreak($breakTime);

            $this->entityManager->persist($openingHours);
        }

        $this->entityManager->flush();

        return new Response('Les horaires par défaut ont été créer avec succès.');
    }
}
