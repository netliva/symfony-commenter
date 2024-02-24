<?php
namespace Netliva\CommentBundle\Event;


use Netliva\CommentBundle\Entity\AuthorInterface;
use Netliva\CommentBundle\Entity\Comments;
use Symfony\Contracts\EventDispatcher\Event;

class AfterAddCollaboratorsEvent extends Event
{

	private $author;
	/**
	 * @var array|null
	 */
	private $collaborators;
	/**
	 * @var string|null
	 */
	private $group;

	/**
	 * AfterAddCommentEvent constructor.
	 *
	 * @param Comments|null $comment
	 * @param array|null    $collaborators
	 */
	public function __construct (?AuthorInterface $author, ?array $collaborators, ?string $group) {
		$this->author        = $author;
		$this->collaborators = $collaborators;
		$this->group = $group;
	}

	public function getAuthor ()
	{
		return $this->author;
	}

	/**
	 * @return array|null
	 */
	public function getCollaborators (): ?array
	{
		return $this->collaborators;
	}

	/**
	 * @return string|null
	 */
	public function getGroup (): ?string
	{
		return $this->group;
	}

}
