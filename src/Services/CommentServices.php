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
    private $allCollaborators = null;

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
        if (!is_null($this->allCollaborators))
            return $this->allCollaborators;

		$authors = $this->em->getRepository("NetlivaCommentBundle:AuthorInterface")->findAll();
        $this->allCollaborators = [];
		foreach ($authors as $author)
		{
			if ($author->isAuthor() && !key_exists($author->getId(), $this->allCollaborators))
                $this->allCollaborators[$author->getId()] = $this->prepareCollaboratorsObject($author);
		}
        uasort($this->allCollaborators, function($a, $b) {
            $c = new \Collator('tr_TR');
            return $c->compare($a['name'], $b['name']);
        });

		return $this->allCollaborators;
	}

	private function prepareCollaborators ($group)
	{
		$collaboratorIds = null;
		$colInfoEntity = $this->em->getRepository('NetlivaCommentBundle:CommentsGroupInfo')->findOneBy(['group' => $group, 'key'=> 'collaborators']);
		if ($colInfoEntity)
		{
            $collaboratorIds = $colInfoEntity->getInfo();
		}

		if (is_array($collaboratorIds) and count($collaboratorIds))
		{
			$collaborators = [];
			foreach ($collaboratorIds as $id)
			{
                if ($this->allCollaborators && key_exists($id, $this->allCollaborators))
                {
                    $collaborators[$id] = $this->allCollaborators[$id];
                }
                else
                {
                    $author = $this->em->getRepository("NetlivaCommentBundle:AuthorInterface")->find($id);
                    if ($author && $author->isAuthor())
                    {
                        $collaborators[$id] = $this->prepareCollaboratorsObject($author);
                        if (is_array($this->allCollaborators) && !key_exists($id, $this->allCollaborators))
                            $this->allCollaborators[$id] = $collaborators[$id];
                    }
                }
			}
            usort($collaborators, function($a, $b) {
                $c = new \Collator('tr_TR');
                return $c->compare($a['name'], $b['name']);
            });
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

		$eventDispatcher = $this->container->get('event_dispatcher');
		$event           = new CommentBoxEvent($group, $options);
		$eventDispatcher->dispatch(NetlivaCommenterEvents::COMMENT_BOX, $event);


		return $this->container->get('twig')->render("@NetlivaComment/comments.html.twig", array(
			'group'         => $group,
			'allAuthors'    => $this->prepareAllCollaborators(),
			'collaborators' => $options['collaborators'] ? $this->prepareCollaborators($group) : [],
			'options'       => $options,
			'topContent'    => $event->getTopContent(),
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

		return $this->container->get('twig')->render('@NetlivaComment/reaction_button.html.twig', [
			'emo_counts'    => $this->getReactionCounts($comment),
			"comment"       => $comment,
			"my_last_react" => $reaction ? $reaction->getReaction() : null,
			"emotions"      => $this->container->getParameter('netliva_commenter.emotions'),
			"def_emo"       => $this->container->getParameter('netliva_commenter.default_emotion'),
		]);

	}

}
