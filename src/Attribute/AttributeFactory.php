<?php

/**
 * This file is part of MetaModels/core.
 *
 * (c) 2012-2017 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage Core
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2017 The MetaModels team.
 * @license    https://github.com/MetaModels/core/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\Attribute;

use MetaModels\Attribute\Events\CollectMetaModelAttributeInformationEvent;
use MetaModels\Attribute\Events\CreateAttributeEvent;
use MetaModels\Attribute\Events\CreateAttributeFactoryEvent;
use MetaModels\IMetaModel;
use MetaModels\IMetaModelsServiceContainer;
use MetaModels\MetaModelsEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This is the implementation of the Field factory to query instances of fields.
 *
 * Usually this is only used internally by {@link MetaModels\Factory}
 */
class AttributeFactory implements IAttributeFactory
{
    /**
     * The service container.
     *
     * @var IMetaModelsServiceContainer
     */
    protected $serviceContainer;

    /**
     * The event dispatcher.
     *
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * The registered type factories.
     *
     * @var IAttributeTypeFactory[]
     */
    protected $typeFactories = array();

    /**
     * Create a new instance.
     *
     * @param EventDispatcherInterface $eventDispatcher The event dispatcher to use.
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Set the service container.
     *
     * @param IMetaModelsServiceContainer $serviceContainer The service container to use.
     *
     * @return AttributeFactory
     *
     * @deprecated The service container will get removed, use the symfony service container instead.
     */
    public function setServiceContainer(IMetaModelsServiceContainer $serviceContainer, $deprecationNotice = true)
    {
        if ($deprecationNotice) {
            @trigger_error(
                '"' .__METHOD__ . '" is deprecated and will get removed.',
                E_USER_DEPRECATED
            );
        }

        $this->serviceContainer = $serviceContainer;

        if ($this->eventDispatcher->hasListeners(MetaModelsEvents::ATTRIBUTE_FACTORY_CREATE)) {
            @trigger_error(
                'Event "' .
                MetaModelsEvents::ATTRIBUTE_FACTORY_CREATE .
                '" is deprecated - register your attribute factories via the service container.',
                E_USER_DEPRECATED
            );
            $this->eventDispatcher->dispatch(
                MetaModelsEvents::ATTRIBUTE_FACTORY_CREATE,
                new CreateAttributeFactoryEvent($this)
            );
        }

        return $this;
    }

    /**
     * Retrieve the service container.
     *
     * @return IMetaModelsServiceContainer
     *
     * @deprecated The service container will get removed, use the symfony service container instead.
     */
    public function getServiceContainer()
    {
        @trigger_error(
            '"' .__METHOD__ . '" is deprecated - use the services from the service container.',
            E_USER_DEPRECATED
        );
        return $this->serviceContainer;
    }

    /**
     * Create an attribute instance from an information array.
     *
     * @param array      $information The attribute information.
     *
     * @param IMetaModel $metaModel   The MetaModel instance for which the attribute shall be created.
     *
     * @return IAttribute|null
     */
    public function createAttribute($information, $metaModel)
    {
        $event = new CreateAttributeEvent($information, $metaModel);
        $this->eventDispatcher->dispatch(CreateAttributeEvent::NAME, $event);

        if ($event->getAttribute()) {
            return $event->getAttribute();
        }

        $factory = $this->getTypeFactory($information['type']);

        if (!$factory) {
            return null;
        }

        return $factory->createInstance($information, $metaModel);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException When the type is already registered.
     */
    public function addTypeFactory(IAttributeTypeFactory $typeFactory)
    {
        $typeName = $typeFactory->getTypeName();
        if (isset($this->typeFactories[$typeName])) {
            throw new \RuntimeException('Attribute type ' . $typeName . ' is already registered.');
        }

        $this->typeFactories[$typeName] = $typeFactory;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFactory($typeFactory)
    {
        return isset($this->typeFactories[(string) $typeFactory]) ? $this->typeFactories[(string) $typeFactory] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeTypeMatchesFlags($name, $flags)
    {
        $factory = $this->getTypeFactory($name);

        // Shortcut, if all are valid, return all. :)
        if ($flags === self::FLAG_ALL) {
            return true;
        }

        return (($flags & self::FLAG_INCLUDE_TRANSLATED) && $factory->isTranslatedType())
            || (($flags & self::FLAG_INCLUDE_SIMPLE) && $factory->isSimpleType())
            || (($flags & self::FLAG_INCLUDE_COMPLEX) && $factory->isComplexType());
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeNames($flags = false)
    {
        if ($flags === false) {
            $flags = self::FLAG_ALL;
        }

        $result = array();
        foreach (array_keys($this->typeFactories) as $name) {
            if (!$this->attributeTypeMatchesFlags($name, $flags)) {
                continue;
            }

            $result[] = $name;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function collectAttributeInformation(IMetaModel $metaModel)
    {
        $event = new CollectMetaModelAttributeInformationEvent($metaModel);

        $this->eventDispatcher->dispatch($event::NAME, $event);

        return $event->getAttributeInformation();
    }

    /**
     * {@inheritdoc}
     */
    public function createAttributesForMetaModel($metaModel)
    {
        $attributes = array();
        foreach ($this->collectAttributeInformation($metaModel) as $information) {
            $attribute = $this->createAttribute($information, $metaModel);
            if ($attribute) {
                $attributes[] = $attribute;
            }
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getIconForType($type)
    {
        return isset($this->typeFactories[(string) $type]) ? $this->typeFactories[(string) $type]->getTypeIcon() : null;
    }
}