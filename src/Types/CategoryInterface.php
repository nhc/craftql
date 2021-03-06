<?php

namespace markhuot\CraftQL\Types;

use GraphQL\Type\Definition\InterfaceType;
use markhuot\CraftQL\Builders\InterfaceBuilder;
use markhuot\CraftQL\FieldBehaviors\CategoryQueryArguments;

class CategoryInterface extends InterfaceBuilder {

    function boot() {
        $this->addIntField('id')->nonNull();
        $this->addStringField('title')->nonNull();
        $this->addStringField('slug');
        $this->addStringField('uri');
        $this->addIntField('level');
        $this->addStringField('group')->type(CategoryGroup::class);
        $this->addField('children')
            ->type(CategoryInterface::class)
            ->lists()
            ->use(new CategoryQueryArguments)
            ->resolve(function ($root, $args, $context, $info) {
            return CategoryInterface::criteriaResolver($root, $args, $context, $info, $root->getChildren());
        });
        $this->addField('childrenConnection')
            ->type(CategoryConnection::class)
            ->use(new CategoryQueryArguments)
            ->resolve(function ($root, $args, $context, $info) {
                list($pageInfo, $categories) = \craft\helpers\Template::paginateCriteria(static::criteriaResolver($root, $args, $context, $info, $root->getChildren()));

                return [
                    'totalCount' => $pageInfo->total,
                    'pageInfo' => $pageInfo,
                    'edges' => $categories,
                ];
            });
    }

    function getResolveType() {
        return function ($category) {
            return ucfirst($category->group->handle).'Category';
        };
    }

    static function criteriaResolver($root, $args, $context, $info, $criteria=null) {
        $criteria = $criteria ?: \craft\elements\Category::find();

        if (isset($args['group'])) {
            $args['groupId'] = $args['group'];
            unset($args['group']);
        }

        foreach ($args as $key => $value) {
            $criteria = $criteria->{$key}($value);
        }

        return $criteria;
    }

}