<?php

namespace Netliva\CommentBundle\EventListener;

use Crm\LiftBundle\Entity\Asansorler;
use Crm\LiftBundle\Entity\Denetimler;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query;
use Netliva\CommentBundle\Entity\Comments;
use Netliva\CommentBundle\Entity\CommentsGroupInfo;
use Netliva\CommentBundle\Entity\Reactions;
use Netliva\CommentBundle\Services\CacheUpdaterServices;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CacheClearEventListener
{
    private $container;
    private $cus;

    public function __construct (ContainerInterface $container, CacheUpdaterServices $cus)
    {
        $this->container = $container;
        $this->cus = $cus;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->controlAndClearCache('persist', $args);
    }
    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->controlAndClearCache('update', $args);
    }
    public function preRemove(LifecycleEventArgs $args)
    {
        $this->controlAndClearCache('remove', $args);
    }

    private function controlAndClearCache (string $action, LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity->getId())
        {
            if (get_class($entity) == Comments::class)
            {
                if (!$this->cus->openData($entity->getGroup()))
                    return;
                
                switch ($action) {
                    case 'persist': $this->cus->addData($entity); break;
                    case 'update': $this->cus->updateData($entity); break;
                    case 'remove': $this->cus->removeData($entity); break;
                }
            }
            elseif (get_class($entity) == Reactions::class) {
                $this->cus->openData($entity->getComment()->getGroup());
            }

            foreach ([Reactions::class => ['comment']] as $className => $cacheClearFields)
            {
                if (get_class($entity) == $className)
                {
                    foreach ($cacheClearFields as $reversField)
                        $this->upateCacheByReverseEntities($entity, $reversField);
                }
            }

            $this->cus->saveData();

        }

    }


    private function upateCacheByReverseEntities($entity, $field): void
    {
        $aField = explode('.', $field);
        $fKey = array_shift($aField);

        // gelen değer tek bir entity ise
        if (is_object($entity) && !($entity instanceof PersistentCollection) && !($entity instanceof ArrayCollection) && $subEntity = $this->getEntityValue($entity, $fKey))
        {
            // eğer field birden fazla derinliğe sahip ise içe doğru kontrole devam et
            if (count($aField))
            {
                $this->upateCacheByReverseEntities($subEntity, implode('.', $aField));
                return;
            }

            // bulunan veri bir kolleksiyon ise;
            if (is_array($subEntity) || $subEntity instanceof PersistentCollection || $subEntity instanceof ArrayCollection )
            {
                foreach ($subEntity as $item)
                    $this->cus->updateData($item);
                return;
            }

            // eğer bulunan değer tek bir entity ise;
            $this->cus->updateData($subEntity);
            return;
        }

        // gelen değer entity collection ise
        if ((is_object($entity) && ($entity instanceof PersistentCollection || $entity instanceof ArrayCollection)))
        {
            // her entity için işlemi gerçekleştirmek üzere fonksiyonu yine çağır
            foreach ($entity as $ent)
                $this->upateCacheByReverseEntities($ent, $field);
        }
    }

    private function getEntityValue ($entity, string $field)
    {
        if (method_exists($entity, 'get'.ucfirst($field)))
        {
            return $entity->{'get'.ucfirst($field)}();
        }

        if (method_exists($entity, 'is'.ucfirst($field)))
        {
            return $entity->{'is'.ucfirst($field)}();
        }

        if (method_exists($entity, $field))
        {
            return $entity->{ucfirst($field)}();
        }

        return null;
    }
}
