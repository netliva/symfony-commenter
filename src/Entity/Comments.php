<?php

namespace Netliva\CommentBundle\Entity;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
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
	 * @ORM\Column(type="datetime", name="addAt")
	 */
    private $addAt;

	/**
	 * @var string
	 *
	 * @ORM\Column(type="string", length=255)
	 */
	private $author_str;

	/**
	 * @var AuthorInterface
	 *
	 * @ORM\ManyToOne(targetEntity="AuthorInterface")
	 * @ORM\JoinColumn()
	 */
	private $author;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime", name="editAt", nullable=true)
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
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\OneToMany(targetEntity="Netliva\CommentBundle\Entity\Reactions", mappedBy="comment", cascade={"persist", "remove"})
	 */
	private $reactions;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\OneToMany(targetEntity="Netliva\CommentBundle\Entity\Comments", mappedBy="answerTo", cascade={"persist", "remove"})
	 */
	private $answers;

	/**
	 * @var Comments
	 *
	 * @ORM\ManyToOne(targetEntity="Netliva\CommentBundle\Entity\Comments", inversedBy="answers")
	 * @ORM\JoinColumn(nullable=true, name="answerTo_id")
	 */
	private $answerTo;


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
	public function getAuthor (): ?AuthorInterface
	{
		if ($this->author)
		{
		    try {
			    $this->author->__toString();
		    }
		    catch (EntityNotFoundException $exception)
		    {
                $this->setAuthor(null);
                return null;
		    }
		}

		return $this->author;
	}

	/**
	 * @param AuthorInterface $author
	 */
	public function setAuthor (?AuthorInterface $author): self
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

	/**
	 * @return string
	 */
	public function getAuthorStr (): string
	{
		return $this->author_str;
	}

	/**
	 * @param string $author_str
	 */
	public function setAuthorStr (string $author_str): void
	{
		$this->author_str = $author_str;
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getReactions (): ?\Doctrine\Common\Collections\Collection
	{
		return $this->reactions;
	}

	/**
	 * @param \Doctrine\Common\Collections\Collection $reactions
	 */
	public function setReactions (\Doctrine\Common\Collections\Collection $reactions): void
	{
		$this->reactions = $reactions;
	}

	/**
	 * @param Reactions $reaction
	 *
	 * @return $this
	 */
	public function addReaction(Reactions $reaction) : self
	{
		if($this->reactions->contains($reaction)) return $this;
		$this->reactions[] = $reaction;
		$reaction->setComment($this);
		return $this;
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getAnswers (): \Doctrine\Common\Collections\Collection
	{
		return $this->answers;
	}

	/**
	 * @param \Doctrine\Common\Collections\Collection $answers
	 */
	public function setAnswers (\Doctrine\Common\Collections\Collection $answers): void
	{
		$this->answers = $answers;
	}

	public function addAnswer(Comments $answer)
	{
		if($this->answers->contains($answer)) return $this;
		$this->answers[] = $answer;
		$answer->setAnswerTo($this);
		return $this;
	}

	/**
	 * @return Comments
	 */
	public function getAnswerTo (): ?Comments
	{
		return $this->answerTo;
	}

	/**
	 * @param Comments $answerTo
	 */
	public function setAnswerTo (Comments $answerTo): void
	{
		$this->answerTo = $answerTo;
	}


}
