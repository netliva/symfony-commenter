<?php

namespace Netliva\CommentBundle\Services;

use Netliva\CommentBundle\Entity\Comments;
use Netliva\CommentBundle\Entity\Reactions;
use Netliva\CommentBundle\Event\CommentBoxEvent;
use Netliva\CommentBundle\Event\NetlivaCommenterEvents;
use Netliva\CommentBundle\Event\UserImageEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CommentServices extends AbstractExtension
{
	protected $em;
	/**
	 * @var ContainerInterface
	 */
	private $container;
    private $allCollaborators = null;
    private $cachePath;
    private $limitPerPage = 6;

	public function __construct($em, ContainerInterface $container ){
		$this->em = $em;
		$this->container = $container;

        $cachePath = $this->container->getParameter('netliva_commenter.cache_path');
        if (!$cachePath) $cachePath = $this->container->getParameter('kernel.cache_dir').DIRECTORY_SEPARATOR.'netliva_comment';

        if(!is_dir($cachePath))
            mkdir($cachePath, 0777, true);

        $this->cachePath = $cachePath;
    }


	private function getUser()
	{
		return $this->container->get('security.token_storage')->getToken()->getUser();
	}

	public function getFilters()
	{
		return array(
			new TwigFilter('prepareCollaboratorsObject', [$this, 'prepareCollaboratorsObject'], array('is_safe' => array('html'))),
		);
	}

	public function getFunctions()
	{
		return array(
			new TwigFunction('commentbox', [$this, 'commentBox'], array('is_safe' => array('html'))),
			new TwigFunction('reaction_button', [$this, 'reactionButton'], array('is_safe' => array('html'))),
		);
	}

	private function prepareAllCollaborators ()
	{
        if (!is_null($this->allCollaborators))
            return $this->allCollaborators;

		$authors = $this->em->getRepository("NetlivaCommentBundle:AuthorInterface")->findAll();
        $this->allCollaborators = [];
		foreach ($authors as $author)
		{
			if ($author->isAuthor() && !key_exists($author->getId(), $this->allCollaborators))
                $this->allCollaborators[$author->getId()] = $this->prepareCollaboratorsObject($author);
		}
        uasort($this->allCollaborators, function($a, $b) {
            $c = new \Collator('tr_TR');
            return $c->compare($a['name'], $b['name']);
        });

		return $this->allCollaborators;
	}

	private function prepareCollaborators ($group)
	{
		$collaboratorIds = null;
		$colInfoEntity = $this->em->getRepository('NetlivaCommentBundle:CommentsGroupInfo')->findOneBy(['group' => $group, 'key'=> 'collaborators']);
		if ($colInfoEntity)
		{
            $collaboratorIds = $colInfoEntity->getInfo();
		}

		if (is_array($collaboratorIds) and count($collaboratorIds))
		{
			$collaborators = [];
			foreach ($collaboratorIds as $id)
			{
                if ($this->allCollaborators && key_exists($id, $this->allCollaborators))
                {
                    $collaborators[$id] = $this->allCollaborators[$id];
                }
                else
                {
                    $author = $this->em->getRepository("NetlivaCommentBundle:AuthorInterface")->find($id);
                    if ($author && $author->isAuthor())
                    {
                        $collaborators[$id] = $this->prepareCollaboratorsObject($author);
                        if (is_array($this->allCollaborators) && !key_exists($id, $this->allCollaborators))
                            $this->allCollaborators[$id] = $collaborators[$id];
                    }
                }
			}
            usort($collaborators, function($a, $b) {
                $c = new \Collator('tr_TR');
                return $c->compare($a['name'], $b['name']);
            });
            return $collaborators;
		}

		return [];
	}


    public function createCommentData (Comments $comment)
    {
        $data = [
            'id'        => $comment->getId(),
            'comment'   => $comment->getComment(),
            'addAt'     => $comment->getAddAt() ? $comment->getAddAt()->format('c') : null,
            'editAt'    => $comment->getEditAt() ? $comment->getEditAt()->format('c') : null,
            'history'   => $comment->getHistory(),
            'authorStr' => $comment->getAuthorStr(),
            'reactions' => [],
            'editor'    => $comment->getEditor() ? [
                'id'   => $comment->getEditor()->getId(),
                'name' => (string)$comment->getEditor(),
            ] : null,
            'author'    => $comment->getAuthor() ? [
                'id'   => $comment->getAuthor()->getId(),
                'name' => (string)$comment->getAuthor(),
            ] : null,
            'answerTo'  => $comment->getAnswerTo() ? [
                'id'        => $comment->getAnswerTo()->getId(),
                'comment'   => $comment->getAnswerTo()->getComment(),
                'addAt'     => $comment->getAnswerTo()->getAddAt() ? $comment->getAnswerTo()->getAddAt()->format('c') : null,
                'authorStr' => $comment->getAnswerTo()->getAuthorStr(),
                'author'    => $comment->getAnswerTo()->getAuthor() ? [
                    'id'   => $comment->getAnswerTo()->getAuthor()->getId(),
                    'name' => (string)$comment->getAnswerTo()->getAuthor(),
                ] : null,
            ] : null,
        ];

        /** @var Reactions $reaction */
        if ($comment->getReactions())
        {
            foreach ($comment->getReactions() as $reaction)
            {
                if ($reaction->getAddBy())
                    $data['reactions'][$reaction->getAddBy()->getId()] = $reaction->getReaction();
            }
        }

        return $data;
    }

    public function reCreateCacheData ($group)
    {

        $tempPath = $this->cachePath.'/'.$group.'-temp.json';
        $filePath = $this->cachePath.'/'.$group.'.json';

        if(file_exists($tempPath))
            unlink($tempPath);

        $count = $this->em->getRepository('NetlivaCommentBundle:Comments')->createQueryBuilder('comments')
            ->select('COUNT(comments.id) as total')
            ->where('comments.group = :gr')
            ->setParameter("gr",$group)
            ->getQuery()->getSingleResult();
        $count = (int) $count["total"];

        $say = 0;
        $limit = 500;
        $dataFile = fopen($tempPath, 'w');
        fwrite($dataFile, "[".PHP_EOL);
        // $data = [];
        for ($i = 0; $i<ceil($count/$limit); $i++)
        {
            $this->em->clear();
            $qb = $this->em->getRepository('NetlivaCommentBundle:Comments')->createQueryBuilder("c");
            $qb->where($qb->expr()->eq("c.group", ":g"));
            $qb->setParameter("g", $group);
            $qb->orderBy("c.addAt", "DESC");
            $qb->setMaxResults($limit);
            $qb->setFirstResult($i*$limit);
            $query = $qb->getQuery();
            foreach ($query->getResult() as $entity)
            {
                $say++;
                // $data[] = $fss->getEntObj($entity, $entityInfos[$entKey]['fields'], $entKey);
                $data = $this->createCommentData($entity);
                unset($entity);
                fwrite($dataFile, json_encode($data).($say==$count?'':',').PHP_EOL);
                unset($data);
            }

        }
        fwrite($dataFile, "]");
        fclose($dataFile);


        if(file_exists($tempPath))
        {
            if(file_exists($filePath))
                unlink($filePath);
            rename($tempPath, $filePath);
        }
    }

	public function loadComments($group, $listType, $page = 1, $options=[])
	{
        $filePath = $this->cachePath.'/'.$group.'.json';
        if(!file_exists($filePath))
        {
            $this->reCreateCacheData($group);
        }

        $comments      = json_decode(file_get_contents($filePath), true);
        $all           = count($comments);
        if (!$comments) $comments = [];
        $comments      = $this->sort($comments, 'addAt', 'desc');
        $count         = count($comments);
        $comments      = array_slice($comments, $this->limitPerPage * ($page - 1), $this->limitPerPage);
        $comments      = $this->sort($comments, 'addAt');

        if ($count)
        {
            $html = $this->container->get('templating')->render('@NetlivaComment/comment.'.$listType.'.html.twig', array(
                'group'    => $group,
                'comments' => $comments,
                'options'  => $options,
            ));
        }
        else
        {
            $html = '<li class="text-center"><em>İlk Yorumu Sen Yap</em></li>';
        }

        return [
            'total'  => $count,
            'count'  => count($comments),
            'html'   => $html
        ];
	}


    public function prepareCollaboratorsObject($author)
	{
		$eventDispatcher = $this->container->get('event_dispatcher');
		$event = new UserImageEvent($author);
		$eventDispatcher->dispatch(NetlivaCommenterEvents::USER_IMAGE, $event);
		return [
			'id'    => $author->getId(),
			'name'  => (string)$author,
			'photo' => $event->getImage(),
		];
	}

	public function commentBox($group, $options = [])
	{
		$options = array_merge([
		   'list_type'        => 'default',
		   'predefined_texts' => [],
		   'collaborators'    => true,
		   'reactions'        => true,
		   'pre_load'         => null, // ön yüklenecek maksimum yorum sayısı
	    ], $options);
        
        $preloadComments = [];
        if ($options['pre_load'] && is_numeric($options['pre_load']))
        {
            $preloadComments = $this->loadComments($group, $options['list_type'], 1, $options);
        }
        
		$eventDispatcher = $this->container->get('event_dispatcher');
		$event           = new CommentBoxEvent($group, $options);
		$eventDispatcher->dispatch(NetlivaCommenterEvents::COMMENT_BOX, $event);


		return $this->container->get('twig')->render("@NetlivaComment/comments.html.twig", array(
            'group'           => $group,
            'allAuthors'      => $this->prepareAllCollaborators(),
            'collaborators'   => $options['collaborators'] ? $this->prepareCollaborators($group) : [],
            'options'         => $options,
            'preloadComments' => $preloadComments,
            'topContent'      => $event->getTopContent(),
		));
	}

	public function getReactionCounts (array $reactions)
	{
		$emotions = [];
		/** @var Reactions $reaction */
		foreach ($reactions as $reaction)
		{
			if(!key_exists($reaction, $emotions)) $emotions[$reaction] = 0;
			$emotions[$reaction]++;
		}
		return $emotions;
	}

	public function reactionButton (array $comment)
	{
        $reaction = key_exists($this->getUser()->getId(), $comment['reactions']) ? $comment['reactions'][$this->getUser()->getId()] : null;
		return $this->container->get('twig')->render('@NetlivaComment/reaction_button.html.twig', [
			'emo_counts'    => $this->getReactionCounts($comment['reactions']),
			"comment"       => $comment,
			"my_last_react" => $reaction,
			"emotions"      => $this->container->getParameter('netliva_commenter.emotions'),
			"def_emo"       => $this->container->getParameter('netliva_commenter.default_emotion'),
		]);

	}



    public function sort($array, $field, $direction = 'asc')
    {
        if (!is_array($array)) return $array;

        $c = new \Collator('tr_TR');
        usort($array, function ($a, $b) use ($field, $direction, $c)
        {
            if (preg_match('/^([^.]+)\.(.+)/', $field, $matches))
            {
                $aVal = $a[$matches[1]];
                $bVal = $b[$matches[1]];
                foreach (explode('.', $matches[2]) as $item)
                {
                    if (is_array($aVal) && key_exists($item, $aVal))
                        $aVal = $aVal[$item];
                    elseif ($item == 'length' && is_array($aVal))
                        $aVal = count($aVal);

                    if (is_array($bVal) && key_exists($item, $bVal))
                        $bVal = $bVal[$item];
                    elseif ($item == 'length' && is_array($bVal))
                        $bVal = count($bVal);
                }
            }
            else {
                $aVal = $a[$field];
                $bVal = $b[$field];
            }

            if (!is_string($aVal) && !is_numeric($aVal)) $aVal = '';
            if (!is_string($bVal) && !is_numeric($bVal)) $bVal = '';

            $compare = $c->compare($aVal, $bVal);

            if (!$compare)
                return 0;

            if ($direction == 'desc')
                return -$compare;

            return $compare;
        });

        return $array;
    }

    public function binarySearch(array $haystack, $needle, $field, $compare, $high, $low = 0, $containsDuplicates = false)
    {
        $key = false;
        // Whilst we have a range. If not, then that match was not found.
        while ($high >= $low) {
            // Find the middle of the range.
            $mid = (int)floor(($high + $low) / 2);
            // Compare the middle of the range with the needle. This should return <0 if it's in the first part of the range,
            // or >0 if it's in the second part of the range. It will return 0 if there is a match.
            $cmp = call_user_func($compare, $needle, $haystack[$mid][$field]);
            // Adjust the range based on the above logic, so the next loop iteration will use the narrowed range
            if ($cmp < 0)
                $high = $mid - 1;
            elseif ($cmp > 0)
                $low = $mid + 1;
            else
            {
                // We've found a match
                if ($containsDuplicates) {
                    // Find the first item, if there is a possibility our data set contains duplicates by comparing the
                    // previous item with the current item ($mid).
                    while ($mid > 0 && call_user_func($compare, $haystack[($mid - 1)][$field], $haystack[$mid][$field]) === 0) {
                        $mid--;
                    }
                }
                $key = $mid;
                break;
            }
        }

        return $key;
    }
}
