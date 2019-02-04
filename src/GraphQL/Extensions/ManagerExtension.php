<?php

namespace SilverStripe\Versioned\GraphQL\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Versioned\GraphQL\Types\CopyToStageInputType;
use SilverStripe\Versioned\GraphQL\Types\VersionedInputType;
use SilverStripe\Versioned\GraphQL\Types\VersionedQueryMode;
use SilverStripe\Versioned\GraphQL\Types\VersionedStage;
use SilverStripe\Versioned\GraphQL\Types\VersionedStatus;

class ManagerExtension extends Extension
{
    /**
     * Adds the versioned types to all schemas
     *
     * @param $config
     */
    public function updateConfig(&$config)
    {
        if (!isset($config['types'])) {
            $config['types'] = [];
        }

        // Allow schemas to opt out of versioning (defaults to false when not set)
        if (!isset($config['useVersionedFilter']) || !(bool)$config['useVersionedFilter']) {
            $config['types']['VersionedStage'] = VersionedStage::class;
            $config['types']['VersionedStatus'] = VersionedStatus::class;
            $config['types']['VersionedQueryMode'] = VersionedQueryMode::class;
            $config['types']['VersionedInputType'] = VersionedInputType::class;
            $config['types']['CopyToStageInputType'] = CopyToStageInputType::class;
        }
    }
}
