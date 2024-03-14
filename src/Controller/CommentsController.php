<?php

namespace Netliva\CommentBundle\Controller;

use Doctrine\ORM\EntityManager;
use Netliva\CommentBundle\Entity\AuthorInterface;
use Netliva\CommentBundle\Entity\Comments;
use Netliva\CommentBundle\Entity\CommentsGroupInfo;
use Netliva\CommentBundle\Event\AfterAddCollaboratorsEvent;
use Netliva\CommentBundle\Event\AfterAddCommentEvent;
use Netliva\CommentBundle\Event\NetlivaCommenterEvents;
use Netliva\CommentBundle\Services\CommentServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Comments controller.
 */
class CommentsController extends AbstractController
{
    /**
     * @Route(name="netliva_symfony_comments_list", path="/comments/list/{group}/{listType}/{page}", defaults={"page": "1"})
     */
	public function listAction($group, $listType, $page, Request $request, CommentServices $commentServices)
	{
		return new JsonResponse($commentServices->loadComments($group, $listType, $page, $request->request->get('options')));
	}


    /**
     * @Route(name="netliva_symfony_comments_create", path="/comments/create")
     */
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
		$entity->setAuthorStr($this->getUser());
		$entity->setAuthor($this->getUser());
		$entity->setGroup($group);
		$entity->setComment($comment);

		$em = $this->getDoctrine()->getManager();

		$em->persist($entity);
		$em->flush();


		$collaborators = $this->addCollaborators($group, $this->getUser()->getId());

		$eventDispatcher = $this->get('event_dispatcher');
		$event = new AfterAddCommentEvent($entity, $collaborators);
		$eventDispatcher->dispatch($event, NetlivaCommenterEvents::AFTER_ADD);


		return new JsonResponse( ["situ" => "success", 'id' => $entity->getId()] );
	}

    /**
     * @Route(name="netliva_symfony_remove_me", path="/collaborators/remove/{group}")
     */
	public function removeCollaboratorsAction (Request $request, $group)
	{
		$em = $this->getDoctrine()->getManager();

		$colInfoEntity = $em->getRepository(CommentsGroupInfo::class)->findOneBy(['group' => $group, 'key'=> 'collaborators']);

		if ($colInfoEntity and $colInfoEntity->getInfo() and is_array($colInfoEntity->getInfo()) && in_array($this->getUser()->getId(), $colInfoEntity->getInfo()))
		{
			$info = $colInfoEntity->getInfo();

			if (($key = array_search($this->getUser()->getId(), $info)) !== false) {
				unset($info[$key]);
			}

			$colInfoEntity->setInfo($info);
			$em->flush();
		}

		return new JsonResponse( ["situ" => "success"] );
	}


    /**
     * @Route(name="netliva_symfony_new_collaborators", path="/collaborators/create/{group}")
     */
	public function createCollaboratorsAction (Request $request, $group)
	{
		$collaborators = $this->addCollaborators($group, $request->request->get('author'));
		$em = $this->getDoctrine()->getManager();


		$eventDispatcher = $this->get('event_dispatcher');
		$event = new AfterAddCollaboratorsEvent($em->getRepository(AuthorInterface::class)->find($request->request->get('author')), $collaborators, $group);
		$eventDispatcher->dispatch($event, NetlivaCommenterEvents::AFTER_ADD_COLLABORATOR);


		return new JsonResponse( ["situ" => "success"] );
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

    /**
     * @Route(name="netliva_symfony_comments_history", path="/comments/history/{id}")
     */
	public function historyAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Comments::class)->find($id);

		if (!$entity) {
			return new JsonResponse(array('situ' => "error", "errors" => ["title"=>["Unable to find Reminder entity."]]));
		}

		return $this->render("@NetlivaComment/history.html.twig", array(
			'comment' => $entity,
		));

	}


    /**
     * @Route(name="netliva_symfony_comments_update", path="/comments/update/{viewtype}/{id}")
     */
	public function updateAction(Request $request, $id, $viewtype, CommentServices $commentServices)
	{
		$em = $this->getDoctrine()->getManager();
		/** @var Comments $entity */
		$entity = $em->getRepository(Comments::class)->find($id);

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

        $data = $commentServices->createCommentData($entity);


		$html = $this->renderView('@NetlivaComment/comment.'.$viewtype.'.html.twig', array(
			'group' => $entity->getGroup(),
			'comments' => [$data],
			'options'  => $request->request->get('options'),
		));

		return new JsonResponse( ['return'=>'success', 'html'=>$html] );

	}

    /**
     * @Route(name="netliva_symfony_comments_delete", path="/comments/delete/{id}")
     */
	public function deleteAction(Request $request, $id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Comments::class)->find($id);

		if (!$entity) {
			return new JsonResponse(array('situ' => "error", "errors" => ["title"=>["Unable to find Reminder entity."]]));
		}


		$em->remove($entity);
		$em->flush();

		return new JsonResponse( array('situ' => "success", "id" => $id) );
	}

	private function addCollaborators ($group, $authorId)
	{
		$em = $this->getDoctrine()->getManager();

		$colInfoEntity = $em->getRepository(CommentsGroupInfo::class)->findOneBy(['group' => $group, 'key'=> 'collaborators']);
		if (!$colInfoEntity)
		{
			$colInfoEntity = new CommentsGroupInfo();
			$colInfoEntity->setGroup($group);
			$colInfoEntity->setKey("collaborators");
			$colInfoEntity->setInfo([]);
			$em->persist($colInfoEntity);
		}
		$collaborators = $colInfoEntity->getInfo();
		if (!in_array($authorId, $collaborators))
			$collaborators[] = $authorId;
		$colInfoEntity->setInfo($collaborators);
		$em->flush();

		return $collaborators;
	}
}
