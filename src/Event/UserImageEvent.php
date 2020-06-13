<?php
namespace Netliva\CommentBundle\Event;


use Netliva\CommentBundle\Entity\AuthorInterface;
use Symfony\Component\EventDispatcher\Event;

class UserImageEvent extends Event
{
	/** @var string */
	private $image = null;

	/** @var AuthorInterface */
	private $author;

	public function __construct (AuthorInterface $author) {
		$this->author = $author;
	}

	/**
	 * @return AuthorInterface
	 */
	public function getAuthor (): AuthorInterface
	{
		return $this->author;
	}

	/**
	 * @return string
	 */
	public function getImage (): ?string
	{
		return $this->image;
	}

	/**
	 * @param string $image
	 */
	public function setImage (string $image): void
	{
		$this->image = $image;
	}


}
