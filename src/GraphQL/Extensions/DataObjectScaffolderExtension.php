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
use SilverStripe\Versioned\GraphQL\VersionedStaticSchema;
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
        $schema = StaticSchema::inst();
        $versionedSchema = VersionedStaticSchema::inst();

        /* @var DataObjectScaffolder $owner */
        $owner = $this->owner;
        $memberType = $schema->typeNameForDataObject(Member::class);
        $instance = $owner->getDataObjectInstance();
        $class = $owner->getDataObjectClass();
        if (!$instance->hasExtension(Versioned::class)) {
            return;
        }

        /* @var ObjectType $rawType */
        $rawType = $owner->scaffold($manager);

        /** @var InterfaceType $rawVersionInterfaceType */
        $rawVersionInterfaceType = $manager->getType('VersionInterface');

        $versionName = $versionedSchema->versionTypeNameForDataObject($class);
        $coreFieldsFn = $rawType->config['fields'];
        $versionFieldsFn = $rawVersionInterfaceType->config['fields'];

        // Create the "version" type for this dataobject. Takes the original fields
        // and augments them with the Versioned_Version specific fields
        $versionType = new ObjectType([
            'name' => $versionName,
            'fields' => function () use ($coreFieldsFn, $versionFieldsFn, $manager, $memberType, $versionName, $class) {
                return array_merge($coreFieldsFn(), $versionFieldsFn());
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
        $owner->addFields(['Version']);

//        $rootClass = StaticSchema::inst()->fetchRootClassForTypeFromManager($class, $manager);
//        $inheritanceTypeName = VersionedStaticSchema::inst()->fetchVersionInheritanceTypeNameFromManager(
//            $rootClass,
//            $manager
//        );
//        $owner->nestedQuery('Versions', new ReadVersions($class, $inheritanceTypeName));

//        $versionName = VersionedStaticSchema::inst()->versionTypeNameForDataObject($class);
//        $owner->nestedQuery('Versions', new ReadVersions($class, $versionName));
    }

    /**
     * Create the nested versions query at the last possible moment,
     * which ensures that all types are registered and we can safely determine
     * the "root type" for the managed DataObject.
     *
     * This ensures that all types can share
     * the same Versions field in different type interface contexts.
     *
     * @param Manager $manager
     */
    public function onBeforeCreateFields(Manager $manager)
    {
        $owner = $this->owner;
        $class = $owner->getDataObjectClass();

        $rootClass = StaticSchema::inst()->fetchRootClassForTypeFromManager($class, $manager);
        $inheritanceTypeName = VersionedStaticSchema::inst()->fetchVersionInheritanceTypeNameFromManager(
            $rootClass,
            $manager
        );

//        $versionName = VersionedStaticSchema::inst()->versionTypeNameForDataObject($class);

        $owner->nestedQuery('Versions', new ReadVersions($class, $inheritanceTypeName));
    }

}
