<?php

namespace SilverStripe\Versioned\GraphQL\Scaffolding;

use InvalidArgumentException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\UnionScaffolder;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\GraphQL\VersionedStaticSchema;

/**
 * Scaffolds a UnionType based on the ancestry of a DataObject class.
 * Relies on types being made available elsewhere.
 */
class VersionInheritanceScaffolder extends UnionScaffolder implements ManagerMutatorInterface
{
    use Configurable;

    /**
     * @var string
     */
    protected $dataObjectClass;

    /**
     * @param string $dataObjectClass
     * @param string $name
     */
    public function __construct($dataObjectClass, $name)
    {
        $this->setDataObjectClass($dataObjectClass);

        parent::__construct(
            $name,
            $this->getTypes()
        );
    }

    /**
     * @return string
     */
    public function getDataObjectClass()
    {
        return $this->dataObjectClass;
    }

    /**
     * @param string $dataObjectClass
     * @return VersionInheritanceScaffolder
     */
    public function setDataObjectClass($dataObjectClass)
    {
        if (!class_exists($dataObjectClass)) {
            throw new InvalidArgumentException(sprintf(
                'Class %s does not exist.',
                $dataObjectClass
            ));
        }

        if (!is_subclass_of($dataObjectClass, DataObject::class)) {
            throw new InvalidArgumentException(sprintf(
                'Class %s is not a subclass of %s.',
                $dataObjectClass,
                DataObject::class
            ));
        }

        $this->dataObjectClass = $dataObjectClass;

        return $this;
    }

    /**
     * Get all the GraphQL types in the ancestry
     * @return array
     */
    public function getTypes()
    {
        $schema = StaticSchema::inst();
        $versionedSchema = VersionedStaticSchema::inst();
        $tree = array_merge(
            [$this->dataObjectClass],
            $schema->getAncestry($this->dataObjectClass),
            $schema->getDescendants($this->dataObjectClass)
        );

        return array_map(function ($class) use ($tree, $versionedSchema) {
            return $versionedSchema->versionTypeNameForDataObject($class);
        }, $tree);
    }

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $types = $this->getTypes();
        if (sizeof($types) === 1) {
            return;
        }

        $manager->addType(
            $this->scaffold($manager),
            $this->getName()
        );
    }

}
