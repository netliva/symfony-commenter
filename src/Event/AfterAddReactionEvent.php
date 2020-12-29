<?php
namespace Netliva\CommentBundle\Event;


use Netliva\CommentBundle\Entity\Comments;
use Netliva\CommentBundle\Entity\Reactions;
use Symfony\Component\EventDispatcher\Event;

class AfterAddReactionEvent extends Event
{

	/**
	 * @var string|null
	 */
	private $type;
	/**
	 * @var Reactions|null
	 */
	private $reaction;

	/**
	 * AfterAddCommentEvent constructor.
	 *
	 * @param string|null $type
	 * @param Reactions|null    $reaction
	 */
	public function __construct (?string $type, ?Reactions $reaction) {
		$this->type = $type;
		$this->reaction = $reaction;
	}

	/**
	 * @return string|null (remove, update, new, nothing)
	 */
	public function getType ()
	{
		return $this->type;
	}

	/**
	 * @return Reactions|null
	 */
	public function getReaction (): ?Reactions
	{
		return $this->reaction;
	}

}
