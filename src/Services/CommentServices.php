<?php

namespace Netliva\CommentBundle\Services;

use Netliva\CommentBundle\Entity\Comments;
use Netliva\CommentBundle\Event\CommentBoxEvent;
use Netliva\CommentBundle\Event\NetlivaCommenterEvents;
use Netliva\CommentBundle\Event\UserImageEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CommentServices extends AbstractExtension
{
	protected $em;
	/**
	 * @var ContainerInterface
	 */
	private $container;

	public function __construct($em, ContainerInterface $container ){
		$this->em = $em;
		$this->container = $container;
	}


	private function getUser()
	{
		return $this->container->get('security.token_storage')->getToken()->getUser();
	}

	public function getFilters()
	{
		return array(
			new TwigFilter('prepareCollaboratorsObject', [$this, 'prepareCollaboratorsObject'], array('is_safe' => array('html'))),
		);
	}

	public function getFunctions()
	{
		return array(
			new TwigFunction('commentbox', [$this, 'commentBox'], array('is_safe' => array('html'))),
			new TwigFunction('reaction_button', [$this, 'reactionButton'], array('is_safe' => array('html'))),
		);
	}

	private function prepareAllCollaborators ()
	{
		$authors = $this->em->getRepository("NetlivaCommentBundle:AuthorInterface")->findAll();
		$collaborators = [];
		foreach ($authors as $author)
		{
			if ($author->isAuthor())
				$collaborators[] = $this->prepareCollaboratorsObject($author);
		}
		return $collaborators;
	}

	private function prepareCollaborators ($group)
	{
		$collaborators = null;
		$colInfoEntity = $this->em->getRepository('NetlivaCommentBundle:CommentsGroupInfo')->findOneBy(['group' => $group, 'key'=> 'collaborators']);
		if ($colInfoEntity)
		{
			$collaborators = $colInfoEntity->getInfo();
		}

		if (is_array($collaborators) and count($collaborators))
		{
			$qb = $this->em->getRepository("NetlivaCommentBundle:AuthorInterface")->createQueryBuilder("ai");
			$qb->where($qb->expr()->in("ai.id", ":ids"));
			$qb->setParameter('ids', $collaborators);
			$authors = $qb->getQuery()->getResult();

			$collaborators = [];
			foreach ($authors as $author)
			{
				if ($author->isAuthor())
					$collaborators[] = $this->prepareCollaboratorsObject($author);
			}
			return $collaborators;
		}

		return [];
	}

	public function prepareCollaboratorsObject($author)
	{
		$eventDispatcher = $this->container->get('event_dispatcher');
		$event = new UserImageEvent($author);
		$eventDispatcher->dispatch(NetlivaCommenterEvents::USER_IMAGE, $event);
		return [
			'id'    => $author->getId(),
			'name'  => (string)$author,
			'photo' => $event->getImage(),
		];
	}

	public function commentBox($group, $options = [])
	{
		$options = array_merge([
		   'list_type'        => 'default',
		   'predefined_texts' => [],
		   'collaborators'    => true,
		   'reactions'        => true,
	    ],$options);

		$comments        = $this->em->getRepository('NetlivaCommentBundle:Comments')->findByGroup($group);
		$eventDispatcher = $this->container->get('event_dispatcher');
		$event           = new CommentBoxEvent($comments, $group, $options);
		$eventDispatcher->dispatch(NetlivaCommenterEvents::COMMENT_BOX, $event);


		return $this->container->get('templating')->render("@NetlivaComment/comments.html.twig", array(
			'group'         => $group,
			'comments'      => $comments,
			'collaborators' => $options['collaborators'] ? $this->prepareCollaborators($group) : [],
			'options'       => $options,
			'allAuthors'    => $this->prepareAllCollaborators(),
			'topContent'    => $event->getTopContent(),
			"emotions"      => $this->container->getParameter('netliva_commenter.emotions'),
			"def_emo"       => $this->container->getParameter('netliva_commenter.default_emotion'),
		));
	}

	public function getReactionCounts (Comments $comment)
	{
		$emotions = [];
		/** @var Reactions $reaction */
		foreach ($comment->getReactions() as $reaction)
		{
			if(!key_exists($reaction->getReaction(), $emotions)) $emotions[$reaction->getReaction()] = 0;
			$emotions[$reaction->getReaction()]++;
		}
		return $emotions;
	}

	public function reactionButton (Comments $comment)
	{
		$reaction = $this->em->getRepository('NetlivaCommentBundle:Reactions')->findOneBy(["comment"=>$comment, "addBy"=>$this->getUser()],['id'=>'DESC']);

		return $this->container->get('templating')->render('@NetlivaComment/reaction_button.html.twig', [
			'emo_counts'    => $this->getReactionCounts($comment),
			"comment"       => $comment,
			"my_last_react" => $reaction ? $reaction->getReaction() : null,
			"emotions"      => $this->container->getParameter('netliva_commenter.emotions'),
			"def_emo"       => $this->container->getParameter('netliva_commenter.default_emotion'),
		]);

	}

}
