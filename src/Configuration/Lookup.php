<?php declare(strict_types=1);

namespace Kiboko\Plugin\Akeneo\Configuration;

use Kiboko\Plugin\FastMap;
use Symfony\Component\Config;
use Symfony\Component\ExpressionLanguage\Expression;
use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

final class Lookup implements Config\Definition\ConfigurationInterface
{
    private static array $endpoints = [
        // Core Endpoints
        'product' => [
            'listPerPage',
            'all',
            'get',
        ],
        'category' => [
            'listPerPage',
            'all',
            'get',
        ],
        'attribute' => [
            'listPerPage',
            'all',
            'get',
        ],
        'attributeOption' => [
            'listPerPage',
            'all',
            'get',
        ],
        'attributeGroup' => [
            'listPerPage',
            'all',
            'get',
        ],
        'family' => [
            'listPerPage',
            'all',
            'get',
        ],
        'productMediaFile' => [
            'listPerPage',
            'all',
            'get',
        ],
        'locale' => [
            'listPerPage',
            'all',
            'get',
        ],
        'channel' => [
            'listPerPage',
            'all',
            'get',
        ],
        'currency' => [
            'listPerPage',
            'all',
            'get',
        ],
        'measureFamily' => [
            'listPerPage',
            'all',
            'get',
        ],
        'associationType' => [
            'listPerPage',
            'all',
            'get',
        ],
        'familyVariant' => [
            'listPerPage',
            'all',
            'get',
        ],
        'productModel' => [
            'listPerPage',
            'all',
            'get',
        ],
        // Enterprise Endpoints
        'publishedProduct' => [
            'listPerPage',
            'all',
            'get',
        ],
        'productModelDraft' => [
            'get',
        ],
        'productDraft' => [
            'get',
        ],
        'asset' => [
            'listPerPage',
            'all',
            'get',
        ],
        'assetCategory' => [
            'listPerPage',
            'all',
            'get',
        ],
        'assetTag' => [
            'listPerPage',
            'all',
            'get',
        ],
//        'assetReferenceFile' => [], // no support
//        'assetVariationFile' => [], // no support
        'referenceEntityRecord' => [
            'all',
            'get',
        ],
//        'referenceEntityMediaFile' => [], // no support
        'referenceEntityAttribute' => [
            'all',
            'get',
        ],
        'referenceEntityAttributeOption' => [
            'all',
            'get',
        ],
        'referenceEntity' => [
            'all',
            'get',
        ],
    ];

    public function getConfigTreeBuilder(): Config\Definition\Builder\TreeBuilder
    {
        $builder = new Config\Definition\Builder\TreeBuilder('lookup');

        $filters = new Search();

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->validate()
                ->ifTrue(fn ($data) => !array_key_exists('conditional', $data) && is_array($data))
                ->then(function (array $item) {
                    if (!in_array($item['method'], self::$endpoints[$item['type']])) {
                        throw new \InvalidArgumentException(
                            sprintf('The value should be one of [%s], got %s', implode(', ', self::$endpoints[$item['type']]), \json_encode($item['method']))
                        );
                    }

                    return $item;
                })
            ->end()
            ->validate()
                ->ifTrue(fn ($data) => !array_key_exists('conditional', $data) && array_key_exists('code', $data) && array_key_exists('type', $data) && !in_array($data['type'], ['attributeOption']))
                ->thenInvalid('The code option should only be used with the "attributeOption" endpoint.')
            ->end()
            ->validate()
                ->ifTrue(fn ($data) => array_key_exists('conditional', $data) && is_array($data['conditional']) && count($data['conditional']) <= 0)
                ->then(function ($data) {
                    unset($data['conditional']);
                    return $data;
                })
            ->end()
            ->children()
                ->scalarNode('type')
                    ->validate()
                        ->ifNotInArray(array_keys(self::$endpoints))
                        ->thenInvalid(
                            sprintf('The value should be one of [%s], got %%s', implode(', ', array_keys(self::$endpoints)))
                        )
                    ->end()
                ->end()
                ->scalarNode('code')
                    ->validate()
                        ->ifTrue(isExpression())
                        ->then(asExpression())
                    ->end()
                ->end()
                ->scalarNode('method')->end()
                ->append((new FastMap\Configuration('merge'))->getConfigTreeBuilder()->getRootNode())
                ->append($filters->getConfigTreeBuilder())
                ->append($this->getConditionalTreeBuilder()->getRootNode())
            ->end();

        return $builder;
    }

    private function getConditionalTreeBuilder(): Config\Definition\Builder\TreeBuilder
    {
        $builder = new Config\Definition\Builder\TreeBuilder('conditional');

        $filters = new Search();

        $builder->getRootNode()
            ->cannotBeEmpty()
            ->requiresAtLeastOneElement()
            ->validate()
                ->ifTrue(fn ($data) => count($data) <= 0)
                ->thenUnset()
            ->end()
            ->arrayPrototype()
                ->validate()
                    ->ifArray()
                    ->then(function (array $item) {
                        if (!in_array($item['method'], self::$endpoints[$item['type']])) {
                            throw new \InvalidArgumentException(
                                sprintf('the value should be one of [%s], got %s', implode(', ', self::$endpoints[$item['type']]), \json_encode($item['method']))
                            );
                        }

                        return $item;
                    })
                ->end()
                ->validate()
                    ->ifTrue(fn ($data) => array_key_exists('code', $data) && array_key_exists('type', $data) && !in_array($data['type'], ['attributeOption']))
                    ->thenInvalid('The code option should only be used with the "attributeOption" endpoint.')
                ->end()
                ->children()
                    ->scalarNode('condition')->end()
                    ->scalarNode('type')
                        ->isRequired()
                        ->validate()
                            ->ifNotInArray(array_keys(self::$endpoints))
                            ->thenInvalid(
                                sprintf('The value should be one of [%s], got %%s', implode(', ', array_keys(self::$endpoints)))
                            )
                        ->end()
                    ->end()
                    ->scalarNode('code')
                        ->validate()
                            ->ifTrue(fn ($data) => str_starts_with($data, '@='))
                            ->then(fn ($data) => new Expression(substr($data, 2)))
                        ->end()
                    ->end()
                    ->scalarNode('method')->isRequired()->end()
                    ->append((new FastMap\Configuration('merge'))->getConfigTreeBuilder()->getRootNode())
                    ->append($filters->getConfigTreeBuilder())
                ->end()
            ->end();

        return $builder;
    }
}