<?php

namespace SilverStripe\Versioned\GraphQL;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;

class VersionedStaticSchema
{

    use Configurable;

    /**
     * @config
     * @var string
     */
    private static $inheritanceTypeSuffix = 'WithDescendants';

    /**
     * @var VersionedStaticSchema
     */
    private static $instance;

    /**
     * @return static
     */
    public static function inst()
    {
        if (!static::$instance) {
            static::setInstance(new static());
        }

        return static::$instance;
    }

    /**
     * @param VersionedStaticSchema $inst
     */
    public static function setInstance(VersionedStaticSchema $inst = null)
    {
        static::$instance = $inst;
    }

    /**
     * Removes the current instance
     */
    public static function reset()
    {
        static::setInstance();
    }

    /**
     * @param $class
     * @return string
     */
    public function versionTypeNameForDataObject($class)
    {
        return StaticSchema::inst()->typeNameForDataObject($class) . 'Version';
    }

    /**
     * Assumes that all types for this class hierarchy have already been registered.
     *
     * @throws \LogicException
     * @param string $class
     * @param Manager $manager
     * @return string
     */
    public function fetchVersionInheritanceTypeNameFromManager($class, Manager $manager)
    {
        $baseTypeName = StaticSchema::inst()->typeNameForDataObject($class);
        return $baseTypeName . $this->config()->get('inheritanceTypeSuffix');
    }

}
