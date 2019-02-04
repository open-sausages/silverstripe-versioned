<?php

namespace SilverStripe\Versioned\GraphQL\Extensions;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use SilverStripe\Core\Extension;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\GraphQL\Operations\ReadVersions;
use SilverStripe\Versioned\Versioned;

class DataObjectScaffolderExtension extends Extension
{
    /**
     * @var bool
     */
    protected $useVersionedFilter = false;

    /**
     * @var bool
     */
    protected $useVersionedMetadata = false;

    /**
     * @return bool
     */
    public function getUseVersionedFilter()
    {
        return $this->useVersionedFilter;
    }

    /**
     * @param bool $useVersionedFilter
     */
    public function setUseVersionedFilter($useVersionedFilter)
    {
        $this->useVersionedFilter = $useVersionedFilter;
    }

    /**
     * @return bool
     */
    public function getUseVersionedMetadata()
    {
        return $this->useVersionedMetadata;
    }

    /**
     * @param bool $useVersionedMetadata
     */
    public function setUseVersionedMetadata($useVersionedMetadata)
    {
        $this->useVersionedMetadata = $useVersionedMetadata;
    }

    public function onAfterOperation($operation)
    {
        // Defined on ReadExtension
        if ($operation->hasExtension(ReadExtension::class)) {
            $operation->setUseVersionedFilter($this->getUseVersionedFilter());
        }
    }

    /**
     * Adds the "Version" and "Versions" fields to any dataobject that has the Versioned extension.
     * @param Manager $manager
     */
    public function onBeforeAddToManager(Manager $manager)
    {
        /* @var DataObjectScaffolder $owner */
        $owner = $this->owner;
        $memberType = StaticSchema::inst()->typeNameForDataObject(Member::class);
        $instance = $owner->getDataObjectInstance();
        $class = $owner->getDataObjectClass();

        if (!$instance->hasExtension(Versioned::class)) {
            return;
        }

        if (!$this->getUseVersionedMetadata()) {
            return;
        }

        /* @var ObjectType $rawType */
        $rawType = $owner->scaffold($manager);

        $versionName = $this->createVersionedTypeName($class);
        $coreFieldsFn = $rawType->config['fields'];
        // Create the "version" type for this dataobject. Takes the original fields
        // and augments them with the Versioned_Version specific fields
        $versionType = new ObjectType([
            'name' => $versionName,
            'fields' => function () use ($coreFieldsFn, $manager, $memberType) {
                $coreFields = $coreFieldsFn();
                $versionFields = [
                    'Author' => [
                        'type' => $manager->getType($memberType),
                        'resolve' => function ($obj) {
                            return $obj->Author();
                        }
                    ],
                    'Publisher' => [
                        'type' => $manager->getType($memberType),
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
                // Remove this recursive madness.
                unset($coreFields['Versions']);

                return array_merge($coreFields, $versionFields);
            }
        ]);

        $manager->addType($versionType, $versionName);

        // With the version type in the manager now, add the versioning fields to the dataobject type
        $owner
            ->addFields(['Version'])
            ->nestedQuery('Versions', new ReadVersions($class, $versionName));
    }

    /**
     * @param string $class
     * @return string
     */
    protected function createVersionedTypeName($class)
    {
        return StaticSchema::inst()->typeNameForDataObject($class).'Version';
    }
}
