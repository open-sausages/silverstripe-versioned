<?php

namespace SilverStripe\Versioned\GraphQL\Types;

use Exception;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\InterfaceTypeCreator;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

if (!class_exists(InterfaceTypeCreator::class)) {
    return;
}

class VersionInterfaceType extends InterfaceTypeCreator
{
    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'VersionInterface',
            'description' => 'Metadata fields for versioning on a DataObject'
        ];
    }

    /**
     * @return array
     */
    public function fields()
    {
        $memberType = StaticSchema::inst()->typeNameForDataObject(Member::class);
        return [
            'Author' => [
                'type' => $this->manager->getType($memberType),
                'resolve' => function ($obj) {
                    return $obj->Author();
                }
            ],
            'Publisher' => [
                'type' => $this->manager->getType($memberType),
                'resolve' => function ($obj) {
                    return $obj->Publisher();
                }
            ],
            'Published' => [
                'type' => Type::boolean(),
                'resolve' => function ($obj) {
                    return $obj->WasPublished;
                }
            ],
            'LiveVersion' => [
                'type' => Type::boolean(),
                'resolve' => function ($obj) {
                    return $obj->isLiveVersion();
                }
            ],
            'LatestDraftVersion' => [
                'type' => Type::boolean(),
                'resolve' => function ($obj) {
                    return $obj->isLatestDraftVersion();
                }
            ],
        ];
    }

    /**
     * @return \GraphQL\Type\Definition\Type
     * @throws Exception
     */
    public function resolveType()
    {
        if (!$obj instanceof DataObject) {
            throw new Exception(sprintf(
                'Type with class %s is not a DataObject',
                get_class($obj)
            ));
        }
        $class = get_class($obj);
        while ($class !== DataObject::class) {
            $typeName = StaticSchema::inst()->typeNameForDataObject($class);
            if ($this->manager->hasType($typeName)) {
                return $this->manager->getType($typeName);
            }
            $class = get_parent_class($class);
        }
        throw new Exception(sprintf(
            'There is no type defined for %s, and none of its ancestors are defined.',
            get_class($obj)
        ));
    }

}
