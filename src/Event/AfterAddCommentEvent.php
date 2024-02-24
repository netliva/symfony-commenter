<?php
namespace Netliva\CommentBundle\Event;


use Netliva\CommentBundle\Entity\Comments;
use Symfony\Contracts\EventDispatcher\Event;

class AfterAddCommentEvent extends Event
{

	private $comment;
	/**
	 * @var array|null
	 */
	private $collaborators;

	/**
	 * AfterAddCommentEvent constructor.
	 *
	 * @param Comments|null $comment
	 * @param array|null    $collaborators
	 */
	public function __construct (?Comments $comment, ?array $collaborators) {
		$this->comment = $comment;
		$this->collaborators = $collaborators;
	}

	public function getComment ()
	{
		return $this->comment;
	}

	/**
	 * @return array|null
	 */
	public function getCollaborators (): ?array
	{
		return $this->collaborators;
	}

}
