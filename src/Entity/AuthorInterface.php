<?php


namespace Netliva\CommentBundle\Entity;


interface AuthorInterface
{
	/**
	 * @return string
	 */
	public function __toString();

	/**
	 * @return bool
	 */
	public function isAuthor():bool;
}
