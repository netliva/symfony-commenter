<?php

namespace Netliva\CommentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Netliva\CommentBundle\Entity\AuthorInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="netliva_commenter")
 */
class Comments
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
	 * @ORM\Column(name="`group`", type="string", length=255)
	 */
    private $group;

	/**
	 * @var string
	 *
	 * @ORM\Column(type="text")
	 */
    private $comment;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(type="datetime")
	 */
    private $addAt;

	/**
	 * @var AuthorInterface
	 *
	 * @ORM\ManyToOne(targetEntity="AuthorInterface")
	 * @ORM\JoinColumn()
	 */
	private $author;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime", nullable=true)
	 */
    private $editAt;

	/**
	 * @var array
	 *
	 * @ORM\Column(type="json", nullable=true)
	 */
    private $history;

	/**
	 * @var AuthorInterface
	 *
	 * @ORM\ManyToOne(targetEntity="AuthorInterface")
	 * @ORM\JoinColumn(nullable=true)
	 */
    private $editor;

	/**
	 * @return int
	 */
	public function getId (): int
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
	public function getGroup (): string
	{
		return $this->group;
	}

	/**
	 * @param string $group
	 */
	public function setGroup (string $group): self
	{
		$this->group = $group;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getComment (): string
	{
		return $this->comment;
	}

	/**
	 * @param string $comment
	 */
	public function setComment (string $comment): self
	{
		$this->comment = $comment;

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
	public function getAuthor (): AuthorInterface
	{
		return $this->author;
	}

	/**
	 * @param AuthorInterface $author
	 */
	public function setAuthor (AuthorInterface $author): self
	{
		$this->author = $author;

		return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getEditAt (): ?\DateTime
	{
		return $this->editAt;
	}

	/**
	 * @param \DateTime $editAt
	 */
	public function setEditAt (?\DateTime $editAt): self
	{
		$this->editAt = $editAt;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getHistory (): ?array
	{
		return $this->history;
	}

	/**
	 * @param array $history
	 */
	public function setHistory (?array $history): self
	{
		$this->history = $history;

		return $this;
	}

	/**
	 * @return AuthorInterface
	 */
	public function getEditor (): ?AuthorInterface
	{
		return $this->editor;
	}

	/**
	 * @param AuthorInterface $editor
	 */
	public function setEditor (?AuthorInterface $editor): self
	{
		$this->editor = $editor;

		return $this;
	}


}
