<?php

namespace Netliva\CommentBundle\Services;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CommentServices extends AbstractExtension
{
	protected $em;
	protected $twig;
	public function __construct($em, Environment $twig){
		$this->em = $em;
		$this->twig = $twig;
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


		return $this->twig->render("@NetlivaComment/comments.html.twig", array(
			'group' => $group,
			'comments' => $comments,
			'listType' => $listType,
		));


	}

}
