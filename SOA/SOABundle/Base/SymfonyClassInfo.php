<?php
/**
 * This class allows us to define the complete location and type of a class and pass 
 * all of the information to instantiate that class inside of one object.
 */
namespace SOA\SOABundle\Base;

use Exception;


class SymfonyClassInfo
{
    /**
     * These are the acceptable type of classes that we can have information about.
     */
    const SYMFONY_CLASSINFO_MODEL      = 'model';
    const SYMFONY_CLASSINFO_PEER       = 'peer';
    const SYMFONY_CLASSINFO_QUERY      = 'query';
    const SYMFONY_CLASSINFO_CONTROLLER = 'controller';

    /**
     * This is the namespace that our class falls under.
     * 
     * @var string
     */
    public $namespace;

    /**
     * This is the bundle that our class falls under.
     * 
     * @var string
     */
    public $bundle;

    /**
     * This is the "name" of our class. This is not the full class name, but
     * the estimate model would be "Estimate".
     * 
     * @var string
     */
    public $entity;


    /**
     * This method will build a class info object when the namespace, bundle, and entity are already known.
     * 
     * @param $namespace
     * @param $bundle
     * @param $entity
     * @return SymfonyClassInfo
     */
    public static function createClassInfo($namespace, $bundle, $entity)
    {
        $classInfo = new SymfonyClassInfo();

        $classInfo->namespace = $namespace;
        $classInfo->bundle    = $bundle;
        $classInfo->entity    = $entity;

        return $classInfo;
    }


    /**
     * This method will get the fully-qualified namespace path so we can instantiate this object.
     * 
     * @param $classType
     * @return string
     */
    public function getClassPath($classType)
    {
        switch ($classType)
        {
            case self::SYMFONY_CLASSINFO_MODEL:
                return "\\{$this->namespace}\\{$this->bundle}Bundle\\Model\\{$this->entity}";

            case self::SYMFONY_CLASSINFO_PEER:
                return "\\{$this->namespace}\\{$this->bundle}Bundle\\Model\\{$this->entity}Peer";

            case self::SYMFONY_CLASSINFO_QUERY:
                return "\\{$this->namespace}\\{$this->bundle}Bundle\\Model\\{$this->entity}Query";

            case self::SYMFONY_CLASSINFO_CONTROLLER:
                return "\\{$this->namespace}\\{$this->bundle}Bundle\\Controller\\{$this->entity}Controller";
        }

        throw new Exception($classType . ' is not a class that can have a path generated.');
    }


    /**
     * This method will take a fully-qualified namespace path and pull the location 
     * information into our object state.
     * 
     * @param string $fullClassPath
     */
    public function parseClassPath($fullClassPath)
    {
        $parts = explode('\\', $fullClassPath);

        $this->namespace = $parts[0];
        $this->bundle = str_replace('Bundle', '', $parts[1]);

        $entity = $parts[3];
        $entity = str_replace('Query', '', $entity);
        $entity = str_replace('Peer', '', $entity);

        $this->entity = $entity;
    }
}