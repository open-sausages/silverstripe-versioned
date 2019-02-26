<?php

namespace SilverStripe\Versioned\GraphQL\Extensions;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extension;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\GraphQL\Scaffolding\VersionInheritanceScaffolder;
use SilverStripe\Versioned\GraphQL\VersionedStaticSchema;
use SilverStripe\Versioned\Versioned;

class SchemaScaffolderExtension extends Extension
{
    use Configurable;

    /**
     * If any types are using Versioned, make sure Member is added as a type. Because
     * the Versioned_Version object is just ViewableData, it has to be added explicitly.
     *
     * @param Manager $manager
     */
    public function onBeforeAddToManager(Manager $manager)
    {
        $memberType = StaticSchema::inst()->typeNameForDataObject(Member::class);
        if ($manager->hasType($memberType)) {
            return;
        }

        /* @var SchemaScaffolder $owner */
        $owner = $this->owner;

        foreach ($owner->getTypes() as $scaffold) {
            if ($scaffold->getDataObjectInstance()->hasExtension(Versioned::class)) {
                $owner->type(Member::class);
                break;
            }
        }
    }

    public function onAfterAddToManager(Manager $manager)
    {
        /** @var DataObjectScaffolder $type */
        foreach ($this->owner->getTypes() as $type) {
            // Only create types for "root" class. This ensures that all types can share
            // the same Versions field in different type interface contexts.
            $rootClass = StaticSchema::inst()->fetchRootClassForTypeFromManager(
                $type->getDataObjectClass(),
                $manager
            );

            // The inheritance type is a union
            $inheritanceTypeName = VersionedStaticSchema::inst()->fetchVersionInheritanceTypeNameFromManager(
                $rootClass,
                $manager
            );

            $scaffolder = new VersionInheritanceScaffolder(
                $rootClass,
                $inheritanceTypeName
            );
            $scaffolder->addToManager($manager);
        }
    }
}
