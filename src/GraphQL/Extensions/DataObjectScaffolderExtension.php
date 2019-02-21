<?php

namespace SilverStripe\Versioned\GraphQL\Extensions;

use Exception;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use SilverStripe\Core\Extension;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\GraphQL\Operations\ReadVersions;
use SilverStripe\Versioned\Versioned;

class DataObjectScaffolderExtension extends Extension
{
    /**
     * @var string
     */
    protected $versionInterfaceName = 'VersionInterface';

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

        /* @var ObjectType $rawType */
        $rawType = $owner->scaffold($manager);

        /** @var InterfaceType $rawVersionInterfaceType */
        $rawVersionInterfaceType = $manager->getType('VersionInterface');

        $versionName = $this->createVersionedTypeName($class);
        $coreFieldsFn = $rawType->config['fields'];
        $versionFieldsFn = $rawVersionInterfaceType->config['fields'];

        // Create the "version" type for this dataobject. Takes the original fields
        // and augments them with the Versioned_Version specific fields
        $versionType = new ObjectType([
            'name' => $versionName,
            'fields' => function () use ($coreFieldsFn, $versionFieldsFn, $manager, $memberType) {
                $coreFields = $coreFieldsFn();
                $versionFields = $versionFieldsFn();

                // Remove this recursive madness.
                unset($coreFields['Versions']);

                return array_merge($coreFields, $versionFields);
            },
            'interfaces' => function() use ($rawType, $manager) {
                return array_merge(
                    $rawType->getInterfaces(),
                    [$manager->getType('VersionInterface')]
                );
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
