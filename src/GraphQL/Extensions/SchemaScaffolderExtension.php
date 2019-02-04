<?php

namespace SilverStripe\Versioned\GraphQL\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\Versioned;

class SchemaScaffolderExtension extends Extension
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

    /**
     * @param array $config
     */
    public function onBeforeCreateFromConfig($config)
    {
        if (!isset($config['versioned'])) {
            return;
        }

        if (isset($config['versioned']['useVersionedFilter'])) {
            $this->setUseVersionedFilter((bool)$config['versioned']['useVersionedFilter']);
        }

        if (isset($config['versioned']['useVersionedMetadata'])) {
            $this->setUseVersionedMetadata((bool)$config['versioned']['useVersionedMetadata']);
        }
    }

    /**
     * @param DataObjectScaffolder $type
     */
    public function onAfterType(DataObjectScaffolder $type)
    {
        // Defined on DataObjectScaffolderExtension
        if ($type->hasExtension(DataObjectScaffolderExtension::class)) {
            $type->setUseVersionedFilter($this->getUseVersionedFilter());
            $type->setUseVersionedMetadata($this->getUseVersionedMetadata());
        }
    }

    /**
     * If any types are using Versioned, make sure Member is added as a type. Because
     * the Versioned_Version object is just ViewableData, it has to be added explicitly.
     *
     * @param Manager $manager
     */
    public function onBeforeAddToManager(Manager $manager)
    {
        if (!$this->getUseVersionedFilter()) {
            return;
        }

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
}
