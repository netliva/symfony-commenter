<?php
namespace Netliva\CommentBundle\Event;


use Netliva\CommentBundle\Entity\Comments;
use Netliva\CommentBundle\Entity\Reactions;
use Symfony\Contracts\EventDispatcher\Event;

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
	 * @var string|null
	 */
	private $oldReactionKey;

	/**
	 * AfterAddCommentEvent constructor.
	 *
	 * @param string|null $type
	 * @param Reactions|null    $reaction
	 */
	public function __construct (?string $type, ?Reactions $reaction, ?string $oldReactionKey) {
		$this->type = $type;
		$this->reaction = $reaction;
		$this->oldReactionKey = $oldReactionKey;
	}

	/**
	 * @return string|null (remove, update, new, nothing)
	 */
	public function getType ()
	{
		return $this->type;
	}
	/**
	 * @return string|null
	 */
	public function getOldReactionKey ()
	{
		return $this->oldReactionKey;
	}

	/**
	 * @return Reactions|null
	 */
	public function getReaction (): ?Reactions
	{
		return $this->reaction;
	}

}
