<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\MicroPost;
use App\Form\CommentType;
use DateTime;
use App\Repository\MicroPostRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Form\MicroPostType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MicroPostController extends AbstractController
{
    #[Route('/micro-post', name: 'app_micro_post')]
    public function index(MicroPostRepository $posts): Response
    {
        // dd($posts->findAll());
        // return $this->render('micro_post/index.html.twig', [
        //     'posts' => $posts->findAll(),
        // ]);

        return $this->render('micro_post/index.html.twig', [
            'posts' => $posts->findAllWithComments(),
        ]);
    }

    #[Route('/micro-post/{post}', name: 'app_micro_post_show')]
    public function showOne(MicroPost $post): Response
    {
        return $this->render('micro_post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/micro-post/add', name: 'app_micro_post_add', priority: 2)]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MicroPostType::class, new MicroPost());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
                $post = $form->getData();
                $post->setCreated(new DateTime());
                $post->setAuthor($this->getUser());
                // $posts->add($post, true); //depricated
                $em->persist($post);
                $em->flush();

        // Add a flash
        $this->addFlash('success', 'Your micro post have been added.');

        // Redirect 
        return $this->redirectToRoute('app_micro_post');
        }

        return $this->render(
            'micro_post/add.html.twig',
            [
                'form' => $form
            ]
        );
    }


    #[Route('/micro-post/{post}/edit', name: 'app_micro_post_edit')]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(MicroPost $post, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MicroPostType::class, $post);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            // $posts->add($post, true); //depricated
            $em->persist($post);
            $em->flush();

            // Add a flash
            $this->addFlash('success', 'Your micro post have been updated.');

            // Redirect
            return $this->redirectToRoute('app_micro_post');
        }

        return $this->render(
            'micro_post/edit.html.twig',
            [
                'form' => $form,
                'post' => $post
            ]
        );
    }

    #[Route('/micro-post/{post}/comment', name: 'app_micro_post_comment')]
    #[IsGranted('ROLE_COMMENTER')]
    public function addComment(MicroPost $post, Request $request, CommentRepository $comments, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CommentType::class, new Comment());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->getData();
            $comment->setPost($post);
            $comment->setAuthor($this->getUser());
            //$comments->add($comment, true); //depricated
            $em->persist($comment);
            $em->flush();

            // Add a flash
            $this->addFlash('success', 'Your comment have been published!');

            // Redirect
            return $this->redirectToRoute(
                'app_micro_post_show',
                ['post' => $post->getId()]
            );
            
        }

        return $this->render(
            'micro_post/comment.html.twig',
            [
                'form' => $form,
                'post' => $post
            ]
        );
    }
}