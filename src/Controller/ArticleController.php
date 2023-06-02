<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class ArticleController extends AbstractController
{
    #[Route('/admin/article/create', name: 'create_article', methods: ['POST'])]
    public function createArticle(
        Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $title = $request->request->get('Title');
        $content = $request->request->get('content');
        $uploadedFile = $request->files->get('photo');

        $article = new Article();
        $article->setTitle($title);
        $article->setContent($content);
        $article->setCreatedAt(new \DateTimeImmutable());

        if ($uploadedFile) {
            $newFilename = $this->generateUniqueFilename() . '.' . $uploadedFile->guessExtension();

            try {
                $uploadedFile->move($this->getParameter('upload_directory'), $newFilename);
            } catch (FileException $e) {
                return $this->json(['error' => 'Failed to upload photo'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $article->setPhoto($newFilename);
        }

        $errors = $validator->validate($article);

        if (count($errors) > 0) {
            return $this->json(['errors' => (string)$errors], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($article);
        $entityManager->flush();

        return $this->json($article, Response::HTTP_CREATED, [], [
            AbstractNormalizer::ATTRIBUTES => ['id', 'title', 'content', 'createdAt', 'photo'],
        ]);
    }


    #[Route('/admin/article/delete/{id}', name: 'delete_article', methods: ['DELETE'])]
    public function deleteArticle(
        Article                $article,
        EntityManagerInterface $entityManager,
        Filesystem             $filesystem
    ): JsonResponse
    {

        $entityManager->remove($article);
        $entityManager->flush();

        // Vérifier s'il y a une photo liée à l'article
        $photoFilename = $article->getPhoto();
        if ($photoFilename !== null) {
            // Supprimer la photo du dossier de téléchargement
            $uploadDirectory = $this->getParameter('upload_directory');
            $photoPath = $uploadDirectory . '/' . $photoFilename;

            if ($filesystem->exists($photoPath)) {
                $filesystem->remove($photoPath);
            }
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/article/{id}', name: 'get_article', methods: ['GET'])]
    public function getArticle(Article $article): JsonResponse
    {
        $photo = $article->getPhoto();

        if ($photo) {
            $photoUrl = $this->generateUrl('article_photo', ['filename' => $photo]);
            $article->setPhoto($photoUrl);
        }

        // Formater la date au format DD/MM/YYYY
        $createdAt = $article->getCreatedAt()->format('d/m/Y');

        // Créer un tableau associatif avec les données sérialisées
        $serializedArticle = [
            'id' => $article->getId(),
            'Title' => $article->getTitle(),
            'content' => $article->getContent(),
            'CreatedAt' => $createdAt,
            'photo' => $article->getPhoto(),
        ];

        return $this->json($serializedArticle, Response::HTTP_OK);
    }


    #[Route('/listArticles', name: 'get_all_articles', methods: ['GET'])]
    public function getArticles(
        Request             $request,
        ArticleRepository   $articleRepository,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $articles = $articleRepository->findAllWithPaginationSortedByDate($page, $limit);
        $totalItems = $articleRepository->count([]);
        $totalPages = ceil($totalItems / $limit);

        $serializedArticles = [];

        foreach ($articles as $article) {
            $photo = $article->getPhoto();

            if ($photo) {
                $photoUrl = $this->generateUrl('article_photo', ['filename' => $photo]);
                $article->setPhoto($photoUrl);
            }

            // Formater la date au format DD/MM/YYYY
            $createdAt = $article->getCreatedAt()->format('d/m/Y');

            // Créer un tableau associatif avec les données sérialisées
            $serializedArticle = [
                'id' => $article->getId(),
                'Title' => $article->getTitle(),
                'content' => $article->getContent(),
                'CreatedAt' => $createdAt,
                'photo' => $article->getPhoto(),
            ];

            $serializedArticles[] = $serializedArticle;
        }

        return $this->json([
            'articles' => $serializedArticles,
            'totalItems' => $totalItems,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ], Response::HTTP_OK);
    }


    #[Route('/articles/photos/{filename}', name: 'article_photo', methods: ['GET'])]
    public function getArticlePhoto(string $filename): Response
    {
        $photoDirectory = $this->getParameter('upload_directory');
        $filePath = $photoDirectory . '/' . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('La photo demandée n\'existe pas.');
        }

        $response = new Response(file_get_contents($filePath));

        $response->headers->set('Content-Type', 'image/jpg');

        return $response;
    }


    private function generateUniqueFilename(): string
    {
        return md5(uniqid());
    }

    #[Route('/articles/last', name: 'get_last_articles', methods: ['GET'])]
    public function getLastArticles(ArticleRepository $articleRepository): JsonResponse
    {
        $articles = $articleRepository->findBy([], ['CreatedAt' => 'DESC'], 3);

        $serializedArticles = [];

        foreach ($articles as $article) {
            $photo = $article->getPhoto();

            if ($photo) {
                $photoUrl = $this->generateUrl('article_photo', ['filename' => $photo]);
                $article->setPhoto($photoUrl);
            }

            // Formater la date au format DD/MM/YYYY
            $createdAt = $article->getCreatedAt()->format('d/m/Y');

            // Créer un tableau associatif avec les données sérialisées
            $serializedArticle = [
                'id' => $article->getId(),
                'Title' => $article->getTitle(),
                'content' => $article->getContent(),
                'CreatedAt' => $createdAt,
                'photo' => $article->getPhoto(),
            ];

            $serializedArticles[] = $serializedArticle;
        }

        return $this->json($serializedArticles, Response::HTTP_OK);
    }
}


