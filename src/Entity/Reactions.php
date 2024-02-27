<?php

namespace Netliva\CommentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Netliva\CommentBundle\Entity\AuthorInterface;
use Netliva\CommentBundle\Entity\Comments;


/**
 * @ORM\Entity
 * @ORM\Table(name="netliva_reactions")
 */
class Reactions
{
	/**
	 * @var integer
	 *
	 * @ORM\Id()
	 * @ORM\GeneratedValue()
	 * @ORM\Column(type="integer")
	 */
    private $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(type="text")
	 */
    private $reaction;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(type="datetime", name="addAt")
	 */
    private $addAt;

	/**
	 * @var string
	 *
	 * @ORM\Column(type="string", length=255, name="addByStr")
	 */
	private $addByStr;

	/**
	 * @var AuthorInterface
	 *
	 * @ORM\ManyToOne(targetEntity="AuthorInterface")
	 * @ORM\JoinColumn(name="addBy_id")
	 */
	private $addBy;

	/**
	 * @var Comments
	 *
	 * @ORM\ManyToOne(targetEntity="Comments", inversedBy="reactions")
	 * @ORM\JoinColumn()
	 */
	private $comment;


	/**
	 * @return int
	 */
	public function getId (): ?int
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function setId (int $id): self
	{
		$this->id = $id;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getReaction (): string
	{
		return $this->reaction;
	}

	/**
	 * @param string $reaction
	 */
	public function setReaction (string $reaction): self
	{
		$this->reaction = $reaction;

		return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getAddAt (): \DateTime
	{
		return $this->addAt;
	}

	/**
	 * @param \DateTime $addAt
	 */
	public function setAddAt (\DateTime $addAt): self
	{
		$this->addAt = $addAt;

		return $this;

	}

	/**
	 * @return AuthorInterface
	 */
	public function getAddBy (): ?AuthorInterface
	{
		return $this->addBy;
	}

	/**
	 * @param AuthorInterface $addBy
	 */
	public function setAddBy (AuthorInterface $addBy): self
	{
		$this->addBy = $addBy;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAddByStr (): string
	{
		return $this->addByStr;
	}

	/**
	 * @param string $addByStr
	 */
	public function setAddByStr (string $addByStr): self
	{
		$this->addByStr = $addByStr;

		return $this;
	}


	/**
	 * @return Comments
	 */
	public function getComment (): ?Comments
	{
		return $this->comment;
	}

	/**
	 * @param Comments $comment
	 */
	public function setComment (Comments $comment): self
	{
		$this->comment = $comment;

		return $this;
	}


}
