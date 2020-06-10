<?php

namespace Netliva\CommentBundle\Services;

use Netliva\CommentBundle\Event\CommentBoxEvent;
use Netliva\CommentBundle\Event\NetlivaCommenterEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CommentServices extends AbstractExtension
{
	protected $em;
	protected $twig;
	/**
	 * @var ContainerInterface
	 */
	private $container;

	public function __construct($em, Environment $twig, ContainerInterface $container){
		$this->em = $em;
		$this->twig = $twig;
		$this->container = $container;
	}


	public function getFunctions()
	{
		return array(
			new TwigFunction('commentbox', [$this, 'commentBox'], array('is_safe' => array('html'))),
		);
	}



	public function commentBox($group, $listType="default")
	{

		$comments =	$this->em->getRepository('NetlivaCommentBundle:Comments')->findByGroup($group);

		$eventDispatcher = $this->container->get('event_dispatcher');
		$event = new CommentBoxEvent($comments, $group, $listType);
		$eventDispatcher->dispatch(NetlivaCommenterEvents::COMMENT_BOX, $event);


		return $this->twig->render("@NetlivaComment/comments.html.twig", array(
			'group'      => $group,
			'comments'   => $comments,
			'listType'   => $listType,
			'topContent' => $event->getTopContent(),
		));


	}

}
