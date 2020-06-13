<?php
namespace Netliva\CommentBundle\Event;


use Netliva\CommentBundle\Entity\Comments;
use Symfony\Component\EventDispatcher\Event;

class CommentBoxEvent extends Event
{

	private $comments;
	/**
	 * @var string
	 */
	private $group;
	/**
	 * @var array
	 */
	private $options;
	/**
	 * @var string
	 */
	private $top_content = '';

	/**
	 * CommentsTopContentEvent constructor.
	 *
	 * @param Comments[] $comments
	 * @param string     $group
	 * @param array      $options
	 */
	public function __construct ($comments, $group, $options) {

		$this->comments = $comments;
		$this->group    = $group;
		$this->options  = $options;
	}

	public function getComments ()
	{
		return $this->comments;
	}

	/**
	 * @return string
	 */
	public function getGroup (): string
	{
		return $this->group;
	}

	/**
	 * @return array
	 */
	public function getOptions (): array
	{
		return $this->options;
	}

	/**
	 * @return string
	 */
	public function getTopContent (): string
	{
		return $this->top_content;
	}

	/**
	 * @param string $top_content
	 */
	public function setTopContent (string $top_content): void
	{
		$this->top_content = $top_content;
	}

}
