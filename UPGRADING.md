From 1.6.x to 2.0.0
===================

- Removed the abstract classes `GenericSerializationVisitor` and `GenericDeserializationVisitor`, extend the specific visitor instead.
- Added `GraphNavigatorInterface` interface, changed method signatures to use `GraphNavigatorInterface` instead of `GraphNavigator` class
- `GraphNavigator` is an abstract class now and is ony for internal proposes
- Removed deprecated method `VisitorInterface::getNavigator()` from all the visitors, use `Context::getNavigator()` instead
- Removed deprecated method `JsonSerializationVisitor::addData`, use `JsonSerializationVisitor::setData` instead
- Removed Propel and PhpCollection support
- Changed default date format from `ISO8601` to `RFC3339`
- Event listeners/handlers class names are case sensitive now
- Changed default "serializeNull" value from `NULL` to `false`
- Removed `Context::accept()` method, use `Context::getNavigator()->accept()` 
- Removed `AbstractVisitor::getNamingStrategy()` method from all the visitors
- Removed Symfony 2.x support
- Removed PHP Driver metadata support
- Removed in-object handler callbacks, use event listeners instead
- Deprecated `VisitorInterface`, use `SerializationVisitorInterface` and `DeserializationVisitorInterface` instead
- Changed `SerializerInterface::serialize` signature to `serialize($data, $format, SerializationContext $context = null, $type = null)`
- Changed `ArrayTransformerInterface::toArray` signature to `toArray($data, SerializationContext $context = null, $type = null);`
- Removed `Context::initialize`, use `DeserializationContext::initialize` and `SerializationContext::initialize`
- Removed `Serializer::setSerializationContextFactory` and `Serializer::setDeserializationContextFactory`, context factories are now constructor parameters
- Marked as `final` many classes, use composition over inheritance
- PHP 7.1 is the minimum PHP version
 
From 0.13 to ???
================

- If you have implemented your own ObjectConstructor, you need to add the DeserializationContext as an additional
  parameter for the ``construct`` method.


From 0.11 to 0.12
=================

- GraphNavigator::detachObject has been removed, you can directly use Context::stopVisiting instead.
- VisitorInterface::getNavigator was deprecated, instead use Context::accept
- Serializer::setGroups, Serializer::setExclusionStrategy and Serializer::setVersion were removed, these settings must
  now be passed as part of a new Context object.

    Before:

        $serializer->setVersion(1);
        $serializer->serialize($data, 'json');

    After:

        $serializer->serialize($data, 'json', SerializationContext::create()->setVersion(1));

- All visit??? methods of the VisitorInterface, now require a third argument, the Context; the context is for example
  passed as an additional argument to handlers, exclusion strategies, and also available in event listeners.
