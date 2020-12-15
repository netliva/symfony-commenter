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
	 * Katılımcı eklendikten sonra çalışır
	 *
	 * @Event("Netliva\CommentBundle\Event\AfterAddCollaboratorsEvent")
	 */
	const  AFTER_ADD_COLLABORATOR = 'netliva_commenter.after_add_collaborator';

	/**
	 * Yorum eklendikten sonra çalışır
	 *
	 * @Event(Netliva\CommentBundle\Event\CommentBoxEvent")
	 */
	const  COMMENT_BOX = 'netliva_commenter.comment_box';

	/**
	 * İfade bıraktıktan sonra çalışır
	 *
	 * @Event(Netliva\CommentBundle\Event\AfterAddReactionEvent")
	 */
	const  AFTER_REACTION = 'netliva_commenter.after_reaction';

	/**
	 * Kullanıcının profil fotoğrafına ulaşmak istendiğinde kullanılır
	 *
	 * @Event(Netliva\CommentBundle\Event\UserImageEvent")
	 */
	const  USER_IMAGE = 'netliva_commenter.user_image';
}
