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

	/**
	 * Yorum eklendikten sonra çalışır
	 *
	 * @Event(Netliva\CommentBundle\Event\CommentBoxEvent")
	 */
	const  COMMENT_BOX = 'netliva_commenter.comment_box';

	/**
	 * Kullanıcının profil fotoğrafına ulaşmak istendiğinde kullanılır
	 *
	 * @Event(Netliva\CommentBundle\Event\UserImageEvent")
	 */
	const  USER_IMAGE = 'netliva_commenter.user_image';
}