<?php

namespace Netliva\CommentBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Netliva\CommentBundle\Entity\Comments;
use Symfony\Component\HttpFoundation\JsonResponse;
use function Doctrine\ORM\QueryBuilder;

/**
 * Comments controller.
 */
class CommentsController extends AbstractController
{
	public function commentBoxAction($group, $listType)
	{
		$em = $this->getDoctrine()->getManager();
		return $this->render("@NetlivaComment/comments.html.twig", array(
			'group' => $group,
			'listType' => $listType,
		));

	}


	public function listAction($group, $listType, $limitId, $limit)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();

		$count = $em->getRepository('NetlivaCommentBundle:Comments')->createQueryBuilder('comments')
			->select('COUNT(comments.id) as total')
			->where('comments.group = :gr')
			->setParameter("gr",$group)
			->getQuery()->getSingleResult();


		$qb = $em->getRepository('NetlivaCommentBundle:Comments')->createQueryBuilder("c");
		$qb->where($qb->expr()->eq("c.group", ":g"));
		$qb->setParameter("g", $group);
		$qb->orderBy("c.addAt", "DESC");
		$qb->setMaxResults($limit);

		if ($limitId)
		{
			$qb->andWhere(
				$qb->expr()->lt("c.id", $limitId)
			);
		}

		$comments = $qb->getQuery()->getResult();

		uasort($comments, function ($first, $second) { return $first->getAddAt() > $second->getAddAt() ? 1 : -1; });
		$lastId = 0;
		if (count($comments))
		{
			$first = current($comments);
			$lastId = $first->getId();
		}

		return new JsonResponse([
			'total' => (int) $count["total"],
			'count' => count($comments),
			'lastId' => $lastId,
			'html'  => $this->renderView('@NetlivaComment/comment.'.$listType.'.html.twig', array(
						'group' => $group,
						'comments' => $comments,
					))
		]);

	}



	public function createAction(Request $request)
	{
		$entity = new Comments();

		$comment = $request->request->get("comment");
		$group = $request->request->get("group");

		if (!$comment || !$group)
		{
			$err = [];
			if (!$comment) $err[] = "Yorum Girmediniz!";
			if (!$group) $err[] = "Yorum Grubu Ayarlanmamış!";

			return new JsonResponse( ["situ" => "error", "errors"=> $err], 500);
		}

		$entity->setAddAt(new \DateTime());
		$entity->setAuthor($this->getUser());
		$entity->setGroup($group);
		$entity->setComment($comment);

		$em = $this->getDoctrine()->getManager();

		$em->persist($entity);
		$em->flush();

		return new JsonResponse( ["situ" => "success", 'id' => $entity->getId()] );
	}

	private function commentNotify($identify, $whoId, $textId, $vars, $link)
	{
		$notify = $this->notifier->isThereNotify($identify, $whoId, $textId);
		if ($notify)
		{
			$oldVars = $notify->getVars();
			if (!in_array($this->getUser()->getName(), $oldVars["{who}"]))
			{
				$oldVars["{who}"][] = $this->getUser()->getName();
				$vars = ["{who}" =>  $oldVars["{who}"]];
				$this->notifier->updateVarsOfNotify($identify, $whoId, $textId, $vars);
			}
		}
		else
			$this->notifier->addNewNotify($identify, $whoId, $textId, $vars, $link, "info", "comment");

	}

	public function historyAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository('NetlivaCommentBundle:Comments')->find($id);

		if (!$entity) {
			return new JsonResponse(array('situ' => "error", "errors" => ["title"=>["Unable to find Reminder entity."]]));
		}
		
		return $this->render("@NetlivaComment/history.html.twig", array(
			'comment' => $entity,
		));

	}


	public function updateAction(Request $request, $id, $viewtype)
	{
		$em = $this->getDoctrine()->getManager();
		/** @var Comments $entity */
		$entity = $em->getRepository('NetlivaCommentBundle:Comments')->find($id);

		if (!$entity) {
			return new JsonResponse(array("errors" => ["title"=>["Unable to find Reminder entity."]]), 404);
		}

		if ($entity->getAuthor()->getId() != $this->getUser()->getId())
			return new JsonResponse(array("errors" => ["title"=>["Bu yorumu düzenleyemezsiniz."]]), 403);


		$date = $entity->getEditAt();
		if (!$date) $date = $entity->getAddAt();
		$staff = $entity->getEditor();
		if (!$staff) $staff = $entity->getAuthor();

		$hist = $entity->getHistory();
		if (!is_array($hist)) $hist = [];
		$hist[$date->format("Y-m-d H:i:s")] = ["staffId"=>$staff->getId(),"comment"=>$entity->getComment()];
		$entity->setHistory($hist);

		$entity->setComment($request->request->get("comment"));
		$entity->setEditAt(new \DateTime());
		$entity->setEditor($this->getUser());

		$em->persist($entity);
		$em->flush();



		$html = $this->renderView('@NetlivaComment/comment.'.$viewtype.'.html.twig', array(
			'group' => $entity->getGroup(),
			'comments' => [$entity],
		));

		return new JsonResponse( ['return'=>'success', 'html'=>$html] );

	}


	public function deleteAction(Request $request, $id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository('NetlivaCommentBundle:Comments')->find($id);

		if (!$entity) {
			return new JsonResponse(array('situ' => "error", "errors" => ["title"=>["Unable to find Reminder entity."]]));
		}


		$em->remove($entity);
		$em->flush();

		return new JsonResponse( array('situ' => "success", "id" => $id) );
	}
}