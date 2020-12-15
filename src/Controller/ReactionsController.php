<?php

namespace Netliva\CommentBundle\Controller;

use Netliva\CommentBundle\Entity\Comments;
use Netliva\CommentBundle\Entity\Reactions;
use Netliva\CommentBundle\Event\AfterAddReactionEvent;
use Netliva\CommentBundle\Event\NetlivaCommenterEvents;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Comments controller.
 */
class ReactionsController extends Controller
{

	public function addAction(Request $request, Comments $comment)
	{
		$em = $this->getDoctrine()->getManager();

		$reaction = $em->getRepository('NetlivaCommentBundle:Reactions')->findOneBy(["comment"=>$comment, "addBy"=>$this->getUser()],['id'=>'DESC']);

		$type = 'nothing';
		if ($reaction)
		{
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
			$type = 'new';
			$reaction = new Reactions();
			$reaction->setAddAt(new \DateTime());
			$reaction->setAddBy($this->getUser());
			$reaction->setAddByStr((string)$this->getUser());
			$reaction->setComment($comment);
			$reaction->setReaction($request->request->get("reaction"));
			$em->persist($reaction);
		}
		$em->flush();

		$eventDispatcher = $this->get('event_dispatcher');
		$event = new AfterAddReactionEvent($type, $reaction);
		$eventDispatcher->dispatch(NetlivaCommenterEvents::AFTER_REACTION, $event);
		
		$commenter = $this->get('netliva_commenter');
		return new JsonResponse(["situ" => "success", "type" => $type, 'counts' => $commenter->getReactionCounts($comment)]);
	}

	public function historyAction(Comments $comment)
	{
		return $this->render('@NetlivaComment/reaction_history.html.twig', [
			"emotions"  => $this->container->getParameter('netliva_commenter.emotions'),
			'reactions' => $comment->getReactions()
		]);
	}
}


