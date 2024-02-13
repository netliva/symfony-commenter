<?php

namespace Netliva\CommentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('netliva_comment');
        $rootNode = method_exists(TreeBuilder::class, 'getRootNode')
            ? $treeBuilder->getRootNode()
            : $treeBuilder->root('netliva_comment');
        
		$rootNode
			->children()
                ->scalarNode('cache_path')
                    ->defaultValue(null)
                ->end()
				->scalarNode('default_emotion')
					->defaultValue('👍')
				->end()
				->arrayNode('emotions')
					->prototype('array')
						->children()
							->scalarNode('emoji')->end()
							->scalarNode('desc')->end()
							->scalarNode('color')->end()
						->end()
					->end()
					->defaultValue([
						'like'  => ['emoji' => '👍🏼', 'color'=>'#8A6749', 'desc' => 'Beğen'],
						'love'  => ['emoji' => '❤️',  'color'=>'#DD2E44', 'desc' => 'Muhteşem'],
						'haha'  => ['emoji' => '😂', 'color'=>'#DD9E00', 'desc' => 'Hahaha'],
						'wow'   => ['emoji' => '😮', 'color'=>'#DD9E00', 'desc' => 'İnanılmaz'],
						'sad'   => ['emoji' => '😔', 'color'=>'#DD9E00', 'desc' => 'Üzgün'],
						'angry' => ['emoji' => '😡', 'color'=>'#DA2F47', 'desc' => 'Kızgın'],
					])
				->end()
			->end()
		;

        return $treeBuilder;
    }
}
