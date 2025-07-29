<?php

namespace Netliva\CommentBundle\Controller;

use Netliva\CommentBundle\Entity\AuthorInterface;
use Netliva\CommentBundle\Entity\Comments;
use Netliva\CommentBundle\Entity\Reactions;
use Netliva\CommentBundle\Event\AfterAddReactionEvent;
use Netliva\CommentBundle\Event\NetlivaCommenterEvents;
use Netliva\CommentBundle\Services\CommentServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/comments/reaction')]
class ReactionsController extends AbstractController
{
    public function __construct(private readonly \Doctrine\Persistence\ManagerRegistry $managerRegistry)
    {
    }
    #[Route(name: 'netliva_symfony_reaction_add', path: '/add/{id}')]
    public function addAction(Request $request, Comments $comment, CommentServices $commentServices, EventDispatcherInterface $dispatcher)
	{
		$em = $this->managerRegistry->getManager();

		$reaction = $em->getRepository(Reactions::class)->findOneBy(["comment"=>$comment, "addBy"=>$this->getUser()],['id'=>'DESC']);

		$old = null;
		$type = 'nothing';
		if ($reaction)
		{
		    $old = $reaction->getReaction();
			if ($reaction->getReaction() == $request->request->get("reaction") || !$request->request->get("reaction"))
			{
				$em->remove($reaction);
				$type = 'remove';
			}
			else
			{
				$reaction->setReaction($request->request->get("reaction"));
				$reaction->setAddAt(new \DateTime());
				$type = 'update';
			}
		}
		else if ($request->request->get("reaction"))
		{
            $user = $this->getUser();
			$type = 'new';
			$reaction = new Reactions();
			$reaction->setAddAt(new \DateTime());
			$reaction->setAddBy($user instanceof AuthorInterface ? $user : null);
			$reaction->setAddByStr((string)$this->getUser());
			$reaction->setComment($comment);
			$reaction->setReaction($request->request->get("reaction"));
			$em->persist($reaction);
		}
		$em->flush();
		$em->refresh($comment);

		$event = new AfterAddReactionEvent($type, $reaction, $old);
		$dispatcher->dispatch($event, NetlivaCommenterEvents::AFTER_REACTION);
		
        $reacts = [];
        foreach ($comment->getReactions() as $reaction)
        {
            $reacts[$reaction->getAddBy()->getId()] = $reaction->getReaction();
        }
		return new JsonResponse(["situ" => "success", "type" => $type, 'counts' => $commentServices->getReactionCounts($reacts)]);
	}

    #[Route(name: 'netliva_symfony_reaction_history', path: '/history/{id}')]
    public function historyAction(Comments $comment)
	{
		return $this->render('@NetlivaComment/reaction_history.html.twig', [
			"emotions"  => $this->getParameter('netliva_commenter.emotions'),
			'reactions' => $comment->getReactions()
		]);
	}
}


