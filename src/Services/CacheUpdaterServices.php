<?php

namespace Netliva\CommentBundle\Services;



use Doctrine\ORM\EntityManagerInterface;
use Netliva\CommentBundle\Entity\Comments;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CacheUpdaterServices
{
	public function __construct(
        private readonly ContainerInterface $container,
        private readonly CommentServices $commentServices
    ){
    }


    private array $data        = [];
    private bool  $dataChanged = false;
    private       $group       = null;
    private string|null $cachePath = null;

    public function openData ($group)
    {
        $cachePath = $this->container->getParameter('netliva_commenter.cache_path');
        if (!$cachePath) $cachePath = $this->container->getParameter('kernel.cache_dir').DIRECTORY_SEPARATOR.'netliva_comment';

        if(!is_dir($cachePath))
            mkdir($cachePath, 0777, true);

        $this->cachePath = $cachePath;

        $filePath  = $this->cachePath.'/'.$group.'.json';
        
        if(!file_exists($filePath))
            return false;

        $this->group       = $group;
        $this->data        = json_decode(file_get_contents($filePath), true);
        $this->dataChanged = false;
        if (!is_array($this->data)) $this->data = [];

        return  true;
    }
    public function addData ($entity)
    {
        if (get_class($entity) == Comments::class)
        {
            $this->data[] = $this->commentServices->createCommentData($entity);
            $this->dataChanged = true;
        }
    }
    public function updateData ($entity)
    {
        if (get_class($entity) == Comments::class)
        {
            $this->data = $this->commentServices->sort($this->data, 'id');
            $key  = $this->commentServices->binarySearch($this->data, $entity->getId(), 'id', 'strcmp', count($this->data) - 1, 0, true);
            if (strlen($key))
            {
                $this->data[$key]  = $this->commentServices->createCommentData($entity);
                $this->dataChanged = true;
            }
        }
    }
    public function removeData ($entity)
    {
        if (get_class($entity) == Comments::class)
        {
            $this->data = $this->commentServices->sort($this->data, 'id');
            $key  = $this->commentServices->binarySearch($this->data, $entity->getId(), 'id', 'strcmp', count($this->data) - 1, 0, true);
            if (strlen($key))
            {
                unset($this->data[$key]);
                $this->dataChanged = true;
            }
        }
    }
    public function saveData (): void
    {
        if ($this->dataChanged)
        {
            $filePath  = $this->cachePath.'/'.$this->group.'.json';
            
            if(!file_exists($filePath))
                return;

            file_put_contents($filePath, json_encode($this->data));
        }
    }

}
