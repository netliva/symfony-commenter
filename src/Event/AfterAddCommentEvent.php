<?php
namespace Netliva\CommentBundle\Event;


use Netliva\CommentBundle\Entity\Comments;
use Symfony\Component\EventDispatcher\Event;

class AfterAddCommentEvent extends Event
{

	private $comment;
	public function __construct (?Comments $comment) {
		$this->comment = $comment;
	}

	public function getComment ()
	{
		return $this->comment;
	}

}
