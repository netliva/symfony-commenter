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
		$em->refresh($comment);

		$eventDispatcher = $this->get('event_dispatcher');
		$event = new AfterAddReactionEvent($type, $reaction, $old);
		$eventDispatcher->dispatch(NetlivaCommenterEvents::AFTER_REACTION, $event);
		
        $reacts = [];
        foreach ($comment->getReactions() as $reaction)
        {
            $reacts[$reaction->getAddBy()->getId()] = $reaction->getReaction();
        }
		$commenter = $this->get('netliva_commenter');
		return new JsonResponse(["situ" => "success", "type" => $type, 'counts' => $commenter->getReactionCounts($reacts)]);
	}

	public function historyAction(Comments $comment)
	{
		return $this->render('@NetlivaComment/reaction_history.html.twig', [
			"emotions"  => $this->container->getParameter('netliva_commenter.emotions'),
			'reactions' => $comment->getReactions()
		]);
	}
}


