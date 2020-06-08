<?php
namespace Netliva\CommentBundle\Event;


final class NetlivaCommenterEvents
{
	/**
	 * Yorum eklendikten sonra çalışır
	 *
	 * @Event("Netliva\CommentBundle\Event\AfterAddCommentEvent")
	 */

	const  AFTER_ADD = 'netliva_commenter.after_add';
}
